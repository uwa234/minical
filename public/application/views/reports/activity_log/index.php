<?php
$sym = $currency_symbol;
$categories = array('risk' => l('Sensitive actions', true), 'money' => l('All charges & payments', true), 'bookings' => l('All booking changes', true), 'all' => l('Everything', true));
$csv_url = base_url() . 'reports/activity_log?' . http_build_query(array('start' => $start, 'end' => $end, 'category' => $category, 'user_id' => $user_id ?: '', 'format' => 'csv'));
$actions = '<a class="vx-btn vx-btn-secondary" href="' . vx_e($csv_url) . '"><i class="fa fa-download"></i> ' . l('Export CSV', true) . '</a>'
    . '<button class="vx-btn vx-btn-secondary" onclick="window.print()"><i class="fa fa-print"></i> ' . l('Print', true) . '</button>';

$totals = array('events' => 0, 'deleted_charges' => 0, 'deleted_payments' => 0, 'refunds' => 0);
foreach ($summary as $s) {
    foreach ($totals as $k => $v) { $totals[$k] += $s[$k]; }
}

$icon_for = function ($ev) {
    $a = $ev['action'];
    if (stripos($a, 'deleted') !== false || stripos($a, 'voided') !== false) { return array('fa-trash-o', 'danger'); }
    if (stripos($a, 'reversed') !== false || stripos($a, 'refund') !== false) { return array('fa-undo', 'warning'); }
    if (stripos($a, 'Status') === 0) { return array('fa-flag-o', $ev['is_risk'] ? 'danger' : 'info'); }
    if (stripos($a, 'Rate') === 0) { return array('fa-tag', 'warning'); }
    if (stripos($a, 'edited') !== false || stripos($a, 'changed') !== false) { return array('fa-pencil', $ev['is_risk'] ? 'warning' : 'info'); }
    if (stripos($a, 'Payment') === 0) { return array('fa-credit-card', 'success'); }
    if (stripos($a, 'Charge added') === 0) { return array('fa-plus', 'success'); }
    if (stripos($a, 'Folio') === 0) { return array('fa-folder-o', 'info'); }
    return array('fa-sticky-note-o', '');
};
?>
<div class="vx-app">
    <?php $this->load->view('veurion/header', array(
        'eyebrow' => l('Owner controls', true),
        'title' => l('Activity log', true),
        'subtitle' => l('Who deleted, refunded, voided or changed what, and when. Times are in the hotel\'s time zone.', true),
        'icon' => 'fa-shield',
        'actions_html' => $actions,
    )); ?>

    <form method="get" class="vx-toolbar">
        <div class="vx-field"><label class="vx-label" for="start"><?php echo l('From', true); ?></label><input type="date" class="vx-input" id="start" name="start" value="<?php echo vx_e($start); ?>" /></div>
        <div class="vx-field"><label class="vx-label" for="end"><?php echo l('To', true); ?></label><input type="date" class="vx-input" id="end" name="end" value="<?php echo vx_e($end); ?>" /></div>
        <div class="vx-field is-wide"><label class="vx-label" for="category"><?php echo l('Category', true); ?></label>
            <select class="vx-select" id="category" name="category">
                <?php foreach ($categories as $key => $label) : ?>
                    <option value="<?php echo $key; ?>" <?php echo $key === $category ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="vx-field is-wide"><label class="vx-label" for="user_id"><?php echo l('User', true); ?></label>
            <select class="vx-select" id="user_id" name="user_id">
                <option value=""><?php echo l('Everyone', true); ?></option>
                <?php foreach ($users as $user) : $n = trim($user['first_name'] . ' ' . $user['last_name']); ?>
                    <option value="<?php echo (int) $user['user_id']; ?>" <?php echo (int) $user['user_id'] === (int) $user_id ? 'selected' : ''; ?>><?php echo vx_e($n !== '' ? $n : $user['email']); ?></option>
                <?php endforeach; ?>
            </select></div>
        <button type="submit" class="vx-btn vx-btn-primary"><i class="fa fa-filter"></i> <?php echo l('Apply', true); ?></button>
    </form>

    <div class="vx-kpis">
        <div class="vx-kpi is-brand"><div class="vx-kpi-label"><i class="fa fa-shield"></i><?php echo l('Sensitive actions', true); ?></div><div class="vx-kpi-value"><?php echo (int) $totals['events']; ?></div><div class="vx-kpi-hint"><?php echo count($summary) . ' ' . l('people', true); ?></div></div>
        <div class="vx-kpi <?php echo $totals['deleted_charges'] > 0 ? 'is-danger' : ''; ?>"><div class="vx-kpi-label"><i class="fa fa-trash-o"></i><?php echo l('Charges deleted / reversed', true); ?></div><div class="vx-kpi-value"><?php echo vx_money($totals['deleted_charges'], $sym); ?></div></div>
        <div class="vx-kpi <?php echo $totals['deleted_payments'] > 0 ? 'is-danger' : ''; ?>"><div class="vx-kpi-label"><i class="fa fa-ban"></i><?php echo l('Payments deleted / voided', true); ?></div><div class="vx-kpi-value"><?php echo vx_money($totals['deleted_payments'], $sym); ?></div></div>
        <div class="vx-kpi <?php echo $totals['refunds'] > 0 ? 'is-warning' : ''; ?>"><div class="vx-kpi-label"><i class="fa fa-undo"></i><?php echo l('Refunds', true); ?></div><div class="vx-kpi-value"><?php echo vx_money($totals['refunds'], $sym); ?></div></div>
    </div>

    <?php if ($summary) : ?>
    <div class="vx-card">
        <div class="vx-card-header"><div><div class="vx-card-title"><i class="fa fa-users"></i><?php echo l('By person', true); ?></div><div class="vx-card-subtitle"><?php echo l('Sensitive actions in this period, most active first.', true); ?></div></div></div>
        <div class="vx-table-wrap">
            <table class="vx-table">
                <thead><tr>
                    <th><?php echo l('Person', true); ?></th>
                    <th class="is-right"><?php echo l('Actions', true); ?></th>
                    <th class="is-right"><?php echo l('Charges deleted', true); ?></th>
                    <th class="is-right"><?php echo l('Payments deleted', true); ?></th>
                    <th class="is-right"><?php echo l('Refunds', true); ?></th>
                    <th class="is-right"><?php echo l('Booking changes', true); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($summary as $person => $s) : ?>
                    <tr>
                        <td><?php echo vx_user($person, $person === 'System / POS'); ?></td>
                        <td class="is-right"><span class="vx-badge is-plain is-brand"><?php echo (int) $s['events']; ?></span></td>
                        <td class="is-right vx-num <?php echo $s['deleted_charges'] > 0 ? 'vx-neg vx-strong' : 'vx-soft'; ?>"><?php echo vx_money($s['deleted_charges'], $sym); ?></td>
                        <td class="is-right vx-num <?php echo $s['deleted_payments'] > 0 ? 'vx-neg vx-strong' : 'vx-soft'; ?>"><?php echo vx_money($s['deleted_payments'], $sym); ?></td>
                        <td class="is-right vx-num <?php echo $s['refunds'] > 0 ? 'vx-warn vx-strong' : 'vx-soft'; ?>"><?php echo vx_money($s['refunds'], $sym); ?></td>
                        <td class="is-right vx-num"><?php echo (int) $s['booking_changes']; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($truncated) : ?>
        <div class="vx-alert is-warning"><i class="fa fa-info-circle"></i><div><?php echo l('Showing the latest 1,000 entries. Narrow the dates or filters to see the rest.', true); ?></div></div>
    <?php endif; ?>

    <div class="vx-card">
        <div class="vx-card-header"><div class="vx-card-title"><i class="fa fa-list"></i><?php echo l('Timeline', true); ?></div><span class="vx-muted"><?php echo count($events) . ' ' . l('entries', true); ?></span></div>
        <?php if (!$events) : ?>
            <div class="vx-empty"><div class="vx-empty-icon"><i class="fa fa-check-circle-o"></i></div><h3><?php echo l('Nothing to review', true); ?></h3><p><?php echo l('No activity matches these filters.', true); ?></p></div>
        <?php else : ?>
        <div class="vx-table-wrap">
            <table class="vx-table">
                <thead><tr>
                    <th><?php echo l('When', true); ?></th>
                    <th><?php echo l('Who', true); ?></th>
                    <th><?php echo l('What', true); ?></th>
                    <th><?php echo l('Details', true); ?></th>
                    <th><?php echo l('Booking', true); ?></th>
                    <th class="is-right"><?php echo l('Amount', true); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($events as $ev) : $ic = $icon_for($ev); ?>
                    <tr class="<?php echo $ev['is_risk'] ? 'is-risk' : ''; ?>">
                        <td class="vx-num vx-muted" style="white-space:nowrap"><?php echo vx_e($ev['local_time']); ?></td>
                        <td style="white-space:nowrap"><?php echo vx_user($ev['user_name'], (int) $ev['user_id'] === 0); ?></td>
                        <td style="white-space:nowrap"><span class="vx-action"><span class="vx-action-icon <?php echo $ic[1] ? 'is-' . $ic[1] : ''; ?>"><i class="fa <?php echo $ic[0]; ?>"></i></span><?php echo vx_e($ev['action']); ?></span></td>
                        <td class="vx-muted"><?php echo vx_e($ev['details']); ?></td>
                        <td><a class="vx-strong" href="<?php echo base_url(); ?>invoice/show_invoice/<?php echo (int) $ev['booking_id']; ?>">#<?php echo (int) $ev['booking_id']; ?></a></td>
                        <td class="is-right vx-num vx-strong <?php echo $ev['amount'] !== null && $ev['amount'] !== '' && (float) $ev['amount'] < 0 ? 'vx-neg' : ''; ?>"><?php echo $ev['amount'] !== null && $ev['amount'] !== '' ? vx_money($ev['amount'], $sym) : ''; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
