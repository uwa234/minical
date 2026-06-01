<?php
$currency_symbol = isset($saas_currency_symbol) ? $saas_currency_symbol : saas_currency_symbol();
$format_amount = function ($amount) {
    return format_saas_price_amount($amount);
};
$funnel = isset($funnel_summary) ? $funnel_summary : array();
$regional = isset($regional_rows) ? $regional_rows : array();
$room_table = isset($room_size_months) ? $room_size_months : array('months' => array(), 'buckets' => array(), 'rows' => array());
$charges_table = isset($charges_by_room_months) ? $charges_by_room_months : array('months' => array(), 'buckets' => array(), 'rows' => array());
?>
<div class="pa-page-header">
    <h1>Growth &amp; analytics</h1>
    <p>Onboarding funnel, regional signups, and subscription mix by property size.</p>
</div>

<?php if (!empty($funnel['latest_month'])) { ?>
<div class="pa-stats pa-stats--compact">
    <div class="pa-stat-card pa-stat-card--accent">
        <div class="pa-stat-label">Signups (<?php echo htmlspecialchars($funnel['latest_month'], ENT_QUOTES, 'UTF-8'); ?>)</div>
        <div class="pa-stat-value"><?php echo (int) $funnel['total_sign_ups']; ?></div>
    </div>
    <div class="pa-stat-card pa-stat-card--info">
        <div class="pa-stat-label">Setup wizard completed</div>
        <div class="pa-stat-value"><?php echo (int) $funnel['completed_setup_wizard']; ?></div>
    </div>
    <div class="pa-stat-card pa-stat-card--success">
        <div class="pa-stat-label">Tutorial completed</div>
        <div class="pa-stat-value"><?php echo (int) $funnel['completed_tutorial']; ?></div>
    </div>
    <div class="pa-stat-card pa-stat-card--warning">
        <div class="pa-stat-label">First booking created</div>
        <div class="pa-stat-value"><?php echo (int) $funnel['created_booking']; ?></div>
    </div>
</div>
<?php } ?>

<div class="pa-card">
    <div class="pa-card-header">
        <h2>Onboarding funnel by month</h2>
        <span class="pa-meta-inline">Last 24 months · bars per signup cohort</span>
    </div>
    <div class="pa-card-body">
        <div class="pa-chart-wrap pa-chart-wrap--tall">
            <canvas id="pa-funnel-chart" aria-label="Onboarding funnel chart"></canvas>
        </div>
        <div id="pa-funnel-legend" class="pa-chart-legend" aria-hidden="true"></div>
        <p class="pa-meta-note">
            Grouped bars compare signups, setup wizard, tutorial, and first booking for each signup month (zeros when no activity).
        </p>
    </div>
</div>

<div class="pa-grid-2">
    <div class="pa-card">
        <div class="pa-card-header">
            <h2>New properties by month</h2>
        </div>
        <div class="pa-card-body">
            <div class="pa-chart-wrap">
                <canvas id="pa-subscriptions-chart" aria-label="New properties by month"></canvas>
            </div>
        </div>
    </div>
    <div class="pa-card">
        <div class="pa-card-header">
            <h2>Platform charges by month</h2>
        </div>
        <div class="pa-card-body">
            <div class="pa-chart-wrap">
                <canvas id="pa-charges-chart" aria-label="Charges by month"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="pa-card">
    <div class="pa-card-header">
        <h2>Regional performance</h2>
    </div>
    <div class="pa-card-body">
        <form method="get" class="pa-filters" style="margin: 0 0 20px; border: none; padding: 0; background: transparent;" action="<?php echo base_url('admin/analytics'); ?>">
            <label for="from_date">From</label>
            <input type="date" name="from_date" id="from_date" class="form-control input-sm"
                   value="<?php echo htmlspecialchars($from_date, ENT_QUOTES, 'UTF-8'); ?>" />
            <label for="to_date">To</label>
            <input type="date" name="to_date" id="to_date" class="form-control input-sm"
                   value="<?php echo htmlspecialchars($to_date, ENT_QUOTES, 'UTF-8'); ?>" />
            <button type="submit" class="pa-btn pa-btn-primary pa-btn-sm">Apply range</button>
        </form>
        <div class="pa-table-wrap">
            <table class="pa-table">
                <thead>
                    <tr>
                        <th>Country</th>
                        <th>Signups</th>
                        <th>Conversions</th>
                        <th>Churn</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($regional)) { ?>
                    <tr>
                        <td colspan="4" class="pa-empty" style="padding: 32px;">No regional data for this date range</td>
                    </tr>
                <?php } else {
                    foreach ($regional as $row) {
                        $country = isset($row['country']) ? $row['country'] : '—';
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($country, ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td><?php echo (int) (isset($row['creation_count']) ? $row['creation_count'] : 0); ?></td>
                            <td><?php
                                $conv = isset($row['conversion_count']) ? $row['conversion_count'] : '';
                                echo $conv === '' ? '—' : (int) $conv;
                            ?></td>
                            <td><?php
                                $churn = isset($row['churn_count']) ? $row['churn_count'] : '';
                                echo $churn === '' ? '—' : (int) $churn;
                            ?></td>
                        </tr>
                    <?php }
                } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="pa-grid-2">
    <div class="pa-card">
        <div class="pa-card-header">
            <h2>Properties by room size</h2>
            <span class="pa-meta-inline">Top buckets · last 12 months</span>
        </div>
        <div class="pa-card-body pa-table-wrap">
            <?php if (empty($room_table['rows'])) { ?>
                <div class="pa-empty"><i class="fa fa-bar-chart" aria-hidden="true"></i> No room-size data yet</div>
            <?php } else { ?>
                <table class="pa-table pa-table--compact">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <?php foreach ($room_table['buckets'] as $bucket) { ?>
                                <th><?php echo htmlspecialchars($bucket . ' rooms', ENT_QUOTES, 'UTF-8'); ?></th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($room_table['rows'] as $row) { ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['month'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <?php foreach ($room_table['buckets'] as $bucket) {
                                $val = isset($row['cells'][$bucket]) ? (int) $row['cells'][$bucket] : 0;
                                ?>
                                <td><?php echo $val > 0 ? (int) $val : '—'; ?></td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>
    </div>

    <div class="pa-card">
        <div class="pa-card-header">
            <h2>Charges by room size</h2>
            <span class="pa-meta-inline">Top buckets · last 12 months</span>
        </div>
        <div class="pa-card-body pa-table-wrap">
            <?php if (empty($charges_table['rows'])) { ?>
                <div class="pa-empty"><i class="fa fa-money" aria-hidden="true"></i> No charge data yet</div>
            <?php } else { ?>
                <table class="pa-table pa-table--compact">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <?php foreach ($charges_table['buckets'] as $bucket) { ?>
                                <th><?php echo htmlspecialchars($bucket . ' rooms', ENT_QUOTES, 'UTF-8'); ?></th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($charges_table['rows'] as $row) { ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['month'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <?php foreach ($charges_table['buckets'] as $bucket) {
                                $val = isset($row['cells'][$bucket]) ? (float) $row['cells'][$bucket] : 0;
                                ?>
                                <td><?php echo $val > 0
                                    ? htmlspecialchars($currency_symbol, ENT_QUOTES, 'UTF-8') . $format_amount($val)
                                    : '—'; ?></td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>
    </div>
</div>

<script type="application/json" id="pa-growth-analytics-data"><?php
    echo json_encode(isset($chart_payload) ? $chart_payload : array(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?></script>
