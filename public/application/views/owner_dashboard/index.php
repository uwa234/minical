<?php
$kpi             = isset($kpi)             ? $kpi             : array();
$currency        = isset($currency_symbol) ? htmlspecialchars($currency_symbol) : '$';
$ytd_revenue     = isset($ytd_revenue)     ? $ytd_revenue     : 0;
$staff_count     = isset($staff_count)     ? $staff_count     : 0;
$recent_bookings = isset($recent_bookings) ? $recent_bookings : array();
$top_rooms       = isset($top_rooms)       ? $top_rooms       : array();
$chart_data      = isset($chart_data)      ? $chart_data      : '{}';

$curr_rev  = isset($kpi['revenue']['current'])  ? $kpi['revenue']['current']  : 0;
$prev_rev  = isset($kpi['revenue']['previous']) ? $kpi['revenue']['previous'] : 0;
$rev_chg   = isset($kpi['revenue']['change'])   ? $kpi['revenue']['change']   : 0;

$curr_bk   = isset($kpi['bookings']['current'])  ? $kpi['bookings']['current']  : 0;
$prev_bk   = isset($kpi['bookings']['previous']) ? $kpi['bookings']['previous'] : 0;
$bk_chg    = isset($kpi['bookings']['change'])   ? $kpi['bookings']['change']   : 0;

$curr_adr  = isset($kpi['adr']['current'])  ? $kpi['adr']['current']  : 0;
$prev_adr  = isset($kpi['adr']['previous']) ? $kpi['adr']['previous'] : 0;
$adr_chg   = isset($kpi['adr']['change'])   ? $kpi['adr']['change']   : 0;

$out_total = isset($kpi['outstanding']['total']) ? $kpi['outstanding']['total'] : 0;
$out_cnt   = isset($kpi['outstanding']['count']) ? $kpi['outstanding']['count'] : 0;

$state_labels = array(
    '0' => array('label' => 'Confirmed',    'class' => 'badge-confirmed'),
    '1' => array('label' => 'In-House',     'class' => 'badge-inhouse'),
    '4' => array('label' => 'Cancelled',    'class' => 'badge-cancelled'),
    '7' => array('label' => 'Unconfirmed',  'class' => 'badge-unconfirmed'),
);
?>

<div class="owd-page">

    <!-- ── Page header ── -->
    <div class="owd-header">
        <div class="owd-header__left">
            <h1 class="owd-header__title">
                <i class="fa fa-crown owd-header__icon"></i> Owner Dashboard
            </h1>
            <p class="owd-header__sub">
                <?php echo htmlspecialchars($selling_date_label); ?> &nbsp;·&nbsp;
                <span class="owd-header__ytd">
                    YTD Revenue: <strong><?php echo $currency . number_format($ytd_revenue, 2); ?></strong>
                </span>
            </p>
        </div>
        <div class="owd-header__right">
            <a href="<?php echo base_url('booking'); ?>" class="owd-btn owd-btn--primary">
                <i class="fa fa-calendar"></i> Calendar
            </a>
            <a href="<?php echo base_url('reports/ledger'); ?>" class="owd-btn owd-btn--secondary">
                <i class="fa fa-bar-chart"></i> Reports
            </a>
        </div>
    </div>

    <!-- ── KPI cards ── -->
    <div class="owd-kpi-grid">

        <!-- Revenue this month -->
        <div class="owd-kpi-card owd-kpi-card--revenue">
            <div class="owd-kpi-card__icon"><i class="fa fa-dollar"></i></div>
            <div class="owd-kpi-card__body">
                <div class="owd-kpi-card__label">Revenue This Month</div>
                <div class="owd-kpi-card__value"><?php echo $currency . number_format($curr_rev, 2); ?></div>
                <div class="owd-kpi-card__sub">
                    vs <?php echo $currency . number_format($prev_rev, 2); ?> last month
                    <?php if ($rev_chg >= 0): ?>
                        <span class="owd-badge owd-badge--up"><i class="fa fa-arrow-up"></i> <?php echo abs($rev_chg); ?>%</span>
                    <?php else: ?>
                        <span class="owd-badge owd-badge--down"><i class="fa fa-arrow-down"></i> <?php echo abs($rev_chg); ?>%</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- New bookings this month -->
        <div class="owd-kpi-card owd-kpi-card--bookings">
            <div class="owd-kpi-card__icon"><i class="fa fa-bookmark"></i></div>
            <div class="owd-kpi-card__body">
                <div class="owd-kpi-card__label">New Bookings This Month</div>
                <div class="owd-kpi-card__value"><?php echo number_format($curr_bk); ?></div>
                <div class="owd-kpi-card__sub">
                    vs <?php echo number_format($prev_bk); ?> last month
                    <?php if ($bk_chg >= 0): ?>
                        <span class="owd-badge owd-badge--up"><i class="fa fa-arrow-up"></i> <?php echo abs($bk_chg); ?>%</span>
                    <?php else: ?>
                        <span class="owd-badge owd-badge--down"><i class="fa fa-arrow-down"></i> <?php echo abs($bk_chg); ?>%</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Average daily rate -->
        <div class="owd-kpi-card owd-kpi-card--adr">
            <div class="owd-kpi-card__icon"><i class="fa fa-bed"></i></div>
            <div class="owd-kpi-card__body">
                <div class="owd-kpi-card__label">Avg. Daily Rate (ADR)</div>
                <div class="owd-kpi-card__value"><?php echo $currency . number_format($curr_adr, 2); ?></div>
                <div class="owd-kpi-card__sub">
                    vs <?php echo $currency . number_format($prev_adr, 2); ?> last month
                    <?php if ($adr_chg >= 0): ?>
                        <span class="owd-badge owd-badge--up"><i class="fa fa-arrow-up"></i> <?php echo abs($adr_chg); ?>%</span>
                    <?php else: ?>
                        <span class="owd-badge owd-badge--down"><i class="fa fa-arrow-down"></i> <?php echo abs($adr_chg); ?>%</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Outstanding balances -->
        <div class="owd-kpi-card owd-kpi-card--outstanding <?php echo $out_cnt > 0 ? 'owd-kpi-card--alert' : ''; ?>">
            <div class="owd-kpi-card__icon"><i class="fa fa-exclamation-circle"></i></div>
            <div class="owd-kpi-card__body">
                <div class="owd-kpi-card__label">Outstanding Balances</div>
                <div class="owd-kpi-card__value"><?php echo $currency . number_format($out_total, 2); ?></div>
                <div class="owd-kpi-card__sub">
                    <?php echo number_format($out_cnt); ?> booking<?php echo $out_cnt !== 1 ? 's' : ''; ?> with balance
                    <?php if ($out_cnt > 0): ?>
                        <a href="<?php echo base_url('customer/outstanding_balances'); ?>" class="owd-link">View all</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- YTD Revenue -->
        <div class="owd-kpi-card owd-kpi-card--ytd">
            <div class="owd-kpi-card__icon"><i class="fa fa-line-chart"></i></div>
            <div class="owd-kpi-card__body">
                <div class="owd-kpi-card__label">Year-to-Date Revenue</div>
                <div class="owd-kpi-card__value"><?php echo $currency . number_format($ytd_revenue, 2); ?></div>
                <div class="owd-kpi-card__sub"><?php echo date('Y'); ?> total collected</div>
            </div>
        </div>

        <!-- Staff count -->
        <div class="owd-kpi-card owd-kpi-card--staff">
            <div class="owd-kpi-card__icon"><i class="fa fa-users"></i></div>
            <div class="owd-kpi-card__body">
                <div class="owd-kpi-card__label">Staff Members</div>
                <div class="owd-kpi-card__value"><?php echo number_format($staff_count); ?></div>
                <div class="owd-kpi-card__sub">
                    <a href="<?php echo base_url('settings/company/employees'); ?>" class="owd-link">Manage staff</a>
                </div>
            </div>
        </div>

    </div><!-- /.owd-kpi-grid -->

    <!-- ── Charts row 1 ── -->
    <div class="owd-charts-row">

        <div class="owd-chart-card owd-chart-card--wide">
            <div class="owd-chart-card__header">
                <h2 class="owd-chart-card__title"><i class="fa fa-bar-chart"></i> Monthly Revenue (12 months)</h2>
            </div>
            <div class="owd-chart-card__body">
                <div class="owd-chart-wrap">
                    <canvas id="owd-chart-revenue"></canvas>
                </div>
            </div>
        </div>

        <div class="owd-chart-card">
            <div class="owd-chart-card__header">
                <h2 class="owd-chart-card__title"><i class="fa fa-pie-chart"></i> Booking Sources</h2>
                <span class="owd-chart-card__sub">This month</span>
            </div>
            <div class="owd-chart-card__body owd-chart-card__body--center">
                <div class="owd-chart-wrap owd-chart-wrap--doughnut">
                    <canvas id="owd-chart-sources"></canvas>
                </div>
            </div>
        </div>

    </div>

    <!-- ── Charts row 2 ── -->
    <div class="owd-charts-row">

        <div class="owd-chart-card">
            <div class="owd-chart-card__header">
                <h2 class="owd-chart-card__title"><i class="fa fa-area-chart"></i> Occupancy % (12 months)</h2>
            </div>
            <div class="owd-chart-card__body">
                <div class="owd-chart-wrap">
                    <canvas id="owd-chart-occupancy"></canvas>
                </div>
            </div>
        </div>

        <div class="owd-chart-card">
            <div class="owd-chart-card__header">
                <h2 class="owd-chart-card__title"><i class="fa fa-calendar-check-o"></i> Forward Occupancy (30 days)</h2>
            </div>
            <div class="owd-chart-card__body">
                <div class="owd-chart-wrap">
                    <canvas id="owd-chart-forward"></canvas>
                </div>
            </div>
        </div>

    </div>

    <!-- ── Bottom row ── -->
    <div class="owd-bottom-row">

        <!-- Top rooms table -->
        <div class="owd-table-card">
            <div class="owd-table-card__header">
                <h2 class="owd-table-card__title"><i class="fa fa-trophy"></i> Top Rooms by Revenue</h2>
                <span class="owd-table-card__sub">This month</span>
            </div>
            <div class="owd-table-card__body">
                <?php if ($top_rooms): ?>
                <table class="owd-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Room</th>
                            <th>Revenue</th>
                            <th>Bookings</th>
                            <th>Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $max_rev = 0;
                        foreach ($top_rooms as $r) {
                            $max_rev = max($max_rev, (float) $r['revenue']);
                        }
                        foreach ($top_rooms as $idx => $r):
                            $pct = $max_rev > 0 ? round(((float) $r['revenue'] / $max_rev) * 100) : 0;
                        ?>
                        <tr>
                            <td class="owd-table__rank"><?php echo $idx + 1; ?></td>
                            <td class="owd-table__room"><?php echo htmlspecialchars($r['room_name']); ?></td>
                            <td class="owd-table__rev"><?php echo $currency . number_format((float) $r['revenue'], 2); ?></td>
                            <td class="owd-table__bk"><?php echo (int) $r['bookings']; ?></td>
                            <td class="owd-table__bar">
                                <div class="owd-bar-wrap">
                                    <div class="owd-bar-fill" style="width:<?php echo $pct; ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="owd-empty">No room revenue data for this month yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent bookings feed -->
        <div class="owd-table-card">
            <div class="owd-table-card__header">
                <h2 class="owd-table-card__title"><i class="fa fa-clock-o"></i> Recent Bookings</h2>
                <a href="<?php echo base_url('booking'); ?>" class="owd-link owd-link--header">View all</a>
            </div>
            <div class="owd-table-card__body">
                <?php if ($recent_bookings): ?>
                <ul class="owd-feed">
                    <?php foreach ($recent_bookings as $b):
                        $state     = (string) $b['state'];
                        $state_info = isset($state_labels[$state])
                            ? $state_labels[$state]
                            : array('label' => 'Unknown', 'class' => 'badge-unknown');
                        $check_in  = $b['check_in']  ? date('M j', strtotime($b['check_in']))  : '—';
                        $check_out = $b['check_out'] ? date('M j', strtotime($b['check_out'])) : '—';
                    ?>
                    <li class="owd-feed__item">
                        <div class="owd-feed__meta">
                            <a href="<?php echo base_url('booking/view/' . $b['booking_id']); ?>" class="owd-feed__name">
                                <?php echo htmlspecialchars($b['customer_name'] ?: 'Guest'); ?>
                            </a>
                            <span class="owd-feed__source"><?php echo htmlspecialchars($b['source']); ?></span>
                        </div>
                        <div class="owd-feed__dates"><?php echo $check_in; ?> → <?php echo $check_out; ?></div>
                        <div class="owd-feed__right">
                            <span class="owd-state-badge <?php echo $state_info['class']; ?>"><?php echo $state_info['label']; ?></span>
                            <?php if ((float) $b['balance'] > 0): ?>
                                <span class="owd-feed__balance owd-feed__balance--due">
                                    <?php echo $currency . number_format((float) $b['balance'], 2); ?> due
                                </span>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p class="owd-empty">No recent bookings.</p>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /.owd-bottom-row -->

    <!-- Chart room revenue bar (hidden visual chart) -->
    <div class="owd-chart-card owd-chart-card--full" style="margin-top: 24px;">
        <div class="owd-chart-card__header">
            <h2 class="owd-chart-card__title"><i class="fa fa-home"></i> Room Revenue Comparison</h2>
            <span class="owd-chart-card__sub">This month</span>
        </div>
        <div class="owd-chart-card__body">
            <div class="owd-chart-wrap owd-chart-wrap--short">
                <canvas id="owd-chart-rooms"></canvas>
            </div>
        </div>
    </div>

</div><!-- /.owd-page -->

<script type="application/json" id="owd-chart-data"><?php echo $chart_data; ?></script>
