/* Owner Dashboard – Chart.js 1.x (matches public/js/Chart.min.js v1.0.2) */
(function ($) {
    'use strict';

    var palette = [
        '#3f6ad8', '#43e97b', '#f7971e', '#fa709a', '#a18cd1',
        '#4facfe', '#f5a623', '#00c6fb', '#e96c75', '#38f9d7'
    ];

    function parseChartData() {
        if (window.OWD_CHART_DATA) {
            return window.OWD_CHART_DATA;
        }
        var node = document.getElementById('owd-chart-data');
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

    function initCharts(data) {
        if (typeof Chart === 'undefined' || !data) {
            return;
        }

        var currency = data.currency || '$';

        /* ── 1. Monthly revenue (bar) ── */
        var revCanvas = document.getElementById('owd-chart-revenue');
        if (revCanvas && data.revLabels && data.revLabels.length) {
            new Chart(revCanvas.getContext('2d')).Bar({
                labels: data.revLabels,
                datasets: [{
                    label: 'Revenue',
                    fillColor: 'rgba(63, 106, 216, 0.35)',
                    strokeColor: '#3f6ad8',
                    highlightFill: 'rgba(63, 106, 216, 0.55)',
                    highlightStroke: '#2d4fba',
                    data: data.revValues
                }]
            }, $.extend(true, {}, sharedScaleOptions(), {
                barShowStroke: true,
                barStrokeWidth: 2,
                tooltipTemplate: '<%= label %>: ' + currency + '<%= parseFloat(value).toLocaleString() %>'
            }));
        }

        /* ── 2. Booking sources (doughnut) ── */
        var srcCanvas = document.getElementById('owd-chart-sources');
        if (srcCanvas && data.sourceLabels && data.sourceLabels.length) {
            var segments = [];
            for (var i = 0; i < data.sourceLabels.length; i++) {
                segments.push({
                    value: data.sourceValues[i],
                    label: data.sourceLabels[i],
                    color: palette[i % palette.length],
                    highlight: palette[i % palette.length]
                });
            }
            var doughnutChart = new Chart(srcCanvas.getContext('2d')).Doughnut(segments, {
                responsive: true,
                maintainAspectRatio: false,
                percentageInnerCutout: 62,
                segmentShowStroke: true,
                segmentStrokeColor: '#fff',
                segmentStrokeWidth: 2,
                tooltipTemplate: '<%if (label){%><%= label %><%}%>: <%= value %> booking<%if (value != 1){%>s<%}%>',
                legendTemplate: '<ul class="<%=name.toLowerCase()%>-legend"><% for (var i=0; i<segments.length; i++){%><li><span style="background-color:<%=segments[i].fillColor%>"></span><%if(segments[i].label){%><%=segments[i].label%><%}%> (<%=segments[i].value%>)</li><%}%></ul>'
            });

            var legend = doughnutChart.generateLegend();
            if (legend) {
                $(srcCanvas).parent().append(legend);
            }
        }

        /* ── 3. Monthly occupancy (line) ── */
        var occCanvas = document.getElementById('owd-chart-occupancy');
        if (occCanvas && data.occLabels && data.occLabels.length) {
            new Chart(occCanvas.getContext('2d')).Line({
                labels: data.occLabels,
                datasets: [{
                    label: 'Occupancy %',
                    fillColor: 'rgba(67, 233, 123, 0.12)',
                    strokeColor: '#43e97b',
                    pointColor: '#43e97b',
                    pointStrokeColor: '#fff',
                    pointHighlightFill: '#43e97b',
                    pointHighlightStroke: '#43e97b',
                    data: data.occValues
                }]
            }, $.extend(true, {}, sharedScaleOptions(), {
                datasetFill: true,
                bezierCurve: true,
                bezierCurveTension: 0.35,
                pointDot: true,
                pointDotRadius: 3,
                scaleOverride: true,
                scaleSteps: 5,
                scaleStepWidth: 20,
                scaleStartValue: 0
            }));
        }

        /* ── 4. Forward occupancy (line) ── */
        var fwdCanvas = document.getElementById('owd-chart-forward');
        if (fwdCanvas && data.fwdLabels && data.fwdLabels.length) {
            new Chart(fwdCanvas.getContext('2d')).Line({
                labels: data.fwdLabels,
                datasets: [{
                    label: 'Occupancy %',
                    fillColor: 'rgba(161, 140, 209, 0.15)',
                    strokeColor: '#a18cd1',
                    pointColor: '#a18cd1',
                    pointStrokeColor: '#fff',
                    pointHighlightFill: '#a18cd1',
                    pointHighlightStroke: '#7c4dff',
                    data: data.fwdValues
                }]
            }, $.extend(true, {}, sharedScaleOptions(), {
                datasetFill: true,
                bezierCurve: true,
                bezierCurveTension: 0.35,
                pointDot: true,
                pointDotRadius: 2,
                scaleOverride: true,
                scaleSteps: 5,
                scaleStepWidth: 20,
                scaleStartValue: 0
            }));
        }

        /* ── 5. Room revenue (bar; Chart.js 1 has no horizontalBar) ── */
        var roomsCanvas = document.getElementById('owd-chart-rooms');
        if (roomsCanvas && data.roomLabels && data.roomLabels.length) {
            new Chart(roomsCanvas.getContext('2d')).Bar({
                labels: data.roomLabels,
                datasets: [{
                    label: 'Revenue',
                    fillColor: 'rgba(63, 106, 216, 0.35)',
                    strokeColor: '#3f6ad8',
                    highlightFill: 'rgba(63, 106, 216, 0.55)',
                    highlightStroke: '#2d4fba',
                    data: data.roomRevenue
                }]
            }, $.extend(true, {}, sharedScaleOptions(), {
                barShowStroke: true,
                barStrokeWidth: 2,
                tooltipTemplate: '<%= label %>: ' + currency + '<%= parseFloat(value).toLocaleString() %>'
            }));
        }
    }

    $(function () {
        if (!$('.owd-page').length) {
            return;
        }

        if (typeof Chart === 'undefined') {
            $('.owd-chart-card__body').addClass('owd-chart-card__body--error');
            return;
        }

        var data = parseChartData();
        initCharts(data);
    });

}(jQuery));
