/* Platform admin growth analytics – Chart.js 1.x (public/js/Chart.min.js v1.0.2) */
(function ($) {
    'use strict';

    function parsePayload() {
        var node = document.getElementById('pa-growth-analytics-data');
        if (!node || !node.textContent) {
            return null;
        }
        try {
            return JSON.parse(node.textContent);
        } catch (e) {
            return null;
        }
    }

    function sharedScaleOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            scaleBeginAtZero: true,
            scaleShowGridLines: true,
            scaleGridLineColor: '#f0f2f7',
            scaleLineColor: '#e8eaf0',
            scaleFontColor: '#6b7385',
            scaleFontFamily: "'Nunito', 'Segoe UI', sans-serif",
            scaleFontSize: 11
        };
    }

    function scaleOptionsForMax(maxValue) {
        var opts = sharedScaleOptions();
        maxValue = parseInt(maxValue, 10) || 0;
        if (maxValue <= 10) {
            opts.scaleOverride = true;
            opts.scaleSteps = Math.max(maxValue, 1);
            opts.scaleStepWidth = 1;
            opts.scaleStartValue = 0;
        }
        return opts;
    }

    function funnelLegendTemplate() {
        return '<ul class="pa-chart-legend-list">'
            + '<% for (var i = 0; i < datasets.length; i++) { %>'
            + '<li><span style="background-color:<%= datasets[i].fillColor %>"></span>'
            + '<% if (datasets[i].label) { %><%= datasets[i].label %><% } %>'
            + '</li>'
            + '<% } %>'
            + '</ul>';
    }

    function initFunnelChart(funnel) {
        var funnelCanvas = document.getElementById('pa-funnel-chart');
        if (!funnelCanvas || !funnel.labels || !funnel.labels.length) {
            return;
        }

        var datasets = [
            {
                label: 'Signups',
                fillColor: 'rgba(79, 70, 229, 0.85)',
                strokeColor: '#4f46e5',
                highlightFill: 'rgba(79, 70, 229, 1)',
                highlightStroke: '#4338ca',
                data: funnel.signups || []
            },
            {
                label: 'Setup wizard',
                fillColor: 'rgba(14, 165, 233, 0.85)',
                strokeColor: '#0ea5e9',
                highlightFill: 'rgba(14, 165, 233, 1)',
                highlightStroke: '#0284c7',
                data: funnel.setupWizard || []
            },
            {
                label: 'Tutorial completed',
                fillColor: 'rgba(5, 150, 105, 0.85)',
                strokeColor: '#059669',
                highlightFill: 'rgba(5, 150, 105, 1)',
                highlightStroke: '#047857',
                data: funnel.tutorial || []
            },
            {
                label: 'First booking',
                fillColor: 'rgba(217, 119, 6, 0.85)',
                strokeColor: '#d97706',
                highlightFill: 'rgba(217, 119, 6, 1)',
                highlightStroke: '#b45309',
                data: funnel.firstBooking || []
            }
        ];

        var funnelChart = new Chart(funnelCanvas.getContext('2d')).Bar({
            labels: funnel.labels,
            datasets: datasets
        }, $.extend(true, {}, scaleOptionsForMax(funnel.maxValue), {
            barShowStroke: true,
            barStrokeWidth: 1,
            barDatasetSpacing: 2,
            barValueSpacing: 6,
            multiTooltipTemplate: '<%if (datasetLabel){%><%= datasetLabel %>: <%}%><%= value %>',
            legendTemplate: funnelLegendTemplate()
        }));

        var $legend = $('#pa-funnel-legend');
        if ($legend.length && funnelChart && typeof funnelChart.generateLegend === 'function') {
            var legendHtml = funnelChart.generateLegend();
            if (legendHtml) {
                $legend.html(legendHtml).attr('aria-hidden', 'false');
            }
        }
    }

    function initCharts(payload) {
        if (typeof Chart === 'undefined' || !payload) {
            $('.pa-chart-wrap').addClass('pa-chart-wrap--error');
            return;
        }

        initFunnelChart(payload.funnel || {});

        var subs = payload.subscriptionsByMonth || {};
        var subsCanvas = document.getElementById('pa-subscriptions-chart');
        if (subsCanvas && subs.labels && subs.labels.length) {
            new Chart(subsCanvas.getContext('2d')).Bar({
                labels: subs.labels,
                datasets: [{
                    label: 'New properties',
                    fillColor: 'rgba(79, 70, 229, 0.75)',
                    strokeColor: '#4f46e5',
                    highlightFill: 'rgba(79, 70, 229, 0.9)',
                    highlightStroke: '#4338ca',
                    data: subs.totals || []
                }]
            }, $.extend(true, {}, sharedScaleOptions(), {
                barShowStroke: true,
                barStrokeWidth: 1,
                tooltipTemplate: '<%= label %>: <%= value %>'
            }));
        }

        var charges = payload.chargesByMonth || {};
        var chargesCanvas = document.getElementById('pa-charges-chart');
        var currency = payload.currencySymbol || '';
        if (chargesCanvas && charges.labels && charges.labels.length) {
            new Chart(chargesCanvas.getContext('2d')).Bar({
                labels: charges.labels,
                datasets: [{
                    label: 'Total charges',
                    fillColor: 'rgba(5, 150, 105, 0.75)',
                    strokeColor: '#059669',
                    highlightFill: 'rgba(5, 150, 105, 0.9)',
                    highlightStroke: '#047857',
                    data: charges.totals || []
                }]
            }, $.extend(true, {}, sharedScaleOptions(), {
                barShowStroke: true,
                barStrokeWidth: 1,
                tooltipTemplate: '<%= label %>: ' + currency + '<%= parseFloat(value).toLocaleString() %>'
            }));
        }
    }

    $(function () {
        if (!$('#pa-funnel-chart').length && !$('#pa-subscriptions-chart').length) {
            return;
        }

        if (typeof Chart === 'undefined') {
            $('.pa-chart-wrap').addClass('pa-chart-wrap--error');
            return;
        }

        initCharts(parsePayload());
    });
}(jQuery));
