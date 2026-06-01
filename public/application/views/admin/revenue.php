<?php
$currency_symbol = isset($saas_currency_symbol) ? $saas_currency_symbol : saas_currency_symbol();
$format_amount = function ($amount) {
    return format_saas_price_amount($amount);
};
$revenue_total = isset($subscription_revenue_summary['total_revenue']) ? (float) $subscription_revenue_summary['total_revenue'] : 0;
$payment_count = isset($subscription_revenue_summary['payment_count']) ? (int) $subscription_revenue_summary['payment_count'] : 0;
$lifetime_total = isset($subscription_revenue_lifetime['total_revenue']) ? (float) $subscription_revenue_lifetime['total_revenue'] : 0;
$lifetime_payment_count = isset($subscription_revenue_lifetime['payment_count']) ? (int) $subscription_revenue_lifetime['payment_count'] : 0;
$lifetime_first_payment = isset($subscription_revenue_lifetime['first_payment_date']) ? $subscription_revenue_lifetime['first_payment_date'] : null;
$lifetime_last_payment = isset($subscription_revenue_lifetime['last_payment_date']) ? $subscription_revenue_lifetime['last_payment_date'] : null;
$listed_payment_count = !empty($subscription_payments) ? count($subscription_payments) : 0;
$revenue_export_url = base_url('admin/export_revenue_csv?' . http_build_query(array(
    'from_date' => $from_date,
    'to_date' => $to_date,
)));
?>
<div class="pa-page-header pa-page-header-row">
    <div>
        <h1>Platform revenue</h1>
        <p>Track subscription payments, signups, conversions, and churn (<?php echo htmlspecialchars(saas_default_currency(), ENT_QUOTES, 'UTF-8'); ?>).</p>
    </div>
    <a class="pa-btn pa-btn-secondary" href="<?php echo htmlspecialchars($revenue_export_url, ENT_QUOTES, 'UTF-8'); ?>">
        <i class="fa fa-download" aria-hidden="true"></i> Export CSV
    </a>
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

<div class="pa-stats" style="margin-bottom: 24px;">
    <div class="pa-stat-card pa-stat-card--accent">
        <div class="pa-stat-label">Lifetime subscription revenue</div>
        <div class="pa-stat-value"><?php echo htmlspecialchars($currency_symbol, ENT_QUOTES, 'UTF-8'); ?><?php echo $format_amount($lifetime_total); ?></div>
        <?php if ($lifetime_payment_count > 0) { ?>
            <div style="margin-top: 6px; font-size: 12px; color: var(--pa-muted, #64748b);">
                <?php echo (int) $lifetime_payment_count; ?> payment<?php echo $lifetime_payment_count === 1 ? '' : 's'; ?>
                <?php if ($lifetime_first_payment && $lifetime_last_payment) { ?>
                    · <?php echo htmlspecialchars($lifetime_first_payment, ENT_QUOTES, 'UTF-8'); ?>
                    – <?php echo htmlspecialchars($lifetime_last_payment, ENT_QUOTES, 'UTF-8'); ?>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
    <div class="pa-stat-card pa-stat-card--success">
        <div class="pa-stat-label">Revenue in selected range</div>
        <div class="pa-stat-value"><?php echo htmlspecialchars($currency_symbol, ENT_QUOTES, 'UTF-8'); ?><?php echo $format_amount($revenue_total); ?></div>
        <div style="margin-top: 6px; font-size: 12px; color: var(--pa-muted, #64748b);">
            <?php echo htmlspecialchars($from_date, ENT_QUOTES, 'UTF-8'); ?> – <?php echo htmlspecialchars($to_date, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    </div>
    <div class="pa-stat-card pa-stat-card--info">
        <div class="pa-stat-label">Payments in selected range</div>
        <div class="pa-stat-value"><?php echo $payment_count; ?></div>
    </div>
</div>

<div class="pa-card" style="margin-bottom: 24px;">
    <div class="pa-card-header">
        <h2>Subscription payments (selected range)</h2>
        <p style="margin: 8px 0 0; font-size: 13px; color: var(--pa-muted, #64748b);">Payments between <?php echo htmlspecialchars($from_date, ENT_QUOTES, 'UTF-8'); ?> and <?php echo htmlspecialchars($to_date, ENT_QUOTES, 'UTF-8'); ?>. Lifetime total is shown above. Deleted properties are still listed.</p>
    </div>
    <div class="pa-card-body pa-table-wrap">
        <table class="pa-table">
            <thead>
                <tr>
                    <th>Paid date</th>
                    <th>Property</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Reference</th>
                    <th>Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subscription_payments)) { ?>
                    <tr>
                        <td colspan="6" class="pa-empty" style="padding: 32px;">No subscription payments in this date range</td>
                    </tr>
                <?php } else {
                    foreach ($subscription_payments as $payment) {
                        $status = isset($payment['property_status']) ? (string) $payment['property_status'] : 'active';
                        ?>
                        <tr<?php if ($status !== 'active') { ?> style="background: #fffbeb;"<?php } ?>>
                            <td><?php echo htmlspecialchars((string) $payment['paid_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <a href="<?php echo base_url('admin/property_detail/' . (int) $payment['company_id']); ?>">
                                    <strong><?php echo htmlspecialchars((string) $payment['property_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                </a>
                                <span style="display: block; font-size: 12px; color: var(--pa-muted, #64748b);">ID <?php echo (int) $payment['company_id']; ?></span>
                                <?php if ($status === 'deleted') { ?>
                                    <span style="display: inline-block; margin-top: 4px; font-size: 11px; color: #b45309;">Property deleted</span>
                                <?php } elseif ($status === 'missing') { ?>
                                    <span style="display: inline-block; margin-top: 4px; font-size: 11px; color: #b45309;">Property record not found</span>
                                <?php } ?>
                            </td>
                            <td><?php echo htmlspecialchars((string) $payment['payment_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><strong><?php echo htmlspecialchars($currency_symbol, ENT_QUOTES, 'UTF-8'); ?><?php echo $format_amount((float) $payment['amount']); ?></strong></td>
                            <td style="font-size: 12px; word-break: break-all;"><?php echo htmlspecialchars((string) $payment['reference'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <a class="pa-btn pa-btn-secondary pa-btn-sm" target="_blank" rel="noopener"
                                   href="<?php echo htmlspecialchars(saas_subscription_receipt_url((int) $payment['company_id'], (string) $payment['reference']), ENT_QUOTES, 'UTF-8'); ?>">
                                    Receipt
                                </a>
                            </td>
                        </tr>
                    <?php }
                } ?>
            </tbody>
        </table>
        <?php if ($payment_count > 0 && $listed_payment_count !== $payment_count) { ?>
            <p style="margin: 12px 0 0; font-size: 13px; color: #b45309;">
                Showing <?php echo (int) $listed_payment_count; ?> of <?php echo (int) $payment_count; ?> payments. Refresh the page after deploying the latest update if rows are still missing.
            </p>
        <?php } ?>
    </div>
</div>

<?php if (!empty($range_summary) && is_array($range_summary)) { ?>
    <div class="pa-card" style="margin-bottom: 24px;">
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
                    'subscription_revenue' => 'Subscription revenue collected',
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
                    if ($key === 'subscription_revenue' && is_array($val)) {
                        $amount = isset($val['total_revenue']) ? (float) $val['total_revenue'] : 0;
                        $count = isset($val['payment_count']) ? (int) $val['payment_count'] : 0;
                        $display = $currency_symbol . $format_amount($amount) . ' (' . $count . ' payment' . ($count === 1 ? '' : 's') . ')';
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
                    <th>Subscription revenue</th>
                    <th>Payments</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($monthly_rows)) { ?>
                    <tr>
                        <td colspan="8" class="pa-empty" style="padding: 32px;">No revenue data yet</td>
                    </tr>
                <?php } else {
                    foreach ($monthly_rows as $row) {
                        $sub_revenue = isset($row['subscription_revenue']) ? (float) $row['subscription_revenue'] : 0;
                        $payments = isset($row['payment_count']) ? (int) $row['payment_count'] : 0;
                        ?>
                        <tr>
                            <td><?php echo (int) $row['year']; ?></td>
                            <td><?php echo (int) $row['month']; ?></td>
                            <td><?php echo (int) $row['creation_count']; ?></td>
                            <td><?php echo (int) $row['conversion_count']; ?></td>
                            <td><?php echo (int) $row['churn_count']; ?></td>
                            <td><?php echo (int) $row['active_trial_count']; ?></td>
                            <td><strong><?php echo htmlspecialchars($currency_symbol, ENT_QUOTES, 'UTF-8'); ?><?php echo $format_amount($sub_revenue); ?></strong></td>
                            <td><?php echo $payments; ?></td>
                        </tr>
                    <?php }
                } ?>
            </tbody>
        </table>
    </div>
</div>
