(function ($) {
    'use strict';

    var config = null;
    var forecastData = null;
    var currentStartDate = null;
    var ratesCache = null;

    function parseJsonNode(id) {
        var node = document.getElementById(id);
        if (!node || !node.textContent) {
            return null;
        }
        try {
            return JSON.parse(node.textContent);
        } catch (e) {
            return null;
        }
    }

    function formatMoney(amount) {
        var symbol = config && config.currencySymbol ? config.currencySymbol : '$';
        return symbol + parseFloat(amount || 0).toLocaleString('en', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function dayLabel(dayOfWeek) {
        var labels = [
            typeof l === 'function' ? l('Mon', true) : 'Mon',
            typeof l === 'function' ? l('Tue', true) : 'Tue',
            typeof l === 'function' ? l('Wed', true) : 'Wed',
            typeof l === 'function' ? l('Thu', true) : 'Thu',
            typeof l === 'function' ? l('Fri', true) : 'Fri',
            typeof l === 'function' ? l('Sat', true) : 'Sat',
            typeof l === 'function' ? l('Sun', true) : 'Sun'
        ];
        return labels[parseInt(dayOfWeek, 10)] || '';
    }

    function addDays(dateStr, days) {
        var date = new Date(dateStr + 'T12:00:00');
        date.setDate(date.getDate() + parseInt(days, 10));
        return date.toISOString().slice(0, 10);
    }

    function updateDateRangeLabel(rates) {
        if (!rates || !rates.length) {
            $('#rms-date-range-label').text('');
            return;
        }
        var first = rates[0].date;
        var last = rates[rates.length - 1].date;
        $('#rms-date-range-label').text(first + ' – ' + last);
    }

    function restrictionBadge(rate) {
        var badges = [];
        if (rate.closed_to_arrival === '1') {
            badges.push('<span class="rms-badge rms-badge--cta">CTA</span>');
        }
        if (rate.closed_to_departure === '1') {
            badges.push('<span class="rms-badge rms-badge--ctd">CTD</span>');
        }
        if (rate.can_be_sold_online === '0') {
            badges.push('<span class="rms-badge rms-badge--stop">Stop</span>');
        }
        if (rate.minimum_length_of_stay && parseInt(rate.minimum_length_of_stay, 10) > 1) {
            badges.push('<span class="rms-badge">Min ' + rate.minimum_length_of_stay + 'n</span>');
        }
        if (rate.maximum_length_of_stay && parseInt(rate.maximum_length_of_stay, 10) < 365) {
            badges.push('<span class="rms-badge">Max ' + rate.maximum_length_of_stay + 'n</span>');
        }
        return badges.join(' ');
    }

    function buildRatesGrid($container, rates, view) {
        if (!rates || !rates.length) {
            $container.html('<p class="text-muted rms-empty">' + (typeof l === 'function' ? l('rms_no_rates', true) : 'No rate data for this period.') + '</p>');
            return;
        }

        var html = '<div class="table-responsive"><table class="table table-bordered table-condensed rms-calendar-table"><thead><tr><th></th>';

        $.each(rates, function (i, rate) {
            html += '<th class="rms-day-col"><div class="rms-day-date">' + rate.date + '</div>';
            html += '<div class="rms-day-dow">' + dayLabel(rate.day_of_week) + '</div></th>';
        });

        html += '</tr></thead><tbody>';

        if (view === 'rates') {
            var rateRows = [
                { label: typeof l === 'function' ? l('Rate', true) + ' (1)' : 'Rate (1)', key: 'adult_1_rate' },
                { label: typeof l === 'function' ? l('Rate', true) + ' (2)' : 'Rate (2)', key: 'adult_2_rate' },
                { label: typeof l === 'function' ? l('Rate', true) + ' (3)' : 'Rate (3)', key: 'adult_3_rate' },
                { label: typeof l === 'function' ? l('Rate', true) + ' (4)' : 'Rate (4)', key: 'adult_4_rate' }
            ];

            $.each(rateRows, function (_, row) {
                html += '<tr><td class="rms-row-label">' + row.label + '</td>';
                $.each(rates, function (__, rate) {
                    var val = rate[row.key];
                    html += '<td class="rms-rate-cell">' + (val !== null && val !== '' ? parseFloat(val).toFixed(2) : '—') + '</td>';
                });
                html += '</tr>';
            });
        } else {
            html += '<tr><td class="rms-row-label">Restrictions</td>';
            $.each(rates, function (__, rate) {
                html += '<td class="rms-restriction-cell">' + (restrictionBadge(rate) || '<span class="text-muted">—</span>') + '</td>';
            });
            html += '</tr>';
            html += '<tr><td class="rms-row-label">' + (typeof l === 'function' ? l('Can be sold online', true) : 'Online') + '</td>';
            $.each(rates, function (__, rate) {
                var online = rate.can_be_sold_online !== '0';
                html += '<td>' + (online
                    ? '<span class="rms-badge rms-badge--open">Open</span>'
                    : '<span class="rms-badge rms-badge--stop">Closed</span>') + '</td>';
            });
            html += '</tr>';
        }

        html += '</tbody></table></div>';
        $container.html(html);
    }

    function loadRates() {
        var ratePlanId = $('#rms-rate-plan-select').val();
        var roomTypeId = $('#rms-rate-plan-select option:selected').data('room-type-id');

        if (!ratePlanId || !config) {
            return;
        }

        $('#rms-rates-grid, #rms-restrictions-grid').addClass('rms-loading');

        $.post(config.ratesUrl, {
            rate_plan_id: ratePlanId,
            room_type_id: roomTypeId,
            start_date: currentStartDate
        }).done(function (data) {
            ratesCache = data.rates || [];
            updateDateRangeLabel(ratesCache);
            buildRatesGrid($('#rms-rates-grid'), ratesCache, 'rates');
            buildRatesGrid($('#rms-restrictions-grid'), ratesCache, 'restrictions');
        }).fail(function () {
            $('#rms-rates-grid, #rms-restrictions-grid').html('<p class="text-danger">Failed to load rates.</p>');
        }).always(function () {
            $('#rms-rates-grid, #rms-restrictions-grid').removeClass('rms-loading');
        });
    }

    function renderForecastChart(data) {
        var $chart = $('#rms-forecast-chart');
        if (!data || !data.labels || !data.labels.length) {
            $chart.empty();
            return;
        }

        var maxVal = 0;
        $.each(data.forecastTotal, function (_, v) {
            maxVal = Math.max(maxVal, parseFloat(v) || 0);
        });
        if (maxVal < 1) {
            maxVal = 1;
        }

        var html = '<div class="rms-chart-bars">';
        $.each(data.labels, function (i, label) {
            var onBooks = parseFloat(data.onBooks[i]) || 0;
            var potential = parseFloat(data.potential[i]) || 0;
            var total = onBooks + potential;
            var heightPct = Math.round((total / maxVal) * 100);
            var onBooksPct = total > 0 ? Math.round((onBooks / total) * 100) : 0;

            html += '<div class="rms-chart-bar-wrap" title="' + label + ': ' + formatMoney(total) + '">';
            html += '<div class="rms-chart-bar" style="height:' + heightPct + '%">';
            html += '<div class="rms-chart-bar-onbooks" style="height:' + onBooksPct + '%"></div>';
            html += '</div>';
            html += '<span class="rms-chart-label">' + label + '</span>';
            html += '</div>';
        });
        html += '</div>';
        html += '<div class="rms-chart-legend">';
        html += '<span><i class="rms-legend-onbooks"></i> ' + (data.i18n && data.i18n.onBooks ? data.i18n.onBooks : 'On books') + '</span>';
        html += '<span><i class="rms-legend-potential"></i> ' + (data.i18n && data.i18n.potential ? data.i18n.potential : 'Potential') + '</span>';
        html += '</div>';

        $chart.html(html);
    }

    function renderForecastTable(data) {
        var $body = $('#rms-forecast-table-body');
        $body.empty();

        if (!data || !data.rows) {
            return;
        }

        $.each(data.rows, function (_, row) {
            $body.append(
                '<tr>' +
                '<td>' + row.date + '</td>' +
                '<td>' + row.occupied + '</td>' +
                '<td>' + row.available + '</td>' +
                '<td>' + row.occupancy_pct + '%</td>' +
                '<td>' + formatMoney(row.on_books_revenue) + '</td>' +
                '<td>' + formatMoney(row.potential_revenue) + '</td>' +
                '<td><strong>' + formatMoney(row.forecast_total) + '</strong></td>' +
                '<td>' + formatMoney(row.adr) + '</td>' +
                '<td>' + formatMoney(row.revpar) + '</td>' +
                '</tr>'
            );
        });
    }

    function updateForecastSummary(data) {
        if (!data || !data.summary) {
            return;
        }
        $('#rms-stat-on-books').text(formatMoney(data.summary.onBooksRevenue));
        $('#rms-stat-potential').text(formatMoney(data.summary.potentialRevenue));
        $('#rms-stat-total').text(formatMoney(data.summary.forecastTotal));
        $('#rms-stat-occupancy').text(parseInt(data.summary.avgOccupancy, 10) + '%');
    }

    function applyForecast(data) {
        forecastData = data;
        renderForecastChart(data);
        renderForecastTable(data);
        updateForecastSummary(data);
    }

    function loadForecast(days) {
        if (!config) {
            return;
        }

        $('#rms-forecast-chart').addClass('rms-loading');

        $.get(config.forecastUrl, { days: days }).done(function (response) {
            if (response && response.success && response.data) {
                applyForecast(response.data);
            }
        }).always(function () {
            $('#rms-forecast-chart').removeClass('rms-loading');
        });
    }

    function bindEvents() {
        $('#rms-rate-plan-select').on('change', function () {
            var planId = $(this).val();
            if (config && planId) {
                $('#rms-edit-rates-link').attr('href', config.editRatesBaseUrl + planId);
            }
            loadRates();
        });

        $('#rms-prev-week').on('click', function () {
            currentStartDate = addDays(currentStartDate, -7);
            loadRates();
        });

        $('#rms-next-week').on('click', function () {
            currentStartDate = addDays(currentStartDate, 7);
            loadRates();
        });

        $('#rms-today').on('click', function () {
            currentStartDate = config.sellingDate;
            loadRates();
        });

        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            if ($(e.target).attr('href') === '#rms-restrictions-tab' && ratesCache) {
                buildRatesGrid($('#rms-restrictions-grid'), ratesCache, 'restrictions');
            }
        });

        $('.rms-forecast-range-btn').on('click', function () {
            var days = parseInt($(this).data('days'), 10);
            if (!days) {
                return;
            }
            $('.rms-forecast-range-btn').removeClass('btn-primary active').addClass('btn-default');
            $(this).removeClass('btn-default').addClass('btn-primary active');
            loadForecast(days);
        });
    }

    $(function () {
        if (!$('.rms-page').length) {
            return;
        }

        config = parseJsonNode('rms-config');
        forecastData = parseJsonNode('rms-forecast-data');

        if (!config) {
            return;
        }

        currentStartDate = config.sellingDate;

        if (forecastData) {
            applyForecast(forecastData);
        }

        if ($('#rms-rate-plan-select option').length) {
            loadRates();
        } else {
            $('#rms-rates-grid, #rms-restrictions-grid').html(
                '<p class="text-muted">' + (typeof l === 'function' ? l('rms_no_rate_plans', true) : 'Create a rate plan in Settings to get started.') + '</p>'
            );
        }

        bindEvents();
    });
}(jQuery));
