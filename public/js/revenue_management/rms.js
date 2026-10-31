(function ($) {
    'use strict';

    var config = null;
    var forecastData = null;
    var currentStartDate = null;
    var ratesCache = null;
    var forecastChart = null;
    var paceChart = null;
    var aiLoaded = false;
    var paceLoaded = false;
    var propertyRatesStartDate = null;
    var propertyDayCount = 14;
    var channelParityStartDate = null;
    var channelParityDayCount = 14;
    var channelParityData = null;
    var demandLoaded = false;
    var demandData = null;

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

    function t(key, fallback) {
        return config && config.i18n && config.i18n[key] ? config.i18n[key] : fallback;
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

    function trendBadge(change) {
        change = parseInt(change, 10) || 0;
        if (change > 0) {
            return '<span class="rms-trend rms-trend--up"><i class="fa fa-arrow-up"></i> ' + Math.abs(change) + '%</span>';
        }
        if (change < 0) {
            return '<span class="rms-trend rms-trend--down"><i class="fa fa-arrow-down"></i> ' + Math.abs(change) + '%</span>';
        }
        return '<span class="rms-trend rms-trend--flat">—</span>';
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

    function editableCell(date, field, value, view, ratePlanId) {
        if (view !== 'rates') {
            return value !== null && value !== '' ? parseFloat(value).toFixed(2) : '—';
        }

        var display = value !== null && value !== '' ? parseFloat(value).toFixed(2) : '';
        var planAttr = ratePlanId ? ' data-rate-plan-id="' + ratePlanId + '"' : '';
        return '<button type="button" class="rms-editable-cell" '
            + 'data-date="' + date + '" data-field="' + field + '" data-value="' + display + '"' + planAttr + ' '
            + 'title="Click to edit">' + (display !== '' ? display : '—') + '</button>';
    }

    function activeRatePlanSelect() {
        if ($('#rms-restrictions-tab').hasClass('active') && $('#rms-restrictions-rate-plan-select').length) {
            return $('#rms-restrictions-rate-plan-select');
        }
        return $('#rms-rate-plan-select');
    }

    function restrictionToggle(date, field, isActive, onLabel, offLabel) {
        var nextValue = isActive ? '0' : '1';
        return '<button type="button" class="rms-restriction-toggle ' + (isActive ? 'is-on' : 'is-off') + '" '
            + 'data-date="' + date + '" data-field="' + field + '" data-value="' + nextValue + '" '
            + 'title="' + (isActive ? offLabel : onLabel) + '">'
            + (isActive ? onLabel : offLabel) + '</button>';
    }

    function minStayControl(date, value) {
        var display = value && parseInt(value, 10) > 0 ? parseInt(value, 10) : 1;
        return '<button type="button" class="rms-min-stay-cell" data-date="' + date + '" '
            + 'data-field="minimum_length_of_stay" data-value="' + display + '" '
            + 'title="Click to change min stay">' + display + 'n min</button>';
    }

    function buildRestrictionsGrid($container, rates) {
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

        html += '<tr><td class="rms-row-label">CTA</td>';
        $.each(rates, function (_, rate) {
            html += '<td>' + restrictionToggle(rate.date, 'closed_to_arrival', rate.closed_to_arrival === '1', 'CTA', 'Open') + '</td>';
        });
        html += '</tr>';

        html += '<tr><td class="rms-row-label">CTD</td>';
        $.each(rates, function (_, rate) {
            html += '<td>' + restrictionToggle(rate.date, 'closed_to_departure', rate.closed_to_departure === '1', 'CTD', 'Open') + '</td>';
        });
        html += '</tr>';

        html += '<tr><td class="rms-row-label">' + (typeof l === 'function' ? l('Can be sold online', true) : 'Online') + '</td>';
        $.each(rates, function (_, rate) {
            var online = rate.can_be_sold_online !== '0';
            html += '<td>' + restrictionToggle(rate.date, 'can_be_sold_online', online, 'Open', 'Stop') + '</td>';
        });
        html += '</tr>';

        html += '<tr><td class="rms-row-label">Min stay</td>';
        $.each(rates, function (_, rate) {
            html += '<td>' + minStayControl(rate.date, rate.minimum_length_of_stay) + '</td>';
        });
        html += '</tr>';

        html += '</tbody></table></div>';
        $container.html(html);
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
                    html += '<td class="rms-rate-cell">' + editableCell(rate.date, row.key, val, view) + '</td>';
                });
                html += '</tr>';
            });
        } else {
            html += '<tr><td class="rms-row-label">Restrictions</td>';
            $.each(rates, function (__, rate) {
                html += '<td class="rms-restriction-cell">' + (restrictionBadge(rate) || '<span class="text-muted">—</span>') + '</td>';
            });
            html += '</tr>';
        }

        html += '</tbody></table></div>';
        $container.html(html);
    }

    function loadRates() {
        var $select = activeRatePlanSelect();
        var ratePlanId = $select.val();
        var roomTypeId = $select.find('option:selected').data('room-type-id');

        if (!ratePlanId || !config) {
            return;
        }

        $('.rms-sync-rate-plan').val(ratePlanId);

        $('#rms-rates-grid, #rms-restrictions-grid').addClass('rms-loading');

        $.post(config.ratesUrl, {
            rate_plan_id: ratePlanId,
            room_type_id: roomTypeId,
            start_date: currentStartDate
        }).done(function (data) {
            ratesCache = data.rates || [];
            updateDateRangeLabel(ratesCache);
            buildRatesGrid($('#rms-rates-grid'), ratesCache, 'rates');
            buildRestrictionsGrid($('#rms-restrictions-grid'), ratesCache);
        }).fail(function () {
            $('#rms-rates-grid, #rms-restrictions-grid').html('<p class="text-danger">Failed to load rates.</p>');
        }).always(function () {
            $('#rms-rates-grid, #rms-restrictions-grid').removeClass('rms-loading');
        });
    }

    function postRateUpdate(payload, $trigger, onSuccess, onError) {
        $trigger.addClass('rms-saving');

        $.post(config.updateRateUrl, payload).done(function (response) {
            if (response && response.success) {
                if (typeof onSuccess === 'function') {
                    onSuccess(response);
                }
                loadRates();
            } else {
                if (typeof onError === 'function') {
                    onError(response);
                } else {
                    alert(response && response.message ? response.message : t('updateFailed', 'Update failed.'));
                }
            }
        }).fail(function () {
            if (typeof onError === 'function') {
                onError();
            } else {
                alert(t('updateFailed', 'Update failed.'));
            }
        }).always(function () {
            $trigger.removeClass('rms-saving');
        });
    }

    function renderForecastChart(data) {
        var $wrap = $('#rms-forecast-chart');
        if (typeof Chart === 'undefined' || !data || !data.labels || !data.labels.length) {
            $wrap.empty();
            forecastChart = null;
            return;
        }

        $wrap.html('<canvas id="rms-forecast-canvas" height="220"></canvas>');
        var canvas = document.getElementById('rms-forecast-canvas');
        if (!canvas) {
            return;
        }

        var ctx = canvas.getContext('2d');
        forecastChart = new Chart(ctx).Bar({
            labels: data.labels,
            datasets: [
                {
                    label: data.i18n && data.i18n.onBooks ? data.i18n.onBooks : 'On books',
                    fillColor: 'rgba(63, 106, 216, 0.85)',
                    strokeColor: '#3f6ad8',
                    highlightFill: 'rgba(63, 106, 216, 0.95)',
                    highlightStroke: '#2d4fba',
                    data: data.onBooks || []
                },
                {
                    label: data.i18n && data.i18n.potential ? data.i18n.potential : 'Potential',
                    fillColor: 'rgba(222, 226, 230, 0.95)',
                    strokeColor: '#adb5bd',
                    highlightFill: 'rgba(206, 212, 218, 1)',
                    highlightStroke: '#868e96',
                    data: data.potential || []
                }
            ]
        }, {
            responsive: true,
            maintainAspectRatio: false,
            scaleBeginAtZero: true,
            scaleShowGridLines: true,
            scaleGridLineColor: '#f0f2f7',
            scaleLineColor: '#e8eaf0',
            scaleFontColor: '#6b7385',
            scaleFontFamily: "'Nunito', 'Segoe UI', sans-serif",
            scaleFontSize: 11,
            barShowStroke: true,
            barStrokeWidth: 1,
            multiTooltipTemplate: function (values) {
                var total = 0;
                $.each(values, function (_, item) {
                    total += parseFloat(item.value) || 0;
                });
                return values.length ? values[0].label + ': ' + formatMoney(total) : '';
            }
        });
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

        if (data.pickup) {
            $('#rms-stat-pickup').text(parseInt(data.pickup.room_nights, 10));
        }

        if (data.yoy) {
            $('#rms-stat-yoy-rev').html('vs last year ' + trendBadge(data.yoy.forecast_change_pct));
            $('#rms-stat-yoy-occ').html('vs last year ' + trendBadge(data.yoy.occupancy_change_pct));
        }
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

    function insightTypeClass(type) {
        if (type === 'positive') {
            return 'rms-ai-item--positive';
        }
        if (type === 'warning') {
            return 'rms-ai-item--warning';
        }
        return 'rms-ai-item--neutral';
    }

    function renderAiInsights(payload) {
        var $body = $('#rms-ai-body');
        $body.removeClass('rms-loading');

        if (!payload || !payload.success) {
            $body.html('<p class="text-danger">' + t('aiError', 'Unable to load insights.') + '</p>');
            return;
        }

        var html = '';
        if (payload.summary) {
            html += '<p class="rms-ai-summary">' + $('<div/>').text(payload.summary).html() + '</p>';
        }

        if (payload.insights && payload.insights.length) {
            html += '<ul class="rms-ai-list">';
            $.each(payload.insights, function (_, item) {
                html += '<li class="rms-ai-item ' + insightTypeClass(item.type) + '">';
                html += '<strong>' + $('<div/>').text(item.title || '').html() + '</strong>';
                html += '<span>' + $('<div/>').text(item.detail || '').html() + '</span>';
                html += '</li>';
            });
            html += '</ul>';
        }

        if (payload.suggestions && payload.suggestions.length) {
            html += '<h4 class="rms-ai-subtitle">' + t('suggestions', 'Suggested actions') + '</h4>';
            html += '<ul class="rms-ai-suggestions">';
            $.each(payload.suggestions, function (idx, item) {
                html += '<li>';
                html += '<strong>' + $('<div/>').text(item.title || '').html() + '</strong>';
                html += '<span>' + $('<div/>').text(item.detail || '').html() + '</span>';
                if (item.applicable && item.action && item.date) {
                    html += '<div class="rms-ai-actions">';
                    html += '<button type="button" class="btn btn-xs btn-success rms-apply-suggestion" data-index="' + idx + '"';
                    html += ' data-action="' + $('<div/>').text(item.action).html() + '"';
                    html += ' data-date="' + $('<div/>').text(item.date).html() + '"';
                    if (item.adjustment_pct !== undefined && item.adjustment_pct !== null) {
                        html += ' data-adjustment-pct="' + item.adjustment_pct + '"';
                    }
                    if (item.value !== undefined && item.value !== null) {
                        html += ' data-value="' + item.value + '"';
                    }
                    html += '>' + $('<div/>').text(item.apply_label || t('applyLabel', 'Apply')).html() + '</button>';
                    html += '</div>';
                }
                html += '</li>';
            });
            html += '</ul>';
        }

        window.rmsLastAiPayload = payload;

        $body.html(html || '<p class="text-muted">' + t('aiError', 'No insights available.') + '</p>');

        var sourceLabel = payload.source === 'ai'
            ? t('aiSourceAi', 'Powered by AI')
            : t('aiSourceRules', 'Smart recommendations (configure OPENAI_API_KEY for AI narratives)');
        $('#rms-ai-source').text(sourceLabel);
    }

    function loadAiInsights(force) {
        if (!config || !config.aiInsightsUrl) {
            return;
        }

        if (aiLoaded && !force) {
            return;
        }

        var days = config.forecastDays || 28;
        var $body = $('#rms-ai-body');
        $body.addClass('rms-loading').html('<p class="text-muted">' + t('aiLoading', 'Analyzing revenue data…') + '</p>');

        $.get(config.aiInsightsUrl, { days: days }).done(function (response) {
            renderAiInsights(response);
            aiLoaded = true;
        }).fail(function () {
            $body.removeClass('rms-loading').html('<p class="text-danger">' + t('aiError', 'Unable to load insights.') + '</p>');
        });
    }

    function beginInlineEdit($btn) {
        if ($btn.hasClass('rms-editing')) {
            return;
        }

        var current = $btn.data('value');
        var input = $('<input type="number" step="0.01" min="0" class="form-control input-sm rms-inline-input" />');
        input.val(current);

        $btn.addClass('rms-editing').hide();
        $btn.after(input);
        input.focus().select();

        function finish(save) {
            input.off('blur keydown');
            $btn.show().removeClass('rms-editing');

            if (!save) {
                input.remove();
                return;
            }

            var newValue = input.val();
            input.remove();

            if (newValue === '' || isNaN(parseFloat(newValue))) {
                return;
            }

            $btn.addClass('rms-saving').text('…');

            postRateUpdate({
                rate_plan_id: $btn.data('rate-plan-id') || activeRatePlanSelect().val(),
                date: $btn.data('date'),
                field: $btn.data('field'),
                value: newValue
            }, $btn, function () {
                $btn.data('value', parseFloat(newValue).toFixed(2));
                $btn.text(parseFloat(newValue).toFixed(2));
            }, function () {
                $btn.text(current || '—');
            });
        }

        input.on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                finish(true);
            } else if (e.key === 'Escape') {
                finish(false);
            }
        });
        input.on('blur', function () {
            finish(true);
        });
    }

    function applySuggestion($btn) {
        if ($btn.hasClass('is-applied') || $btn.prop('disabled')) {
            return;
        }

        var action = $btn.data('action');
        var date = $btn.data('date');
        if (!action || !date) {
            return;
        }

        if (!window.confirm(t('applyConfirm', 'Apply this recommendation to all default BAR rate plans for this date?'))) {
            return;
        }

        var payload = {
            action: action,
            date: date
        };

        if ($btn.data('adjustment-pct') !== undefined) {
            payload.adjustment_pct = $btn.data('adjustment-pct');
        }
        if ($btn.data('value') !== undefined) {
            payload.value = $btn.data('value');
        }

        $btn.prop('disabled', true).text('…');

        $.post(config.applySuggestionUrl, payload).done(function (response) {
            if (response && response.success) {
                $btn.removeClass('btn-success').addClass('btn-default is-applied')
                    .text(t('appliedLabel', 'Applied'));
                if (response.message) {
                    loadForecast(config.forecastDays || 28);
                    loadRates();
                }
            } else {
                alert(response && response.message ? response.message : t('applyFailed', 'Unable to apply recommendation.'));
                $btn.prop('disabled', false).text($btn.data('original-label') || t('applyLabel', 'Apply'));
            }
        }).fail(function () {
            alert(t('applyFailed', 'Unable to apply recommendation.'));
            $btn.prop('disabled', false).text($btn.data('original-label') || t('applyLabel', 'Apply'));
        });
    }

    function cycleMinStay($btn) {
        if ($btn.hasClass('rms-saving')) {
            return;
        }

        var current = parseInt($btn.data('value'), 10) || 1;
        var next = current >= 7 ? 1 : current + 1;

        postRateUpdate({
            rate_plan_id: activeRatePlanSelect().val(),
            date: $btn.data('date'),
            field: 'minimum_length_of_stay',
            value: next
        }, $btn, function () {
            $btn.data('value', next).text(next + 'n min');
        });
    }

    function paceDeltaClass(delta) {
        delta = parseInt(delta, 10) || 0;
        if (delta > 0) {
            return 'rms-pace-delta--up';
        }
        if (delta < 0) {
            return 'rms-pace-delta--down';
        }
        return 'rms-pace-delta--flat';
    }

    function renderPaceStats(data) {
        var summary = data && data.summary ? data.summary : {};
        var html = '';
        html += '<div class="rms-stat-card"><span class="rms-stat-label">Avg. pace Δ</span>';
        html += '<span class="rms-stat-value">' + (summary.avg_pace_delta || 0) + ' pts</span></div>';
        html += '<div class="rms-stat-card"><span class="rms-stat-label">Days ahead of STLY</span>';
        html += '<span class="rms-stat-value">' + (summary.days_ahead || 0) + '</span></div>';
        html += '<div class="rms-stat-card"><span class="rms-stat-label">Days behind STLY</span>';
        html += '<span class="rms-stat-value">' + (summary.days_behind || 0) + '</span></div>';
        html += '<div class="rms-stat-card rms-stat-card--highlight"><span class="rms-stat-label">Total 7-day pickup</span>';
        html += '<span class="rms-stat-value">' + (summary.total_pickup || 0) + '</span>';
        html += '<span class="rms-stat-sub">room-nights</span></div>';
        $('#rms-pace-stats').html(html);
    }

    function renderPaceChart(data) {
        var $wrap = $('#rms-pace-chart');
        if (typeof Chart === 'undefined' || !data || !data.labels || !data.labels.length) {
            $wrap.empty();
            paceChart = null;
            return;
        }

        $wrap.html('<canvas id="rms-pace-canvas" height="220"></canvas>');
        var canvas = document.getElementById('rms-pace-canvas');
        if (!canvas) {
            return;
        }

        var ctx = canvas.getContext('2d');
        paceChart = new Chart(ctx).Line({
            labels: data.labels,
            datasets: [
                {
                    label: data.i18n && data.i18n.currentYear ? data.i18n.currentYear : 'This year',
                    fillColor: 'rgba(63, 106, 216, 0.08)',
                    strokeColor: '#3f6ad8',
                    pointColor: '#3f6ad8',
                    pointStrokeColor: '#fff',
                    data: data.currentOccupancy || []
                },
                {
                    label: data.i18n && data.i18n.priorYear ? data.i18n.priorYear : 'STLY',
                    fillColor: 'rgba(148, 163, 184, 0.05)',
                    strokeColor: '#94a3b8',
                    pointColor: '#94a3b8',
                    pointStrokeColor: '#fff',
                    data: data.priorOccupancy || []
                }
            ]
        }, {
            responsive: true,
            maintainAspectRatio: false,
            scaleBeginAtZero: true,
            scaleOverride: true,
            scaleSteps: 5,
            scaleStepWidth: 20,
            scaleStartValue: 0,
            scaleShowGridLines: true,
            scaleGridLineColor: '#f0f2f7',
            scaleLineColor: '#e8eaf0',
            scaleFontColor: '#6b7385',
            scaleFontFamily: "'Nunito', 'Segoe UI', sans-serif",
            scaleFontSize: 11,
            datasetFill: true,
            pointDotRadius: 3,
            tooltipTemplate: '<%= label %>: <%= value %>%'
        });
    }

    function renderPaceTable(data) {
        var $body = $('#rms-pace-table-body');
        $body.empty();

        if (!data || !data.rows) {
            return;
        }

        var symbol = data.currencySymbol || config.currencySymbol || '$';
        $.each(data.rows, function (_, row) {
            $body.append(
                '<tr>' +
                '<td>' + row.date + '</td>' +
                '<td>' + row.occupied + '</td>' +
                '<td>' + row.prior_occupied + '</td>' +
                '<td>' + row.occupancy_pct + '%</td>' +
                '<td>' + row.prior_occupancy_pct + '%</td>' +
                '<td class="' + paceDeltaClass(row.pace_delta) + '">' + (row.pace_delta > 0 ? '+' : '') + row.pace_delta + '</td>' +
                '<td>' + row.pickup_room_nights + '</td>' +
                '<td>' + formatMoney(row.on_books_revenue) + '</td>' +
                '<td>' + formatMoney(row.prior_on_books_revenue) + '</td>' +
                '</tr>'
            );
        });
    }

    function applyPace(data) {
        window.rmsPaceData = data;
        renderPaceStats(data);
        renderPaceChart(data);
        renderPaceTable(data);
    }

    function loadPace(days) {
        if (!config || !config.paceUrl) {
            return;
        }

        days = days || config.forecastDays || 28;
        $('#rms-pace-chart').addClass('rms-loading');

        $.get(config.paceUrl, { days: days }).done(function (response) {
            if (response && response.success && response.data) {
                applyPace(response.data);
                paceLoaded = true;
            }
        }).always(function () {
            $('#rms-pace-chart').removeClass('rms-loading');
        });
    }

    function buildPropertyRatesGrid(data) {
        var $container = $('#rms-property-rates-grid');
        if (!data || !data.rows || !data.rows.length) {
            $container.html('<p class="text-muted rms-empty">' + t('propertyRatesEmpty', 'Set default BAR on each room type.') + '</p>');
            $('#rms-property-date-range-label').text('');
            return;
        }

        var dates = data.dates || [];
        var html = '<div class="table-responsive rms-property-table-wrap"><table class="table table-bordered table-condensed rms-calendar-table rms-property-table"><thead><tr><th class="rms-property-sticky">' + (typeof l === 'function' ? l('Room Type', true) : 'Room Type') + '</th>';

        $.each(dates, function (_, date) {
            html += '<th class="rms-day-col"><div class="rms-day-date">' + date + '</div></th>';
        });
        html += '</tr></thead><tbody>';

        $.each(data.rows, function (_, row) {
            html += '<tr><td class="rms-row-label rms-property-sticky">' + $('<div/>').text(row.room_type_name).html() + '</td>';
            $.each(dates, function (__, date) {
                var rate = row.rates_by_date && row.rates_by_date[date] ? row.rates_by_date[date] : null;
                var val = rate && rate.adult_1_rate !== null && rate.adult_1_rate !== '' ? rate.adult_1_rate : null;
                html += '<td class="rms-rate-cell">' + editableCell(date, 'adult_1_rate', val, 'rates', row.rate_plan_id) + '</td>';
            });
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        $container.html(html);

        if (dates.length) {
            $('#rms-property-date-range-label').text(dates[0] + ' – ' + dates[dates.length - 1]);
        }
    }

    function parityStatusClass(status) {
        if (status === 'undercut') {
            return 'rms-parity--undercut';
        }
        if (status === 'mismatch') {
            return 'rms-parity--mismatch';
        }
        if (status === 'aligned') {
            return 'rms-parity--ok';
        }
        return 'rms-parity--unknown';
    }

    function renderChannelStatus(data) {
        var $status = $('#rms-channel-status');
        if (!data) {
            $status.empty();
            return;
        }

        var html = '';
        if (data.connected_channels && data.connected_channels.length) {
            html += '<div class="rms-channel-badges">';
            $.each(data.connected_channels, function (_, channel) {
                html += '<span class="rms-channel-badge"><i class="fa fa-link"></i> '
                    + $('<div/>').text(channel.label).html() + '</span>';
            });
            html += '</div>';
        } else {
            html += '<p class="text-muted">' + t('channelNoConnections', 'No channels connected.') + ' '
                + '<a href="' + (config.extensionsUrl || '#') + '">' + (typeof l === 'function' ? l('Connect extensions', true) : 'Connect extensions') + '</a></p>';
        }

        if (data.summary) {
            html += '<div class="rms-channel-summary">';
            html += '<span>' + (data.summary.issue_days || 0) + ' ' + t('channelParityIssue', 'parity issues') + '</span>';
            html += '<span>' + (data.summary.aligned_days || 0) + ' ' + t('channelParityOk', 'in parity') + '</span>';
            html += '</div>';
        }

        $status.html(html);
    }

    function buildChannelParityGrid(data) {
        var $container = $('#rms-channel-parity-grid');
        if (!data || !data.sections || !data.sections.length) {
            var msg = t('channelNoPlans', 'Set up channel rate plans linked to BAR.');
            $container.html(
                '<p class="text-muted rms-empty">' + msg + ' '
                + '<a href="' + (config.ratePlansUrl || '#') + '">' + (typeof l === 'function' ? l('Rate Plans', true) : 'Rate plans') + '</a></p>'
            );
            $('#rms-channel-date-range-label').text('');
            $('#rms-channel-sync').hide();
            return;
        }

        var html = '';
        $.each(data.sections, function (_, section) {
            html += '<div class="rms-parity-section">';
            html += '<h3 class="rms-parity-section__title">' + $('<div/>').text(section.room_type_name).html() + '</h3>';
            html += '<div class="table-responsive"><table class="table table-bordered table-condensed rms-calendar-table rms-parity-table"><thead><tr>';
            html += '<th>' + (typeof l === 'function' ? l('Date', true) : 'Date') + '</th>';
            html += '<th>BAR</th>';
            $.each(section.channel_plans, function (__, plan) {
                html += '<th>' + $('<div/>').text(plan.channel_label).html() + '</th>';
            });
            html += '<th></th></tr></thead><tbody>';

            $.each(section.rows, function (__, row) {
                html += '<tr class="' + parityStatusClass(row.status) + '">';
                html += '<td>' + row.date + '</td>';
                html += '<td>' + (row.bar_rate !== null ? formatMoney(row.bar_rate) : '—') + '</td>';
                $.each(row.channels, function (___, channel) {
                    var cell = channel.rate !== null ? formatMoney(channel.rate) : '—';
                    if (channel.delta_pct !== null && channel.delta_pct !== 0) {
                        cell += ' <small>(' + (channel.delta_pct > 0 ? '+' : '') + channel.delta_pct + '%)</small>';
                    }
                    html += '<td class="' + parityStatusClass(channel.status) + '">' + cell + '</td>';
                });
                html += '<td class="rms-parity-status">' + (row.status === 'aligned'
                    ? t('channelParityOk', 'OK')
                    : t('channelParityIssue', 'Issue')) + '</td>';
                html += '</tr>';
            });

            html += '</tbody></table></div></div>';
        });

        $container.html(html);

        if (data.start_date && data.end_date) {
            $('#rms-channel-date-range-label').text(data.start_date + ' – ' + data.end_date);
        }

        if (data.sections[0] && data.sections[0].bar_rate_plan_id) {
            $('#rms-channel-sync').show().data('rate-plan-id', data.sections[0].bar_rate_plan_id);
        }
    }

    function loadChannelParity() {
        if (!config || !config.channelParityUrl) {
            return;
        }

        $('#rms-channel-parity-grid').addClass('rms-loading');

        $.get(config.channelParityUrl, {
            start_date: channelParityStartDate,
            days: channelParityDayCount
        }).done(function (response) {
            if (response && response.success && response.data) {
                channelParityData = response.data;
                renderChannelStatus(channelParityData);
                buildChannelParityGrid(channelParityData);
            }
        }).fail(function () {
            $('#rms-channel-parity-grid').html('<p class="text-danger">Failed to load channel parity.</p>');
        }).always(function () {
            $('#rms-channel-parity-grid').removeClass('rms-loading');
        });
    }

    function syncChannelRates() {
        if (!config || !config.channelSyncUrl || !channelParityData) {
            return;
        }

        var ratePlanId = $('#rms-channel-sync').data('rate-plan-id');
        if (!ratePlanId) {
            return;
        }

        if (!window.confirm(t('channelSyncConfirm', 'Push rates to channels?'))) {
            return;
        }

        var $btn = $('#rms-channel-sync');
        $btn.prop('disabled', true);

        $.post(config.channelSyncUrl, {
            start_date: channelParityData.start_date,
            end_date: channelParityData.end_date,
            rate_plan_id: ratePlanId
        }).done(function (response) {
            if (response && response.success) {
                alert(response.message || t('channelSyncStarted', 'Sync started.'));
            } else {
                alert(response && response.message ? response.message : t('channelSyncFailed', 'Sync failed.'));
            }
        }).fail(function () {
            alert(t('channelSyncFailed', 'Sync failed.'));
        }).always(function () {
            $btn.prop('disabled', false);
        });
    }

    function renderDemandSignals(data) {
        demandData = data;
        if (!data) {
            return;
        }

        if (data.external && data.external.message) {
            $('#rms-external-notice span').text(data.external.message);
        }

        var $signals = $('#rms-demand-signals');
        $signals.empty();
        if (data.signals && data.signals.length) {
            $.each(data.signals, function (_, signal) {
                var cls = 'rms-demand-card--neutral';
                if (signal.type === 'positive') {
                    cls = 'rms-demand-card--positive';
                } else if (signal.type === 'warning') {
                    cls = 'rms-demand-card--warning';
                }
                $signals.append(
                    '<div class="rms-demand-card ' + cls + '">'
                    + '<span class="rms-demand-card__metric">' + $('<div/>').text(signal.metric || '').html() + '</span>'
                    + '<strong>' + $('<div/>').text(signal.title || '').html() + '</strong>'
                    + '<span>' + $('<div/>').text(signal.detail || '').html() + '</span>'
                    + '</div>'
                );
            });
        }

        var $mix = $('#rms-source-mix-chart');
        $mix.empty();
        if (data.source_mix && data.source_mix.length) {
            $.each(data.source_mix, function (_, row) {
                $mix.append(
                    '<div class="rms-source-row">'
                    + '<span class="rms-source-label">' + $('<div/>').text(row.source).html() + '</span>'
                    + '<div class="rms-source-bar-wrap"><span class="rms-source-bar" style="width:' + row.pct + '%;"></span></div>'
                    + '<span class="rms-source-pct">' + row.pct + '%</span>'
                    + '</div>'
                );
            });
        } else {
            $mix.html('<p class="text-muted rms-empty">' + t('demandNoSources', 'No sources yet.') + '</p>');
        }

        var $dow = $('#rms-dow-chart');
        $dow.empty();
        if (data.day_of_week && data.day_of_week.length) {
            $.each(data.day_of_week, function (_, row) {
                $dow.append(
                    '<div class="rms-dow-col">'
                    + '<span class="rms-dow-bar" style="height:' + Math.min(100, row.avg_occupancy) + '%;"></span>'
                    + '<span class="rms-dow-label">' + row.day + '</span>'
                    + '<span class="rms-dow-value">' + row.avg_occupancy + '%</span>'
                    + '</div>'
                );
            });
        }
    }

    function loadDemandSignals(days) {
        if (!config || !config.demandSignalsUrl) {
            return;
        }

        days = days || config.forecastDays || 28;
        $('#rms-demand-signals, #rms-source-mix-chart, #rms-dow-chart').addClass('rms-loading');

        $.get(config.demandSignalsUrl, { days: days }).done(function (response) {
            if (response && response.success && response.data) {
                renderDemandSignals(response.data);
                demandLoaded = true;
            }
        }).always(function () {
            $('#rms-demand-signals, #rms-source-mix-chart, #rms-dow-chart').removeClass('rms-loading');
        });
    }

    function loadPropertyRates() {
        if (!config || !config.propertyRatesUrl) {
            return;
        }

        $('#rms-property-rates-grid').addClass('rms-loading');

        $.get(config.propertyRatesUrl, {
            start_date: propertyRatesStartDate,
            days: propertyDayCount
        }).done(function (response) {
            if (response && response.success && response.data) {
                buildPropertyRatesGrid(response.data);
            }
        }).fail(function () {
            $('#rms-property-rates-grid').html('<p class="text-danger">Failed to load property rates.</p>');
        }).always(function () {
            $('#rms-property-rates-grid').removeClass('rms-loading');
        });
    }

    function previewPricingRules() {
        if (!config || !config.previewRulesUrl) {
            return;
        }

        var days = config.forecastDays || 28;
        $.get(config.previewRulesUrl, { days: days }).done(function (response) {
            if (!response || !response.success) {
                return;
            }

            var $panel = $('#rms-rules-panel');
            var $list = $('#rms-rules-list');
            $list.empty();

            if (!response.actions || !response.actions.length) {
                $list.append('<li class="text-muted">' + t('rulesNone', 'No rule actions matched.') + '</li>');
            } else {
                $.each(response.actions, function (_, action) {
                    $list.append(
                        '<li><strong>' + $('<div/>').text(action.date).html() + '</strong> '
                        + $('<div/>').text(action.reason || action.action).html() + '</li>'
                    );
                });
            }

            $panel.show();
        });
    }

    function runPricingRules() {
        if (!config || !config.runRulesUrl) {
            return;
        }

        if (!window.confirm(t('rulesConfirm', 'Apply pricing rules to matching dates?'))) {
            return;
        }

        var days = config.forecastDays || 28;
        var $btn = $('#rms-rules-run');
        $btn.prop('disabled', true);

        $.post(config.runRulesUrl, { days: days }).done(function (response) {
            if (response && response.message) {
                alert(response.message);
            }
            if (response && response.success) {
                loadPace(days);
                loadPropertyRates();
                loadRates();
                previewPricingRules();
            }
        }).always(function () {
            $btn.prop('disabled', false);
        });
    }

    function setHorizonDays(days) {
        config.forecastDays = days;
        $('.rms-forecast-range-btn, .rms-pace-range-btn').removeClass('btn-primary active').addClass('btn-default');
        $('.rms-forecast-range-btn[data-days="' + days + '"], .rms-pace-range-btn[data-days="' + days + '"]')
            .removeClass('btn-default').addClass('btn-primary active');
        loadForecast(days);
        loadPace(days);
        loadDemandSignals(days);
        aiLoaded = false;
        loadAiInsights(true);
    }

    function bindEvents() {
        $('.rms-sync-rate-plan').on('change', function () {
            var val = $(this).val();
            $('.rms-sync-rate-plan').val(val);
            if (config && val) {
                $('#rms-edit-rates-link').attr('href', config.editRatesBaseUrl + val);
            }
            loadRates();
        });

        $('.rms-sync-prev-week').on('click', function () {
            currentStartDate = addDays(currentStartDate, -7);
            loadRates();
        });

        $('.rms-sync-next-week').on('click', function () {
            currentStartDate = addDays(currentStartDate, 7);
            loadRates();
        });

        $('.rms-sync-today').on('click', function () {
            currentStartDate = config.sellingDate;
            loadRates();
        });

        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            var target = $(e.target).attr('href');
            if (target === '#rms-restrictions-tab' && ratesCache) {
                buildRestrictionsGrid($('#rms-restrictions-grid'), ratesCache);
            }
            if (target === '#rms-forecast-tab') {
                loadAiInsights(false);
                if (forecastData) {
                    renderForecastChart(forecastData);
                }
            }
            if (target === '#rms-pace-tab') {
                if (!paceLoaded) {
                    loadPace(config.forecastDays || 28);
                } else {
                    renderPaceChart(window.rmsPaceData || null);
                }
            }
            if (target === '#rms-property-rates-tab') {
                loadPropertyRates();
            }
            if (target === '#rms-channels-tab') {
                loadChannelParity();
            }
            if (target === '#rms-demand-tab') {
                if (!demandLoaded) {
                    loadDemandSignals(config.forecastDays || 28);
                }
            }
        });

        $('.rms-demand-range-btn').on('click', function () {
            var days = parseInt($(this).data('days'), 10);
            if (!days) {
                return;
            }
            config.forecastDays = days;
            $('.rms-demand-range-btn').removeClass('btn-primary active').addClass('btn-default');
            $(this).removeClass('btn-default').addClass('btn-primary active');
            loadDemandSignals(days);
        });

        $('#rms-channel-prev-week').on('click', function () {
            channelParityStartDate = addDays(channelParityStartDate, -7);
            loadChannelParity();
        });

        $('#rms-channel-next-week').on('click', function () {
            channelParityStartDate = addDays(channelParityStartDate, 7);
            loadChannelParity();
        });

        $('#rms-channel-today').on('click', function () {
            channelParityStartDate = config.sellingDate;
            loadChannelParity();
        });

        $('#rms-channel-sync').on('click', syncChannelRates);

        $('.rms-forecast-range-btn, .rms-pace-range-btn').on('click', function () {
            var days = parseInt($(this).data('days'), 10);
            if (!days) {
                return;
            }
            setHorizonDays(days);
        });

        $('#rms-property-prev-week').on('click', function () {
            propertyRatesStartDate = addDays(propertyRatesStartDate, -7);
            loadPropertyRates();
        });

        $('#rms-property-next-week').on('click', function () {
            propertyRatesStartDate = addDays(propertyRatesStartDate, 7);
            loadPropertyRates();
        });

        $('#rms-property-today').on('click', function () {
            propertyRatesStartDate = config.sellingDate;
            loadPropertyRates();
        });

        $('#rms-rules-preview').on('click', previewPricingRules);
        $('#rms-rules-run').on('click', runPricingRules);

        $('#rms-ai-refresh').on('click', function () {
            aiLoaded = false;
            loadAiInsights(true);
        });

        $(document).on('click', '.rms-editable-cell', function () {
            beginInlineEdit($(this));
        });

        $(document).on('click', '.rms-restriction-toggle', function () {
            var $btn = $(this);
            postRateUpdate({
                rate_plan_id: activeRatePlanSelect().val(),
                date: $btn.data('date'),
                field: $btn.data('field'),
                value: $btn.data('value')
            }, $btn);
        });

        $(document).on('click', '.rms-min-stay-cell', function () {
            cycleMinStay($(this));
        });

        $(document).on('click', '.rms-apply-suggestion', function () {
            var $btn = $(this);
            if (!$btn.data('original-label')) {
                $btn.data('original-label', $btn.text());
            }
            applySuggestion($btn);
        });
    }

    $(function () {
        if (!$('.rms-page').length) {
            return;
        }

        config = parseJsonNode('rms-config');
        forecastData = parseJsonNode('rms-forecast-data');
        demandData = parseJsonNode('rms-demand-data');

        if (!config) {
            return;
        }

        currentStartDate = config.sellingDate;
        propertyRatesStartDate = config.sellingDate;
        channelParityStartDate = config.sellingDate;

        if (forecastData) {
            applyForecast(forecastData);
        }

        if (demandData) {
            renderDemandSignals(demandData);
            demandLoaded = true;
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
