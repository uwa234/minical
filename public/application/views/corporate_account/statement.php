<?php
$sym = $currency_symbol;
$date = function ($value) { return $value ? date('d M Y', strtotime($value)) : '—'; };
$limit = $account['credit_limit'] !== null ? (float) $account['credit_limit'] : null;
$over_limit = $limit !== null && $balance > $limit;
$used = $limit ? min(100, max(0, $balance / $limit * 100)) : 0;
$actions = '<button class="vx-btn vx-btn-secondary" onclick="window.print()"><i class="fa fa-print"></i> ' . l('Print statement', true) . '</button>'
    . '<a class="vx-btn vx-btn-secondary" href="' . base_url() . 'corporate_account"><i class="fa fa-arrow-left"></i> ' . l('All accounts', true) . '</a>';
$terms_options = array(0 => l('Due on checkout', true), 7 => '7 ' . l('days', true), 14 => '14 ' . l('days', true), 30 => '30 ' . l('days', true), 45 => '45 ' . l('days', true), 60 => '60 ' . l('days', true), 90 => '90 ' . l('days', true));
$current_terms = $account['payment_terms_days'] !== null ? (int) $account['payment_terms_days'] : null;
if ($current_terms !== null && !isset($terms_options[$current_terms])) { $terms_options[$current_terms] = $current_terms . ' ' . l('days', true); ksort($terms_options); }
?>
<div class="vx-app">
    <?php $this->load->view('veurion/header', array(
        'eyebrow' => l('Statement of account', true),
        'title' => vx_e($account['customer_name']),
        'subtitle' => l('As of', true) . ' ' . vx_e($date($selling_date)) . ($over_limit ? ' &nbsp;<span class="vx-badge is-danger">' . l('Over credit limit', true) . '</span>' : ''),
        'icon' => 'fa-file-text-o',
        'actions_html' => $actions,
    )); ?>

    <div class="vx-card">
        <div class="vx-card-body">
            <div class="vx-doc-head">
                <div class="vx-doc-party">
                    <div class="vx-eyebrow"><?php echo l('From', true); ?></div>
                    <strong><?php echo vx_e($company['name']); ?></strong>
                    <div class="vx-muted"><?php echo vx_e(trim($company['address'] . ' ' . $company['city'])); ?></div>
                    <div class="vx-muted"><?php echo vx_e(trim($company['phone'] . '  ' . $company['email'])); ?></div>
                </div>
                <div class="vx-doc-party" style="text-align:right">
                    <div class="vx-eyebrow"><?php echo l('Bill to', true); ?></div>
                    <strong><?php echo vx_e($account['customer_name']); ?></strong>
                    <div class="vx-muted"><?php echo vx_e(trim($account['address'] . ' ' . $account['city'])); ?></div>
                    <div class="vx-muted"><?php echo vx_e(trim($account['email'] . '  ' . $account['phone'])); ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($over_limit) : ?>
        <div class="vx-alert is-danger"><i class="fa fa-exclamation-triangle"></i><div><?php echo l('This account is over its credit limit. Collect payment before extending more credit.', true); ?></div></div>
    <?php endif; ?>

    <div class="vx-kpis">
        <div class="vx-kpi is-brand"><div class="vx-kpi-label"><i class="fa fa-money"></i><?php echo l('Balance due', true); ?></div><div class="vx-kpi-value"><?php echo vx_money($balance, $sym); ?></div><div class="vx-kpi-hint"><?php echo count($bookings) . ' ' . l('open bookings', true); ?></div></div>
        <div class="vx-kpi <?php echo $overdue > 0 ? 'is-danger' : 'is-success'; ?>"><div class="vx-kpi-label"><i class="fa fa-exclamation-circle"></i><?php echo l('Overdue', true); ?></div><div class="vx-kpi-value"><?php echo vx_money($overdue, $sym); ?></div><div class="vx-kpi-hint"><?php echo $current_terms !== null ? l('Terms', true) . ': ' . ($current_terms === 0 ? l('due on checkout', true) : $current_terms . ' ' . l('days', true)) : l('No payment terms set', true); ?></div></div>
        <div class="vx-kpi <?php echo $over_limit ? 'is-danger' : ''; ?>"><div class="vx-kpi-label"><i class="fa fa-tachometer"></i><?php echo l('Credit used', true); ?></div>
            <?php if ($limit !== null) : ?>
                <div class="vx-kpi-value"><?php echo round($used); ?>%</div>
                <div class="vx-meter <?php echo $over_limit ? 'is-danger' : ($used >= 80 ? 'is-warning' : ''); ?>" style="margin-top:8px"><div class="vx-meter-track"><div class="vx-meter-fill" style="width:<?php echo round($used); ?>%"></div></div>
                <div class="vx-meter-label"><span><?php echo l('Limit', true); ?></span><span><?php echo vx_money($limit, $sym); ?></span></div></div>
            <?php else : ?>
                <div class="vx-kpi-value">—</div><div class="vx-kpi-hint"><?php echo l('No credit limit', true); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="vx-card">
        <div class="vx-card-header"><div class="vx-card-title"><i class="fa fa-hourglass-half"></i><?php echo l('Ageing', true); ?></div><div class="vx-card-subtitle"><?php echo l('Days since check-out', true); ?></div></div>
        <div class="vx-card-body">
            <div class="vx-ageing">
                <div class="vx-ageing-cell is-current"><div class="vx-kpi-label"><?php echo l('0–30 days', true); ?></div><div class="vx-kpi-value"><?php echo vx_money($buckets['current'], $sym); ?></div></div>
                <div class="vx-ageing-cell is-30"><div class="vx-kpi-label"><?php echo l('31–60 days', true); ?></div><div class="vx-kpi-value"><?php echo vx_money($buckets['31_60'], $sym); ?></div></div>
                <div class="vx-ageing-cell is-60"><div class="vx-kpi-label"><?php echo l('61–90 days', true); ?></div><div class="vx-kpi-value"><?php echo vx_money($buckets['61_90'], $sym); ?></div></div>
                <div class="vx-ageing-cell is-90"><div class="vx-kpi-label"><?php echo l('Over 90 days', true); ?></div><div class="vx-kpi-value <?php echo $buckets['over_90'] > 0 ? 'vx-neg' : ''; ?>"><?php echo vx_money($buckets['over_90'], $sym); ?></div></div>
            </div>
        </div>
    </div>

    <div class="vx-card">
        <div class="vx-card-header"><div class="vx-card-title"><i class="fa fa-bed"></i><?php echo l('Open bookings', true); ?></div></div>
        <?php if (!$bookings) : ?>
            <div class="vx-empty"><div class="vx-empty-icon"><i class="fa fa-check"></i></div><h3><?php echo l('Nothing outstanding', true); ?></h3><p><?php echo l('This account has no unpaid bookings.', true); ?></p></div>
        <?php else : ?>
        <div class="vx-table-wrap">
            <table class="vx-table">
                <thead><tr>
                    <th><?php echo l('Booking', true); ?></th><th><?php echo l('Room', true); ?></th><th><?php echo l('Check-in', true); ?></th><th><?php echo l('Check-out', true); ?></th>
                    <th class="is-right"><?php echo l('Age', true); ?></th><th class="is-right"><?php echo l('Balance', true); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($bookings as $b) : ?>
                    <tr class="<?php echo $b['overdue'] ? 'is-danger' : ''; ?>">
                        <td><a class="vx-strong" href="<?php echo base_url(); ?>invoice/show_invoice/<?php echo (int) $b['booking_id']; ?>">#<?php echo (int) $b['booking_id']; ?></a></td>
                        <td><?php echo $b['room_name'] !== null && $b['room_name'] !== '' ? vx_e($b['room_name']) : '<span class="vx-soft">—</span>'; ?></td>
                        <td class="vx-num"><?php echo vx_e($date($b['check_in_date'])); ?></td>
                        <td class="vx-num"><?php echo vx_e($date($b['check_out_date'])); ?></td>
                        <td class="is-right"><span class="vx-badge is-plain <?php echo $b['overdue'] ? 'is-danger' : ($b['days'] > 30 ? 'is-warning' : ''); ?>"><?php echo (int) $b['days'] . ' ' . l('days', true); ?></span></td>
                        <td class="is-right vx-num vx-strong"><?php echo vx_money($b['balance'], $sym); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><tr><td colspan="5"><?php echo l('Total due', true); ?></td><td class="is-right vx-num"><?php echo vx_money($balance, $sym); ?></td></tr></tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="vx-card">
        <div class="vx-card-header"><div class="vx-card-title"><i class="fa fa-credit-card"></i><?php echo l('Recent payments', true); ?></div></div>
        <?php if (!$payments) : ?>
            <div class="vx-empty" style="padding:28px"><p style="margin:0"><?php echo l('No payments received yet.', true); ?></p></div>
        <?php else : ?>
        <div class="vx-table-wrap">
            <table class="vx-table">
                <thead><tr><th><?php echo l('Date', true); ?></th><th><?php echo l('Booking', true); ?></th><th><?php echo l('Method', true); ?></th><th><?php echo l('Description', true); ?></th><th class="is-right"><?php echo l('Amount', true); ?></th></tr></thead>
                <tbody>
                <?php foreach ($payments as $p) : ?>
                    <tr>
                        <td class="vx-num"><?php echo vx_e($date($p['selling_date'])); ?></td>
                        <td>#<?php echo (int) $p['booking_id']; ?></td>
                        <td><span class="vx-badge is-plain is-info"><?php echo vx_e($p['payment_type']); ?></span></td>
                        <td class="vx-muted"><?php echo vx_e($p['description']); ?></td>
                        <td class="is-right vx-num vx-strong vx-pos"><?php echo vx_money($p['amount'], $sym); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <form class="vx-card vx-no-print" method="post" action="<?php echo base_url(); ?>corporate_account/update/<?php echo (int) $account['customer_id']; ?>">
        <div class="vx-card-header"><div class="vx-card-title"><i class="fa fa-sliders"></i><?php echo l('Credit terms', true); ?></div></div>
        <div class="vx-card-body">
            <div class="vx-form-grid">
                <div class="vx-field"><label class="vx-label" for="credit_limit"><?php echo l('Credit limit', true); ?></label>
                    <div class="vx-input-money"><span><?php echo vx_e(vx_symbol($sym)); ?></span><input type="number" step="0.01" min="0" class="vx-input" id="credit_limit" name="credit_limit" value="<?php echo $limit !== null ? vx_e($account['credit_limit']) : ''; ?>" placeholder="<?php echo vx_e(l('No limit', true)); ?>" /></div></div>
                <div class="vx-field"><label class="vx-label" for="payment_terms_days"><?php echo l('Payment terms', true); ?></label>
                    <select class="vx-select" id="payment_terms_days" name="payment_terms_days">
                        <option value="" <?php echo $current_terms === null ? 'selected' : ''; ?>><?php echo l('Not set', true); ?></option>
                        <?php foreach ($terms_options as $days => $label) : ?>
                            <option value="<?php echo (int) $days; ?>" <?php echo $current_terms === (int) $days ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select></div>
            </div>
        </div>
        <div class="vx-card-footer">
            <button type="submit" class="vx-btn vx-btn-primary" style="order:2"><i class="fa fa-check"></i> <?php echo l('Save terms', true); ?></button>
            <button type="submit" name="remove" value="1" class="vx-btn vx-btn-danger-ghost" style="order:1" onclick="return confirm('<?php echo vx_e(l('Stop treating this customer as a corporate account? Their bookings are not changed.', true)); ?>');"><i class="fa fa-times"></i> <?php echo l('Remove corporate account', true); ?></button>
        </div>
    </form>
</div>
