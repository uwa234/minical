<?php
$sym = $currency_symbol;
$t = array('balance' => 0, 'overdue' => 0, 'over_limit' => 0);
foreach ($accounts as $a) {
    $t['balance'] += (float) $a['balance'];
    $t['overdue'] += (float) $a['overdue'];
    if ($a['over_limit']) { $t['over_limit']++; }
}
?>
<div class="vx-app">
    <?php $this->load->view('veurion/header', array(
        'eyebrow' => l('City ledger', true),
        'title' => l('Corporate accounts', true),
        'subtitle' => l('Companies and organisations you bill on account. Any booking where they are the paying customer goes on their account.', true),
        'icon' => 'fa-building',
        'actions_html' => '<a class="vx-btn vx-btn-primary" href="#vx-add-account"><i class="fa fa-plus"></i> ' . l('Add account', true) . '</a>',
    )); ?>

    <div class="vx-kpis">
        <div class="vx-kpi"><div class="vx-kpi-label"><i class="fa fa-building-o"></i><?php echo l('Accounts', true); ?></div><div class="vx-kpi-value"><?php echo count($accounts); ?></div></div>
        <div class="vx-kpi is-brand"><div class="vx-kpi-label"><i class="fa fa-money"></i><?php echo l('Total receivable', true); ?></div><div class="vx-kpi-value"><?php echo vx_money($t['balance'], $sym); ?></div><div class="vx-kpi-hint"><?php echo l('Owed to the hotel', true); ?></div></div>
        <div class="vx-kpi <?php echo $t['overdue'] > 0 ? 'is-danger' : 'is-success'; ?>"><div class="vx-kpi-label"><i class="fa fa-exclamation-circle"></i><?php echo l('Overdue', true); ?></div><div class="vx-kpi-value"><?php echo vx_money($t['overdue'], $sym); ?></div><div class="vx-kpi-hint"><?php echo l('Past payment terms', true); ?></div></div>
        <div class="vx-kpi <?php echo $t['over_limit'] > 0 ? 'is-warning' : 'is-success'; ?>"><div class="vx-kpi-label"><i class="fa fa-tachometer"></i><?php echo l('Over credit limit', true); ?></div><div class="vx-kpi-value"><?php echo (int) $t['over_limit']; ?></div><div class="vx-kpi-hint"><?php echo l('accounts', true); ?></div></div>
    </div>

    <div class="vx-card">
        <?php if (!$accounts) : ?>
            <div class="vx-empty">
                <div class="vx-empty-icon"><i class="fa fa-building"></i></div>
                <h3><?php echo l('No corporate accounts yet', true); ?></h3>
                <p><?php echo l('Add the companies, NGOs and government offices you bill on credit to track what they owe.', true); ?></p>
                <a class="vx-btn vx-btn-primary" href="#vx-add-account"><i class="fa fa-plus"></i> <?php echo l('Add your first account', true); ?></a>
            </div>
        <?php else : ?>
        <div class="vx-table-wrap">
            <table class="vx-table">
                <thead>
                    <tr>
                        <th><?php echo l('Account', true); ?></th>
                        <th class="is-right"><?php echo l('Balance', true); ?></th>
                        <th style="width:220px"><?php echo l('Credit used', true); ?></th>
                        <th class="is-right"><?php echo l('Overdue', true); ?></th>
                        <th class="is-center"><?php echo l('Bookings', true); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($accounts as $a) :
                    $limit = $a['credit_limit'] !== null ? (float) $a['credit_limit'] : null;
                    $used = $limit ? min(100, max(0, (float) $a['balance'] / $limit * 100)) : 0;
                    $meter = $a['over_limit'] ? 'is-danger' : ($used >= 80 ? 'is-warning' : '');
                ?>
                    <tr class="<?php echo $a['over_limit'] ? 'is-danger' : ''; ?>">
                        <td>
                            <span class="vx-user"><span class="vx-avatar vx-avatar-lg"><?php echo vx_e(vx_initials($a['customer_name'])); ?></span>
                            <span><span class="vx-cell-title"><?php echo vx_e($a['customer_name']); ?></span>
                                <?php if ($a['over_limit']) : ?> <span class="vx-badge is-danger"><?php echo l('Over limit', true); ?></span><?php endif; ?>
                                <div class="vx-cell-sub"><?php echo vx_e(trim($a['email'] . '  ' . $a['phone'])); ?><?php echo $a['payment_terms_days'] !== null ? ' · ' . (int) $a['payment_terms_days'] . ' ' . l('day terms', true) : ''; ?></div></span></span>
                        </td>
                        <td class="is-right vx-num vx-strong"><?php echo vx_money($a['balance'], $sym); ?></td>
                        <td>
                            <?php if ($limit !== null) : ?>
                                <div class="vx-meter <?php echo $meter; ?>">
                                    <div class="vx-meter-track"><div class="vx-meter-fill" style="width:<?php echo round($used); ?>%"></div></div>
                                    <div class="vx-meter-label"><span><?php echo round($used); ?>%</span><span><?php echo l('of', true) . ' ' . vx_money($limit, $sym); ?></span></div>
                                </div>
                            <?php else : ?>
                                <span class="vx-badge is-plain"><?php echo l('No limit', true); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="is-right vx-num <?php echo (float) $a['overdue'] > 0 ? 'vx-neg vx-strong' : 'vx-soft'; ?>"><?php echo $a['payment_terms_days'] !== null ? vx_money($a['overdue'], $sym) : '—'; ?></td>
                        <td class="is-center"><span class="vx-badge is-plain is-brand"><?php echo (int) $a['open_bookings']; ?></span></td>
                        <td class="is-right"><a class="vx-btn vx-btn-secondary vx-btn-sm" href="<?php echo base_url(); ?>corporate_account/statement/<?php echo (int) $a['customer_id']; ?>"><?php echo l('Statement', true); ?> <i class="fa fa-angle-right"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <form class="vx-card" id="vx-add-account" method="post" action="<?php echo base_url(); ?>corporate_account/save">
        <div class="vx-card-header">
            <div><div class="vx-card-title"><i class="fa fa-plus-circle"></i><?php echo l('Add corporate account', true); ?></div>
            <div class="vx-card-subtitle"><?php echo l('Pick an existing customer or create a new company.', true); ?></div></div>
        </div>
        <div class="vx-card-body">
            <div class="vx-field">
                <label class="vx-label" for="customer_id"><?php echo l('Existing customer', true); ?></label>
                <select class="vx-select" id="customer_id" name="customer_id">
                    <option value=""><?php echo l('— Create a new company instead —', true); ?></option>
                    <?php foreach ($customers as $c) : if ((int) $c['is_corporate_account'] === 1) { continue; } ?>
                        <option value="<?php echo (int) $c['customer_id']; ?>"><?php echo vx_e($c['customer_name'] . ($c['email'] ? ' (' . $c['email'] . ')' : '')); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="vx-divider"><?php echo l('or new company', true); ?></div>
            <div class="vx-form-grid">
                <div class="vx-field"><label class="vx-label" for="customer_name"><?php echo l('Company name', true); ?></label><input type="text" class="vx-input" id="customer_name" name="customer_name" maxlength="255" placeholder="<?php echo vx_e(l('e.g. Dangote Group', true)); ?>" /></div>
                <div class="vx-field"><label class="vx-label" for="email"><?php echo l('Accounts email', true); ?></label><input type="email" class="vx-input" id="email" name="email" maxlength="200" placeholder="accounts@company.com" /></div>
                <div class="vx-field"><label class="vx-label" for="phone"><?php echo l('Phone', true); ?></label><input type="text" class="vx-input" id="phone" name="phone" maxlength="20" /></div>
                <div class="vx-field"><label class="vx-label" for="address"><?php echo l('Address', true); ?></label><input type="text" class="vx-input" id="address" name="address" /></div>
            </div>
            <div class="vx-divider"><?php echo l('credit terms', true); ?></div>
            <div class="vx-form-grid">
                <div class="vx-field"><label class="vx-label" for="credit_limit"><?php echo l('Credit limit', true); ?></label>
                    <div class="vx-input-money"><span><?php echo vx_e(vx_symbol($sym)); ?></span><input type="number" step="0.01" min="0" class="vx-input" id="credit_limit" name="credit_limit" placeholder="<?php echo vx_e(l('No limit', true)); ?>" /></div>
                    <p class="vx-help"><?php echo l('Front desk is warned when a booking would go past this.', true); ?></p></div>
                <div class="vx-field"><label class="vx-label" for="payment_terms_days"><?php echo l('Payment terms', true); ?></label>
                    <select class="vx-select" id="payment_terms_days" name="payment_terms_days">
                        <?php foreach (array(0 => l('Due on checkout', true), 7 => '7 ' . l('days', true), 14 => '14 ' . l('days', true), 30 => '30 ' . l('days', true), 45 => '45 ' . l('days', true), 60 => '60 ' . l('days', true), 90 => '90 ' . l('days', true)) as $days => $label) : ?>
                            <option value="<?php echo $days; ?>" <?php echo $days === 30 ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="vx-help"><?php echo l('Balances older than this are shown as overdue.', true); ?></p></div>
            </div>
        </div>
        <div class="vx-card-footer">
            <span class="vx-muted"><i class="fa fa-info-circle"></i> <?php echo l('To bill a stay to this account, choose the company as the paying customer on the booking.', true); ?></span>
            <button type="submit" class="vx-btn vx-btn-primary"><i class="fa fa-plus"></i> <?php echo l('Add account', true); ?></button>
        </div>
    </form>
</div>
