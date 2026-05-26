<?php
$currency_symbol = $this->session->userdata('currency_symbol') ? $this->session->userdata('currency_symbol') : '$';
$show_billable = ($this->company_id == 2242);
?>
<div id="printable-container" class="mc-page ledger-summary-page"
     data-currency="<?php echo htmlspecialchars($currency_symbol, ENT_QUOTES, 'UTF-8'); ?>">

    <?php $this->load->view('includes/mc_page_header', array(
        'title' => l('ledger_summary_report', true),
        'subtitle' => l('Track charges, payments, occupancy, and balances for any date range. Use Overview for trends or Detailed table for line-by-line numbers.', true),
        'icon' => 'fa-line-chart',
        'extra_html' => '<span class="visible-print-block" id="selected-customer-type">' . l('Customer Type', true) . ': ' . l('All', true) . '</span>',
        'hidden_print' => true,
    )); ?>

    <div class="main-card mb-3 card mc-card">
        <div class="card-body">

            <div class="panel panel-default hidden-print mc-toolbar">
                <div class="panel-body">
                    <div class="form-inline hidden-print ledger-filters">
                        <div class="form-group">
                            <label for="dateStart"><?php echo l('from'); ?></label>
                            <input type="text" class="form-control datepicker form-001" id="dateStart" value="<?php echo $start_date; ?>" />
                            <label for="dateEnd"><?php echo l('to'); ?></label>
                            <input type="text" class="form-control datepicker form-001" id="dateEnd" value="<?php echo $end_date; ?>" />
                            <label for="groupBy"><?php echo l('Group by', true); ?></label>
                            <select class="form-control form-001" id="groupBy">
                                <option value="daily"><?php echo l('daily'); ?></option>
                                <option value="monthly"><?php echo l('monthly'); ?></option>
                            </select>
                            <label for="customer_type_id"><?php echo l('Customer Type', true); ?></label>
                            <select class="form-control form-001" id="customer_type_id" name="customer_type_id">
                                <option value=""><?php echo l('All', true); ?></option>
                                <?php foreach ($customer_types as $customer_type): ?>
                                    <option value="<?php echo $customer_type['id']; ?>"><?php echo $customer_type['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button id="generateReport" class="btn btn-success hidden-print">
                                <i class="fa fa-refresh"></i> <?php echo l('generate_report'); ?>
                            </button>
                        </div>
                        <div class="mc-toolbar-actions">
                            <a id="downloadReport" target="_blank" href="javascript:" class="btn btn-default hidden-print" title="<?php echo l('Export to CSV', true); ?>">
                                <span class="glyphicon glyphicon-download-alt"></span>
                            </a>
                            <button id="printReportButton" class="btn btn-default hidden-print" title="<?php echo l('Print Report', true); ?>">
                                <span class="glyphicon glyphicon-print"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="ledger-report-loading" class="ledger-loading hidden-print" aria-live="polite">
                <i class="fa fa-spinner fa-spin"></i>
                <?php echo l('Loading report data…', true); ?>
            </div>

            <div id="ledger-report-body" class="ledger-report-body hidden-print" style="display:none;">
                <div class="ledger-kpi-grid" id="ledger-kpi-grid">
                    <div class="ledger-kpi-card ledger-kpi-card--charges">
                        <div class="ledger-kpi-label"><?php echo l('all_charges'); ?></div>
                        <div class="ledger-kpi-value" id="kpi-charges">—</div>
                        <div class="ledger-kpi-hint"><?php echo l('including_taxes'); ?></div>
                    </div>
                    <div class="ledger-kpi-card ledger-kpi-card--payments">
                        <div class="ledger-kpi-label"><?php echo l('all_payments'); ?></div>
                        <div class="ledger-kpi-value" id="kpi-payments">—</div>
                        <div class="ledger-kpi-hint"><?php echo l('Collected in period', true); ?></div>
                    </div>
                    <div class="ledger-kpi-card ledger-kpi-card--balance">
                        <div class="ledger-kpi-label"><?php echo l('balance'); ?></div>
                        <div class="ledger-kpi-value" id="kpi-balance">—</div>
                        <div class="ledger-kpi-hint"><?php echo l('Charges minus payments', true); ?></div>
                    </div>
                    <div class="ledger-kpi-card ledger-kpi-card--occupancy">
                        <div class="ledger-kpi-label"><?php echo l('occupancy_rate'); ?></div>
                        <div class="ledger-kpi-value" id="kpi-occupancy">—</div>
                        <div class="ledger-kpi-hint"><span id="kpi-bookings">—</span> <?php echo l('bookings'); ?></div>
                    </div>
                    <div class="ledger-kpi-card ledger-kpi-card--adr">
                        <div class="ledger-kpi-label" title="<?php echo l('Average daily rate — room revenue divided by bookings', true); ?>"><?php echo l('adr'); ?></div>
                        <div class="ledger-kpi-value" id="kpi-adr">—</div>
                        <div class="ledger-kpi-hint"><?php echo l('Average daily rate', true); ?></div>
                    </div>
                    <div class="ledger-kpi-card ledger-kpi-card--revpar">
                        <div class="ledger-kpi-label" title="<?php echo l('Revenue per available room — a key performance metric', true); ?>"><?php echo l('revpar'); ?></div>
                        <div class="ledger-kpi-value" id="kpi-revpar">—</div>
                        <div class="ledger-kpi-hint"><?php echo l('RevPAR average', true); ?></div>
                    </div>
                </div>

                <div class="ledger-view-tabs" role="tablist">
                    <button type="button" class="ledger-view-tab is-active" data-view="overview" role="tab" aria-selected="true">
                        <i class="fa fa-area-chart"></i> <?php echo l('Overview', true); ?>
                    </button>
                    <button type="button" class="ledger-view-tab" data-view="details" role="tab" aria-selected="false">
                        <i class="fa fa-table"></i> <?php echo l('Detailed table', true); ?>
                    </button>
                </div>

                <div id="ledger-overview-panel" class="ledger-overview-panel">
                    <div class="ledger-chart-card">
                        <div class="ledger-chart-card__header">
                            <h2 class="ledger-chart-card__title"><?php echo l('Charges vs payments', true); ?></h2>
                            <div class="ledger-chart-legend">
                                <span class="ledger-legend-item ledger-legend-item--charges"><?php echo l('Charges'); ?></span>
                                <span class="ledger-legend-item ledger-legend-item--payments"><?php echo l('Payment'); ?></span>
                            </div>
                        </div>
                        <div class="ledger-chart-wrap">
                            <canvas id="ledger-chart"></canvas>
                        </div>
                    </div>
                    <div id="ledger-period-cards" class="ledger-period-cards"></div>
                </div>

                <div id="ledger-details-panel" class="ledger-details-panel" style="display:none;">
                    <div class="visible-print-block ledger-print-range">
                        <?php echo l('showing_data_between'); ?>
                        <span id="dateStartPrint"><?php echo $start_date; ?></span>
                        <?php echo l('to'); ?>
                        <span id="dateEndPrint"><?php echo $end_date; ?></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover ledger-detail-table">
                            <thead>
                                <tr>
                                    <th class="text-left"><?php echo l('selling_date'); ?></th>
                                    <th class="text-center"><?php echo l('bookings'); ?><span class="hidden-print"> (<?php echo l('occupancy_rate'); ?>)</span></th>
                                    <?php if ($show_billable): ?>
                                        <th class="text-center"><?php echo l('Billable bookings'); ?></th>
                                    <?php endif; ?>
                                    <th class="text-right"><?php echo l('revpar'); ?></th>
                                    <th class="text-right"><?php echo l('adr'); ?></th>
                                    <th class="text-right"><?php echo l($this->default_room_singular) . ' ' . l('Charges Before Taxes', true); ?></th>
                                    <th class="text-right"><?php echo l('all_charges'); ?><span class="hidden-print"> <?php echo l('including_taxes'); ?></span></th>
                                    <th class="text-right"><?php echo l('all_payments'); ?></th>
                                    <th class="text-right"><?php echo l('balance'); ?></th>
                                    <th class="hidden-print text-center"><?php echo l('Flow', true); ?></th>
                                </tr>
                            </thead>
                            <tbody id="report-content"></tbody>
                            <tfoot>
                                <tr>
                                    <td class="text-right"><strong><?php echo l('total'); ?>:</strong></td>
                                    <td class="text-center">
                                        <span id="monthly-booking-count-total"></span>
                                        (<span id="monthly-occupancy-rate-total"></span>%)
                                    </td>
                                    <?php if ($show_billable): ?>
                                        <td></td>
                                    <?php endif; ?>
                                    <td class="text-right" id="monthly-revPAR-average"></td>
                                    <td class="text-right" id="monthly-ADR-average"></td>
                                    <td class="text-right" id="monthly-room-charge-total"></td>
                                    <td class="text-right" id="monthly-charge-total"></td>
                                    <td class="text-right" id="monthly-payment-total"></td>
                                    <td class="text-right" id="monthly-balance-total"></td>
                                    <td class="hidden-print"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
