<div class="pa-page-header">
    <h1>Platform revenue</h1>
    <p>Track signups, conversions, churn, and monthly recurring revenue.</p>
</div>

<div class="pa-card" style="margin-bottom: 24px;">
    <div class="pa-card-body">
        <form method="get" class="pa-filters" style="margin: 0; border: none; padding: 0; background: transparent;" action="<?php echo base_url('admin/revenue'); ?>">
            <label for="from_date">From</label>
            <input type="date" name="from_date" id="from_date" class="form-control input-sm"
                   value="<?php echo htmlspecialchars($from_date, ENT_QUOTES, 'UTF-8'); ?>" />
            <label for="to_date">To</label>
            <input type="date" name="to_date" id="to_date" class="form-control input-sm"
                   value="<?php echo htmlspecialchars($to_date, ENT_QUOTES, 'UTF-8'); ?>" />
            <button type="submit" class="pa-btn pa-btn-primary pa-btn-sm">Apply range</button>
        </form>
    </div>
</div>

<?php if (!empty($range_summary) && is_array($range_summary)) { ?>
    <div class="pa-card">
        <div class="pa-card-header">
            <h2>Summary for selected range</h2>
        </div>
        <div class="pa-card-body pa-table-wrap">
            <table class="pa-table" style="max-width: 640px;">
                <thead>
                    <tr><th>Metric</th><th>Value</th></tr>
                </thead>
                <tbody>
                <?php
                $labels = array(
                    'creation_count' => 'Signups',
                    'conversion_count' => 'Conversions (cumulative)',
                    'bw_conversion_count' => 'Conversions in range',
                    'churn_count' => 'Churn',
                    'active_trial_count' => 'Active trials (5+ bookings)',
                );
                foreach ($labels as $key => $label) {
                    if (!isset($range_summary[$key][0])) {
                        continue;
                    }
                    $val = $range_summary[$key][0];
                    $display = is_array($val) ? json_encode($val) : $val;
                    foreach (array('cre_count', 'conv_count', 'bw_conv_count', 'chn_count', 'act_trial_count') as $sub) {
                        if (is_array($val) && isset($val[$sub])) {
                            $display = $val[$sub];
                        }
                    }
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><strong><?php echo htmlspecialchars((string) $display, ENT_QUOTES, 'UTF-8'); ?></strong></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
<?php } ?>

<div class="pa-card">
    <div class="pa-card-header">
        <h2>Monthly history</h2>
    </div>
    <div class="pa-card-body pa-table-wrap">
        <table class="pa-table">
            <thead>
                <tr>
                    <th>Year</th>
                    <th>Month</th>
                    <th>Signups</th>
                    <th>Conversions</th>
                    <th>Churn</th>
                    <th>Active trials</th>
                    <th>MRR added</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($monthly_rows)) { ?>
                    <tr>
                        <td colspan="7" class="pa-empty" style="padding: 32px;">No revenue data yet</td>
                    </tr>
                <?php } else {
                    foreach ($monthly_rows as $row) { ?>
                        <tr>
                            <td><?php echo (int) $row['year']; ?></td>
                            <td><?php echo (int) $row['month']; ?></td>
                            <td><?php echo (int) $row['creation_count']; ?></td>
                            <td><?php echo (int) $row['conversion_count']; ?></td>
                            <td><?php echo (int) $row['churn_count']; ?></td>
                            <td><?php echo (int) $row['active_trial_count']; ?></td>
                            <td><strong>$<?php echo isset($row['renewal_cost']) ? number_format((float) $row['renewal_cost'], 2) : '0.00'; ?></strong></td>
                        </tr>
                    <?php }
                } ?>
            </tbody>
        </table>
    </div>
</div>
