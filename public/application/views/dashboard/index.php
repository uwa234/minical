<?php
$snapshot = $snapshot;
$currency_symbol = $currency_symbol;
$analytics = isset($analytics) ? $analytics : array();
$analytics_summary = isset($analytics['summary']) ? $analytics['summary'] : array();
$analytics_days = isset($analytics_days) ? (int) $analytics_days : 14;
$analytics_payload = isset($analytics_payload) ? $analytics_payload : array();
$analytics_period_label = sprintf(l('analytics_period_days', true), $analytics_days);
$trial_context = isset($trial_context) ? $trial_context : null;
?>

<div class="dashboard-page">
    <?php if ($trial_context): ?>
        <?php $this->load->view('dashboard/trial_banner', array('trial_context' => $trial_context)); ?>
    <?php endif; ?>

    <div class="dashboard-header">
        <div class="dashboard-header-text">
            <h1 class="dashboard-title"><?php echo l('operational_dashboard', true); ?></h1>
            <p class="dashboard-subtitle">
                <?php echo l('selling_date', true); ?>:
                <strong><?php echo htmlspecialchars($selling_date_label); ?></strong>
            </p>
        </div>
        <div class="dashboard-header-actions">
            <a class="btn btn-primary" href="<?php echo base_url('booking'); ?>">
                <i class="fa fa-calendar"></i> <?php echo l('open_calendar', true); ?>
            </a>
        </div>
    </div>

    <div class="dashboard-metrics">
        <div class="dashboard-metric-card dashboard-metric-card--arrivals">
            <div class="dashboard-metric-label"><?php echo l('arrivals_today', true); ?></div>
            <div class="dashboard-metric-value"><?php echo (int) $snapshot['arrivals_count']; ?></div>
        </div>
        <div class="dashboard-metric-card dashboard-metric-card--departures">
            <div class="dashboard-metric-label"><?php echo l('departures_today', true); ?></div>
            <div class="dashboard-metric-value"><?php echo (int) $snapshot['departures_count']; ?></div>
        </div>
        <div class="dashboard-metric-card dashboard-metric-card--inhouse">
            <div class="dashboard-metric-label"><?php echo l('in_house_tonight', true); ?></div>
            <div class="dashboard-metric-value"><?php echo (int) $snapshot['in_house_count']; ?></div>
        </div>
        <div class="dashboard-metric-card dashboard-metric-card--occupancy">
            <div class="dashboard-metric-label"><?php echo l('occupancy_tonight', true); ?></div>
            <div class="dashboard-metric-value"><?php echo (int) $snapshot['occupancy_rate']; ?>%</div>
            <div class="dashboard-metric-detail">
                <?php echo (int) $snapshot['occupied_rooms']; ?> / <?php echo (int) $snapshot['total_rooms']; ?>
                <?php echo l('rooms_occupied', true); ?>
            </div>
        </div>
        <a href="<?php echo base_url('customer/outstanding_balances'); ?>" class="dashboard-metric-card dashboard-metric-card--balance dashboard-metric-card--link <?php echo $snapshot['outstanding_count'] > 0 ? 'dashboard-metric-card--alert' : ''; ?>">
            <div class="dashboard-metric-label"><?php echo l('outstanding_balance', true); ?></div>
            <div class="dashboard-metric-value">
                <?php echo htmlspecialchars($currency_symbol) . number_format($snapshot['outstanding_total'], 2); ?>
            </div>
            <div class="dashboard-metric-detail">
                <?php echo (int) $snapshot['outstanding_count']; ?>
                <?php echo l('bookings_with_balance', true); ?>
            </div>
        </a>
        <div class="dashboard-metric-card dashboard-metric-card--unassigned <?php echo $snapshot['unassigned_count'] > 0 ? 'dashboard-metric-card--alert' : ''; ?>">
            <div class="dashboard-metric-label"><?php echo l('unassigned_rooms', true); ?></div>
            <div class="dashboard-metric-value"><?php echo (int) $snapshot['unassigned_count']; ?></div>
            <div class="dashboard-metric-detail"><?php echo l('needs_room_assignment', true); ?></div>
        </div>
    </div>

    <section class="dashboard-analytics" aria-labelledby="dashboard-analytics-heading" id="dashboard-analytics">
        <div class="dashboard-analytics-header">
            <div class="dashboard-analytics-header-text">
                <h2 id="dashboard-analytics-heading" class="dashboard-analytics-title"><?php echo l('analytics_heading', true); ?></h2>
                <p class="dashboard-analytics-subtitle">
                    <span id="dashboard-analytics-period-label"><?php echo htmlspecialchars($analytics_period_label); ?></span>
                    <span class="dashboard-analytics-date-range" id="dashboard-analytics-date-range">
                        <?php
                        if (!empty($analytics['start_date']) && !empty($analytics['end_date'])) {
                            echo ' · ';
                            echo htmlspecialchars(date('M j', strtotime($analytics['start_date'])));
                            if ($analytics['start_date'] !== $analytics['end_date']) {
                                echo ' – ' . htmlspecialchars(date('M j, Y', strtotime($analytics['end_date'])));
                            }
                        }
                        ?>
                    </span>
                </p>
            </div>
            <div class="dashboard-analytics-header-actions">
                <div class="dashboard-analytics-range" role="group" aria-label="<?php echo l('analytics_time_period', true); ?>">
                    <?php foreach (array(7, 14, 30) as $range_days): ?>
                        <button
                            type="button"
                            class="btn btn-sm dashboard-range-btn <?php echo $analytics_days === $range_days ? 'btn-primary active' : 'btn-default'; ?>"
                            data-days="<?php echo $range_days; ?>"
                        ><?php echo l('analytics_days_' . $range_days, true); ?></button>
                    <?php endforeach; ?>
                </div>
                <a class="dashboard-analytics-report-link" href="<?php echo base_url('reports/ledger/show_monthly_payment_report'); ?>">
                    <?php echo l('view_payment_report', true); ?>
                </a>
            </div>
        </div>

        <div class="dashboard-analytics-stats">
            <div class="dashboard-analytics-stat">
                <span class="dashboard-analytics-stat-label"><?php echo l('period_revenue', true); ?></span>
                <span class="dashboard-analytics-stat-value" id="dashboard-stat-revenue">
                    <?php echo htmlspecialchars($currency_symbol) . number_format(isset($analytics_summary['period_revenue']) ? $analytics_summary['period_revenue'] : 0, 2); ?>
                </span>
            </div>
            <div class="dashboard-analytics-stat">
                <span class="dashboard-analytics-stat-label"><?php echo l('period_arrivals', true); ?></span>
                <span class="dashboard-analytics-stat-value" id="dashboard-stat-arrivals"><?php echo (int) (isset($analytics_summary['period_arrivals']) ? $analytics_summary['period_arrivals'] : 0); ?></span>
            </div>
            <div class="dashboard-analytics-stat">
                <span class="dashboard-analytics-stat-label"><?php echo l('avg_occupancy', true); ?></span>
                <span class="dashboard-analytics-stat-value" id="dashboard-stat-occupancy"><?php echo (int) (isset($analytics_summary['avg_occupancy']) ? $analytics_summary['avg_occupancy'] : 0); ?>%</span>
            </div>
        </div>

        <div class="dashboard-charts" id="dashboard-charts">
            <div class="main-card dashboard-chart-card">
                <div class="card-body">
                    <h3 class="dashboard-chart-title"><?php echo l('revenue_chart_title', true); ?></h3>
                    <div class="dashboard-chart-wrap">
                        <canvas id="dashboard-revenue-chart"></canvas>
                    </div>
                </div>
            </div>
            <div class="main-card dashboard-chart-card">
                <div class="card-body">
                    <h3 class="dashboard-chart-title"><?php echo l('occupancy_chart_title', true); ?></h3>
                    <div class="dashboard-chart-wrap">
                        <canvas id="dashboard-occupancy-chart"></canvas>
                    </div>
                </div>
            </div>
            <div class="main-card dashboard-chart-card dashboard-chart-card--wide">
                <div class="card-body">
                    <h3 class="dashboard-chart-title"><?php echo l('arrivals_chart_title', true); ?></h3>
                    <div class="dashboard-chart-wrap">
                        <canvas id="dashboard-arrivals-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <script type="application/json" id="dashboard-analytics-data"><?php
            echo json_encode(array_merge($analytics_payload, array(
                'analyticsUrl' => base_url('dashboard/analytics'),
                'selectedDays' => $analytics_days,
            )), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        ?></script>
    </section>

    <div class="dashboard-panels">
        <div class="main-card dashboard-panel">
            <div class="card-body">
                <div class="dashboard-panel-header">
                    <h2><?php echo l('arrivals_today', true); ?></h2>
                    <a href="<?php echo base_url('booking'); ?>"><?php echo l('view_all_in_calendar', true); ?></a>
                </div>
                <?php if (empty($snapshot['arrivals'])): ?>
                    <p class="dashboard-empty"><?php echo l('no_arrivals_today', true); ?></p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover dashboard-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th><?php echo l('guest', true); ?></th>
                                    <th><?php echo l('room', true); ?></th>
                                    <th><?php echo l('balance', true); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($snapshot['arrivals'] as $row): ?>
                                    <tr class="dashboard-booking-row" data-booking-id="<?php echo (int) $row['booking_id']; ?>">
                                        <td><?php echo (int) $row['booking_id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['customer_name'] ? $row['customer_name'] : '—'); ?></td>
                                        <td><?php echo htmlspecialchars($row['room_names'] ? $row['room_names'] : l('unassigned', true)); ?></td>
                                        <td><?php echo htmlspecialchars($currency_symbol) . number_format((float) $row['balance'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="main-card dashboard-panel">
            <div class="card-body">
                <div class="dashboard-panel-header">
                    <h2><?php echo l('departures_today', true); ?></h2>
                    <a href="<?php echo base_url('booking'); ?>"><?php echo l('view_all_in_calendar', true); ?></a>
                </div>
                <?php if (empty($snapshot['departures'])): ?>
                    <p class="dashboard-empty"><?php echo l('no_departures_today', true); ?></p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover dashboard-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th><?php echo l('guest', true); ?></th>
                                    <th><?php echo l('room', true); ?></th>
                                    <th><?php echo l('balance', true); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($snapshot['departures'] as $row): ?>
                                    <tr class="dashboard-booking-row" data-booking-id="<?php echo (int) $row['booking_id']; ?>">
                                        <td><?php echo (int) $row['booking_id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['customer_name'] ? $row['customer_name'] : '—'); ?></td>
                                        <td><?php echo htmlspecialchars($row['room_names'] ? $row['room_names'] : l('unassigned', true)); ?></td>
                                        <td class="<?php echo ((float) $row['balance'] > 0.005) ? 'dashboard-balance--due' : ''; ?>">
                                            <?php echo htmlspecialchars($currency_symbol) . number_format((float) $row['balance'], 2); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="main-card dashboard-panel">
            <div class="card-body">
                <div class="dashboard-panel-header">
                    <h2><?php echo l('outstanding_balance', true); ?></h2>
                    <a href="<?php echo base_url('customer/outstanding_balances'); ?>"><?php echo l('view_outstanding_balances', true); ?></a>
                </div>
                <?php if (empty($snapshot['unpaid_stays'])): ?>
                    <p class="dashboard-empty"><?php echo l('no_outstanding_balances', true); ?></p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover dashboard-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th><?php echo l('guest', true); ?></th>
                                    <th><?php echo l('room', true); ?></th>
                                    <th><?php echo l('balance', true); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($snapshot['unpaid_stays'] as $row): ?>
                                    <tr class="dashboard-booking-row" data-booking-id="<?php echo (int) $row['booking_id']; ?>">
                                        <td><?php echo (int) $row['booking_id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['customer_name'] ? $row['customer_name'] : '—'); ?></td>
                                        <td><?php echo htmlspecialchars($row['room_names'] ? $row['room_names'] : l('unassigned', true)); ?></td>
                                        <td class="dashboard-balance--due">
                                            <?php echo htmlspecialchars($currency_symbol) . number_format((float) $row['balance'], 2); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="main-card dashboard-panel">
            <div class="card-body">
                <div class="dashboard-panel-header">
                    <h2><?php echo l('unassigned_rooms', true); ?></h2>
                </div>
                <?php if (empty($snapshot['unassigned_stays'])): ?>
                    <p class="dashboard-empty"><?php echo l('no_unassigned_rooms', true); ?></p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover dashboard-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th><?php echo l('guest', true); ?></th>
                                    <th><?php echo l('dates', true); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($snapshot['unassigned_stays'] as $row): ?>
                                    <tr class="dashboard-booking-row" data-booking-id="<?php echo (int) $row['booking_id']; ?>">
                                        <td><?php echo (int) $row['booking_id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['customer_name'] ? $row['customer_name'] : '—'); ?></td>
                                        <td>
                                            <?php
                                            echo htmlspecialchars(date('M j', strtotime($row['check_in_date'])));
                                            echo ' – ';
                                            echo htmlspecialchars(date('M j', strtotime($row['check_out_date'])));
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
