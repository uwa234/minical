(function ($) {
    'use strict';

    var chartInstances = {
        revenue: null,
        occupancy: null,
        arrivals: null
    };

    function parseAnalyticsConfig() {
        var node = document.getElementById('dashboard-analytics-data');
        if (!node || !node.textContent) {
            return null;
        }

        try {
            return JSON.parse(node.textContent);
        } catch (e) {
            return null;
        }
    }

    function destroyCharts() {
        $.each(chartInstances, function (key, chart) {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
            chartInstances[key] = null;
        });
    }

    function sharedOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                display: false
            },
            tooltips: {
                mode: 'index',
                intersect: false
            },
            scales: {
                xAxes: [{
                    gridLines: {
                        display: false
                    },
                    ticks: {
                        maxRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: 10
                    }
                }],
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        };
    }

    function initCharts(data) {
        if (typeof Chart === 'undefined' || !data || !data.labels || !data.labels.length) {
            return;
        }

        destroyCharts();

        var i18n = data.i18n || {};

        var revenueCanvas = document.getElementById('dashboard-revenue-chart');
        if (revenueCanvas) {
            chartInstances.revenue = new Chart(revenueCanvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: i18n.revenueAxis || 'Revenue',
                        data: data.revenue,
                        borderColor: '#3f6ad8',
                        backgroundColor: 'rgba(63, 106, 216, 0.15)',
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: '#3f6ad8',
                        fill: true,
                        lineTension: 0.3
                    }]
                },
                options: $.extend(true, {}, sharedOptions(), {
                    scales: {
                        xAxes: sharedOptions().scales.xAxes,
                        yAxes: [{
                            ticks: {
                                beginAtZero: true
                            }
                        }]
                    }
                })
            });
        }

        var occupancyCanvas = document.getElementById('dashboard-occupancy-chart');
        if (occupancyCanvas) {
            chartInstances.occupancy = new Chart(occupancyCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: i18n.occupancyAxis || 'Occupancy',
                        data: data.occupancy,
                        backgroundColor: 'rgba(99, 102, 241, 0.8)',
                        borderColor: '#6366f1',
                        borderWidth: 1
                    }]
                },
                options: $.extend(true, {}, sharedOptions(), {
                    scales: {
                        xAxes: sharedOptions().scales.xAxes,
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                max: 100,
                                callback: function (value) {
                                    return value + '%';
                                }
                            }
                        }]
                    },
                    tooltips: {
                        callbacks: {
                            label: function (tooltipItem) {
                                return tooltipItem.yLabel + '%';
                            }
                        }
                    }
                })
            });
        }

        var arrivalsCanvas = document.getElementById('dashboard-arrivals-chart');
        if (arrivalsCanvas) {
            chartInstances.arrivals = new Chart(arrivalsCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: i18n.arrivalsAxis || 'Arrivals',
                        data: data.arrivals,
                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
                        borderColor: '#10b981',
                        borderWidth: 1
                    }]
                },
                options: sharedOptions()
            });
        }
    }

    function formatMoney(amount, symbol) {
        var value = parseFloat(amount);
        if (isNaN(value)) {
            value = 0;
        }
        return (symbol || '') + value.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function updateSummary(data) {
        var summary = data.summary || {};
        var symbol = data.currencySymbol || '';

        $('#dashboard-stat-revenue').text(formatMoney(summary.periodRevenue, symbol));
        $('#dashboard-stat-arrivals').text(parseInt(summary.periodArrivals, 10) || 0);
        $('#dashboard-stat-occupancy').text((parseInt(summary.avgOccupancy, 10) || 0) + '%');
    }

    function updatePeriodLabels(data) {
        if (data.periodLabel) {
            $('#dashboard-analytics-period-label').text(data.periodLabel);
        }

        if (data.dateRangeLabel) {
            $('#dashboard-analytics-date-range').text(' · ' + data.dateRangeLabel);
        }
    }

    function setActiveRangeButton(days) {
        $('.dashboard-range-btn')
            .removeClass('btn-primary active')
            .addClass('btn-default');

        $('.dashboard-range-btn[data-days="' + days + '"]')
            .removeClass('btn-default')
            .addClass('btn-primary active');
    }

    function setChartsLoading(isLoading) {
        $('#dashboard-charts').toggleClass('dashboard-charts--loading', isLoading);
    }

    function loadAnalytics(days, config) {
        if (!config || !config.analyticsUrl) {
            return;
        }

        setChartsLoading(true);
        setActiveRangeButton(days);

        $.getJSON(config.analyticsUrl, { days: days })
            .done(function (response) {
                if (!response || !response.success || !response.data) {
                    return;
                }

                var payload = response.data;
                initCharts(payload);
                updateSummary(payload);
                updatePeriodLabels(payload);
                config.selectedDays = days;

                if (window.history && window.history.replaceState) {
                    var url = new URL(window.location.href);
                    url.searchParams.set('days', days);
                    window.history.replaceState({}, '', url.toString());
                }
            })
            .always(function () {
                setChartsLoading(false);
            });
    }

    function parseTrialConfig() {
        var node = document.getElementById('dashboard-trial-data');
        if (!node || !node.textContent) {
            return null;
        }

        try {
            return JSON.parse(node.textContent);
        } catch (e) {
            return null;
        }
    }

    function padCountdown(n) {
        return n < 10 ? '0' + n : String(n);
    }

    function initTrialCountdown() {
        var trialConfig = parseTrialConfig();
        if (!trialConfig || !trialConfig.expiresAt) {
            return;
        }

        var $days = $('#dashboard-trial-days');
        var $hours = $('#dashboard-trial-hours');
        var $minutes = $('#dashboard-trial-minutes');
        var $wrap = $('#dashboard-trial-countdown');

        if (!$days.length) {
            return;
        }

        function tick() {
            var now = Math.floor(Date.now() / 1000);
            var remaining = trialConfig.expiresAt - now;

            if (remaining <= 0) {
                $wrap.addClass('dashboard-trial-countdown--expired');
                $days.text(trialConfig.expiredLabel || '0');
                $hours.text('00');
                $minutes.text('00');
                return;
            }

            var days = Math.floor(remaining / 86400);
            remaining -= days * 86400;
            var hours = Math.floor(remaining / 3600);
            remaining -= hours * 3600;
            var minutes = Math.floor(remaining / 60);

            $days.text(padCountdown(days));
            $hours.text(padCountdown(hours));
            $minutes.text(padCountdown(minutes));
        }

        tick();
        window.setInterval(tick, 1000);
    }

    $(function () {
        initTrialCountdown();
    });

    $(function () {
        var config = parseAnalyticsConfig();

        if (!$('#dashboard-analytics').length) {
            return;
        }

        if (typeof Chart === 'undefined') {
            $('#dashboard-charts').addClass('dashboard-charts--error');
            return;
        }

        if (config) {
            initCharts(config);
        }

        $('.dashboard-range-btn').on('click', function () {
            var days = parseInt($(this).data('days'), 10);
            if (!days || !config) {
                return;
            }

            if (config.selectedDays === days) {
                return;
            }

            loadAnalytics(days, config);
        });

        $('.dashboard-booking-row').on('click', function () {
            var bookingId = $(this).data('booking-id');

            if (!bookingId) {
                return;
            }

            if (typeof $.fn.openBookingModal === 'function') {
                $.fn.openBookingModal({
                    bookingID: bookingId
                });
                return;
            }

            window.location.href = (typeof getBaseURL === 'function' ? getBaseURL() : '/') + 'booking/show_bookings?search_query=' + bookingId;
        });
    });
}(jQuery));
