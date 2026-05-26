(function ($) {
    'use strict';

    var ledgerChartInstance = null;
    var ledgerChartPayload = null;

    function scheduleLedgerChartDraw() {
        window.setTimeout(function () {
            if (!ledgerChartPayload) {
                return;
            }
            innGrid.drawLedgerChart(
                ledgerChartPayload.labels,
                ledgerChartPayload.charges,
                ledgerChartPayload.payments
            );
        }, 100);
    }

    function getCurrencySymbol() {
        var page = document.querySelector('.ledger-summary-page');
        if (page && page.getAttribute('data-currency')) {
            return page.getAttribute('data-currency');
        }
        return '$';
    }

    function formatMoney(value) {
        var num = parseFloat(value);
        if (isNaN(num)) {
            return getCurrencySymbol() + '0.00';
        }
        return getCurrencySymbol() + innGrid.addCommas(num.toFixed(2));
    }

    function flowWidths(chargeTotal, paymentTotal) {
        var charges = Math.max(parseFloat(chargeTotal) || 0, 0);
        var payments = Math.max(parseFloat(paymentTotal) || 0, 0);
        var max = Math.max(charges, payments, 1);
        return {
            charges: (charges / max) * 100,
            payments: (payments / max) * 100
        };
    }

    function sharedChartOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                display: false
            },
            tooltips: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    label: function (tooltipItem, data) {
                        var dataset = data.datasets[tooltipItem.datasetIndex];
                        var value = dataset.data[tooltipItem.index];
                        return dataset.label + ': ' + getCurrencySymbol() + parseFloat(value).toFixed(2);
                    }
                }
            },
            scales: {
                xAxes: [{
                    gridLines: { display: false },
                    ticks: {
                        maxRotation: 45,
                        autoSkip: true,
                        maxTicksLimit: 12
                    }
                }],
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        callback: function (value) {
                            return getCurrencySymbol() + value;
                        }
                    }
                }]
            }
        };
    }

    function destroyLedgerChart() {
        if (ledgerChartInstance && typeof ledgerChartInstance.destroy === 'function') {
            ledgerChartInstance.destroy();
        }
        ledgerChartInstance = null;
    }

    innGrid.drawLedgerChart = function (labels, chargeValues, paymentValues) {
        destroyLedgerChart();

        var canvas = document.getElementById('ledger-chart');
        if (!canvas) {
            return;
        }

        if (typeof Chart === 'undefined') {
            console.warn('Ledger chart: Chart.js is not loaded.');
            return;
        }

        if (!labels || !labels.length) {
            return;
        }

        ledgerChartInstance = new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: l('Charges'),
                        data: chargeValues,
                        backgroundColor: 'rgba(99, 102, 241, 0.75)',
                        borderColor: '#6366f1',
                        borderWidth: 1
                    },
                    {
                        label: l('Payment'),
                        data: paymentValues,
                        backgroundColor: 'rgba(16, 185, 129, 0.75)',
                        borderColor: '#10b981',
                        borderWidth: 1
                    }
                ]
            },
            options: sharedChartOptions()
        });
    };

    function updateKpiCards(bookingCountTotal, occupancyRateTotal, revPARTotal, revPARCount, roomChargeTotal, chargeTotal, paymentTotal, balanceTotal) {
        $('#kpi-charges').text(formatMoney(chargeTotal));
        $('#kpi-payments').text(formatMoney(paymentTotal));
        $('#kpi-balance').text(formatMoney(balanceTotal));
        $('#kpi-occupancy').text(parseFloat(occupancyRateTotal).toFixed(1) + '%');
        $('#kpi-bookings').text(bookingCountTotal);
        $('#kpi-adr').text(formatMoney(bookingCountTotal > 0 ? roomChargeTotal / bookingCountTotal : 0));
        $('#kpi-revpar').text(formatMoney(revPARCount > 0 ? revPARTotal / revPARCount : 0));
    }

    function buildPeriodCard(index, value, groupBy, isFuture) {
        var chargeTotal = parseFloat(value.charge_total) || 0;
        var roomChargeTotal = parseFloat(value.room_charge_total) || 0;
        var paymentTotal = parseFloat(value.payment_total) || 0;
        var balance = chargeTotal - paymentTotal;
        var bookingCount = value.booking_count || 0;
        var occupancy = parseFloat(value.occupancy_rate * 100).toFixed(1);
        var adr = (bookingCount > 0 && !isNaN(roomChargeTotal / bookingCount)) ? (roomChargeTotal / bookingCount).toFixed(2) : '0.00';
        var widths = flowWidths(chargeTotal, paymentTotal);

        var dateLabel = index;
        var dateHref = '#';
        if (groupBy === 'daily') {
            dateLabel = index + ' ' + innGrid.getWeekday(index);
            dateHref = getBaseURL() + 'reports/ledger/show_daily_report/' + index;
        }

        var forecastBadge = isFuture
            ? '<span class="ledger-period-card__badge">' + l('Forecast', true) + '</span>'
            : '';

        var dateHtml = groupBy === 'daily'
            ? '<a class="ledger-period-card__date" href="' + dateHref + '">' + dateLabel + '</a>'
            : '<span class="ledger-period-card__date">' + dateLabel + '</span>';

        return $('<article>', {
            'class': 'ledger-period-card' + (isFuture ? ' is-forecast' : ''),
            'title': isFuture ? l('This is a forecast based on existing & upcoming bookings.') : ''
        }).append(
            $('<div>', { 'class': 'ledger-period-card__header' }).append(dateHtml).append(forecastBadge)
        ).append(
            $('<div>', { 'class': 'ledger-period-card__grid' }).append(
                $('<div>', { 'class': 'ledger-period-card__metric' }).append(
                    $('<label>').text(l('bookings')),
                    $('<span>').text(bookingCount + ' (' + occupancy + '%)')
                ),
                $('<div>', { 'class': 'ledger-period-card__metric' }).append(
                    $('<label>').text(l('adr')),
                    $('<span>').text(formatMoney(adr))
                ),
                $('<div>', { 'class': 'ledger-period-card__metric' }).append(
                    $('<label>').text(l('Charges')),
                    $('<span>').text(formatMoney(chargeTotal))
                ),
                $('<div>', { 'class': 'ledger-period-card__metric' }).append(
                    $('<label>').text(l('Payment')),
                    $('<span>').text(formatMoney(paymentTotal))
                )
            )
        ).append(
            $('<div>', { 'class': 'ledger-period-card__flow' }).append(
                $('<div>', { 'class': 'ledger-period-card__flow-charges', css: { width: widths.charges + '%' } }),
                $('<div>', { 'class': 'ledger-period-card__flow-payments', css: { width: widths.payments + '%' } })
            )
        ).append(
            $('<div>', { 'class': 'ledger-period-card__footer' }).append(
                $('<span>').text(l('revpar') + ': ' + formatMoney(value.revPAR)),
                $('<span>', {
                    'class': 'ledger-period-card__balance' + (balance > 0 ? ' is-negative' : ''),
                    text: l('balance') + ': ' + formatMoney(balance)
                })
            )
        );
    }

    function buildFlowCell(chargeTotal, paymentTotal) {
        var widths = flowWidths(chargeTotal, paymentTotal);
        return $('<td>', { 'class': 'text-center flow-cell hidden-print' }).append(
            $('<div>', { 'class': 'ledger-flow-bar' }).append(
                $('<div>', { 'class': 'ledger-flow-bar__charges', css: { width: widths.charges + '%' } }),
                $('<div>', { 'class': 'ledger-flow-bar__payments', css: { width: widths.payments + '%' } })
            )
        );
    }

    innGrid.renderReport = function (dateStart, dateEnd, groupBy, customerTypeId) {
        if (dateStart === '' || dateEnd === '') {
            return;
        }

        $('#ledger-report-loading').show();
        $('#ledger-report-body').hide();

        $.ajax({
            type: 'POST',
            url: getBaseURL() + 'reports/ledger/get_ledger_report_AJAX/',
            data: {
                dateStart: dateStart,
                dateEnd: dateEnd,
                groupBy: groupBy,
                customerTypeId: customerTypeId
            },
            dataType: 'json',
            success: function (data) {
                $('#report-content').empty();
                $('#ledger-period-cards').empty();

                var chartLabels = [];
                var chartCharges = [];
                var chartPayments = [];

                $.each(data, function (index, value) {
                    if ($('#groupBy').val() === 'daily') {
                        var dateLabel = "<a href='" + getBaseURL() + "reports/ledger/show_daily_report/" + index + "'>" + index + ' ' + innGrid.getWeekday(index) + '</a>';
                    } else {
                        dateLabel = index;
                    }

                    var chargeTotal = parseFloat(value.charge_total).toFixed(2);
                    var roomChargeTotal = parseFloat(value.room_charge_total).toFixed(2);
                    var paymentTotal = parseFloat(value.payment_total).toFixed(2);
                    var balance = parseFloat(value.charge_total - value.payment_total).toFixed(2);

                    var isFuture = innGrid.getDate(index) >= innGrid.getDate($('#sellingDate').val());
                    var future = isFuture ? 'bg-warning futureRow' : '';

                    var billableBookingsCol = '';
                    if (innGrid.companyID == 2242) {
                        billableBookingsCol = '<td class="text-center"><span class="booking-count">' + value.charges_booking_count + '</span></td>';
                    }

                    var row = $('<tr>', {
                        'class': 'salesRow ' + future,
                        'data-toggle': isFuture ? 'popover' : undefined,
                        'data-content': isFuture ? l('This is a forecast based on existing & upcoming bookings.') : undefined,
                        'data-trigger': isFuture ? 'hover' : undefined,
                        'data-placement': isFuture ? 'bottom' : undefined
                    }).append(
                        $('<td>', { html: dateLabel }),
                        $('<td>', {
                            'class': 'text-center',
                            html: "<span class='booking-count'>" + value.booking_count + "</span> (<span class='occupancy-rate'>" + parseFloat(value.occupancy_rate * 100).toFixed(1) + '</span>%)'
                        })
                    );

                    if (innGrid.companyID == 2242) {
                        row.append(billableBookingsCol);
                    }

                    row.append(
                        $('<td>', { 'class': 'text-right revPAR', text: value.revPAR.toFixed(2) }),
                        $('<td>', {
                            'class': 'text-right ADR',
                            text: isNaN(roomChargeTotal / value.booking_count) || roomChargeTotal / value.booking_count === Infinity ? '0.00' : (roomChargeTotal / value.booking_count).toFixed(2)
                        }),
                        $('<td>', { 'class': 'text-right room-charge-total', text: roomChargeTotal }),
                        $('<td>', { 'class': 'text-right charge-total', text: chargeTotal }),
                        $('<td>', { 'class': 'text-right payment-total', text: paymentTotal }),
                        $('<td>', { 'class': 'text-right balance', text: balance })
                    );

                    row.append(buildFlowCell(chargeTotal, paymentTotal));

                    $('#report-content').append(row);
                    $('#ledger-period-cards').append(buildPeriodCard(index, value, $('#groupBy').val(), isFuture));

                    chartLabels.push(index);
                    chartCharges.push(parseFloat(chargeTotal));
                    chartPayments.push(parseFloat(paymentTotal));
                });

                $('.futureRow').popover();

                ledgerChartPayload = {
                    labels: chartLabels,
                    charges: chartCharges,
                    payments: chartPayments
                };

                var bookingCountTotal = 0;
                $('.booking-count').each(function () {
                    bookingCountTotal += parseFloat($(this).html().replace(/,/g, ''));
                });
                $('#monthly-booking-count-total').html(bookingCountTotal);

                var occupancyRateTotal = 0;
                var occupancyCount = 0;
                $('.occupancy-rate').each(function () {
                    occupancyRateTotal += parseFloat($(this).html().replace(/,/g, ''));
                    occupancyCount++;
                });
                var avgOccupancy = occupancyCount > 0 ? (occupancyRateTotal / occupancyCount).toFixed(2) : '0.00';
                $('#monthly-occupancy-rate-total').html(avgOccupancy);

                var revPARTotal = 0;
                var revPARCount = 0;
                $('.revPAR').each(function () {
                    revPARTotal += parseFloat($(this).html().replace(/,/g, ''));
                    revPARCount++;
                });
                $('#monthly-revPAR-average').html((revPARCount > 0 ? revPARTotal / revPARCount : 0).toFixed(2));

                var roomChargeTotalSum = 0;
                $('.room-charge-total').each(function () {
                    roomChargeTotalSum += parseFloat($(this).html().replace(/,/g, ''));
                });
                $('#monthly-room-charge-total').html(innGrid.addCommas(roomChargeTotalSum.toFixed(2)));
                $('#monthly-ADR-average').html((bookingCountTotal > 0 ? roomChargeTotalSum / bookingCountTotal : 0).toFixed(2));

                var chargeTotalSum = 0;
                $('.charge-total').each(function () {
                    chargeTotalSum += parseFloat($(this).html().replace(/,/g, ''));
                });
                $('#monthly-charge-total').html(innGrid.addCommas(chargeTotalSum.toFixed(2)));

                var paymentTotalSum = 0;
                $('.payment-total').each(function () {
                    paymentTotalSum += parseFloat($(this).html().replace(/,/g, ''));
                });
                $('#monthly-payment-total').html(innGrid.addCommas(paymentTotalSum.toFixed(2)));

                var balanceTotal = 0;
                $('.balance').each(function () {
                    var rowCharge = parseFloat($(this).parent().find('.charge-total').html().replace(/,/g, '')).toFixed(2);
                    var rowPayment = parseFloat($(this).parent().find('.payment-total').html().replace(/,/g, '')).toFixed(2);
                    var rowBalance = (rowCharge - rowPayment).toFixed(2);
                    balanceTotal += parseFloat(rowBalance);
                    $(this).html(innGrid.addCommas(rowBalance));
                });
                $('#monthly-balance-total').html(innGrid.addCommas(balanceTotal.toFixed(2)));

                updateKpiCards(
                    bookingCountTotal,
                    avgOccupancy,
                    revPARTotal,
                    revPARCount,
                    roomChargeTotalSum,
                    chargeTotalSum,
                    paymentTotalSum,
                    balanceTotal
                );

                $('#ledger-report-loading').hide();
                $('#ledger-report-body').show();
                scheduleLedgerChartDraw();
            },
            error: function () {
                $('#ledger-report-loading').html('<i class="fa fa-exclamation-circle"></i> ' + l('Unable to load report data.', true));
            }
        });
    };

    $("#dateStart").datepicker({
        dateFormat: 'yy-mm-dd',
        onClose: function () {
            $("#dateEnd").datepicker('change', { minDate: new Date($('#dateStart').val()) });
        }
    });

    $("#dateEnd").datepicker({
        dateFormat: 'yy-mm-dd',
        onClose: function () {
            $("#dateStart").datepicker('change', { maxDate: new Date($('#dateEnd').val()) });
        }
    });

    innGrid.getDate = function (dateString) {
        var dateArray = dateString.split('-');
        return new Date(dateArray[0], dateArray[1] - 1, dateArray[2]);
    };

    innGrid.getWeekday = function (dateString) {
        var weekdayNumber = innGrid.getDate(dateString).getDay();
        switch (weekdayNumber) {
            case 0: return 'Sunday';
            case 1: return 'Monday';
            case 2: return 'Tuesday';
            case 3: return 'Wednesday';
            case 4: return 'Thursday';
            case 5: return 'Friday';
            case 6: return 'Saturday';
        }
    };

    $(function () {
        $('.ledger-view-tab').on('click', function () {
            var view = $(this).data('view');
            $('.ledger-view-tab').removeClass('is-active').attr('aria-selected', 'false');
            $(this).addClass('is-active').attr('aria-selected', 'true');

            if (view === 'details') {
                $('#ledger-overview-panel').hide();
                $('#ledger-details-panel').show();
            } else {
                $('#ledger-overview-panel').show();
                $('#ledger-details-panel').hide();
                scheduleLedgerChartDraw();
            }
        });

        $('#printReportButton').click(function () {
            $('#ledger-overview-panel').hide();
            $('#ledger-details-panel').show();
            window.print();
            if ($('.ledger-view-tab[data-view="overview"]').hasClass('is-active')) {
                $('#ledger-overview-panel').show();
                $('#ledger-details-panel').hide();
            }
        });

        $('#generateSalesReport').click(function () {
            var dateStart = $("input[name='date_start']").val();
            var dateEnd = $("input[name='date_end']").val();
            var reportType = $("select[name='report_type']").val();
            var roomType = $("select[name='room_type']").val();
            window.location.href = getBaseURL() + 'reports/show_sales_summary_report/' + dateStart + '/' + dateEnd + '/' + reportType + '/' + roomType;
        });

        $('#generateDepositReport').click(function () {
            var dateStart = $("input[name='date_start']").val();
            var dateEnd = $("input[name='date_end']").val();
            var depositType = $("select[name='depositType']").val();
            window.location.href = getBaseURL() + 'reports/advanced_deposits/' + dateStart + '/' + dateEnd + '/' + depositType;
        });

        $('#generateFolioAuditReport').click(function () {
            $('.monthselectpicker').css('display', 'none');
            var startDate = $('#dateStart').val();
            var endDate = $('#dateEnd').val();
            if (startDate === '' || endDate === '') {
                alert(l('Start date or End date cannot be blank.'));
                return false;
            }
            window.location.href = base_url + '/reports/show_folio_audit_trail_report/' + startDate + '--' + endDate;
        });

        $("#dateStart, #dateEnd, input[name='date_start'], input[name='date_end']").datepicker({ dateFormat: 'yy-mm-dd' });

        var dateStart = $('#dateStart').val();
        var dateEnd = $('#dateEnd').val();
        var groupBy = $('#groupBy').val();
        var customerTypeId = $('#customer_type_id').val();

        if ($('#report-content').length) {
            innGrid.renderReport(dateStart, dateEnd, groupBy, customerTypeId);
        }

        $('#generateReport').on('click', function () {
            var customerTypeId = $('#customer_type_id').val();
            var customerType = $('select option[value="' + customerTypeId + '"]').text();
            $('#selected-customer-type').text(l('Customer Type') + ': ' + customerType);
            innGrid.renderReport($('#dateStart').val(), $('#dateEnd').val(), $('#groupBy').val(), customerTypeId);
        });

        $('#downloadReport').on('click', function () {
            var dateStart = $('#dateStart').val();
            var dateEnd = $('#dateEnd').val();
            var groupBy = $('#groupBy').val();
            var customerTypeId = $('#customer_type_id').val();
            var customerType = $('select option[value="' + customerTypeId + '"]').text();
            $('#selected-customer-type').text(l('Customer Type') + ': ' + customerType);
            window.location.href = getBaseURL() + 'reports/ledger/download_summary_csv_export/' + dateStart + '/' + dateEnd + '/' + groupBy + '/' + customerTypeId;
        });

        $('#dateStart, #dateEnd').on('change', function () {
            $('#dateStartPrint').text($('#dateStart').val());
            $('#dateEndPrint').text($('#dateEnd').val());
        });

        $('#report_type').change(function () {
            var reportType = $(this).val();
            if (reportType === 'room_type') {
                $('#room_type').show();
            } else {
                $('#room_type').hide();
            }
        });

        $('.show_daily_account_report').click(function () {
            var startDate = $('#dateStart').val();
            var endDate = $('#dateEnd').val();
            if (startDate === '' || endDate === '') {
                alert(l('Start date or End date cannot be blank.'));
                return false;
            }
            window.location.assign(getBaseURL() + 'reports/daily_account_summary/' + startDate + '--' + endDate);
        });
    });
})(jQuery);
