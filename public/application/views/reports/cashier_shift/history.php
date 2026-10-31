<?php
$sym = $currency_symbol;
$name = function ($row) { $n = trim($row['first_name'] . ' ' . $row['last_name']); return $n !== '' ? $n : $row['email']; };
$t = array('shifts' => count($shifts), 'open' => 0, 'expected' => 0, 'counted' => 0, 'variance' => 0, 'short' => 0);
foreach ($shifts as $s) {
    if ($s['status'] !== 'closed') { $t['open']++; continue; }
    $t['expected'] += (float) $s['expected_total'];
    $t['counted'] += (float) $s['counted_total'];
    $t['variance'] += (float) $s['variance'];
    if ((float) $s['variance'] < 0) { $t['short']++; }
}
?>
<div class="vx-app">
    <?php $this->load->view('veurion/header', array(
        'eyebrow' => l('Reports', true),
        'title' => l('Cashier shifts', true),
        'subtitle' => l('Every shift with what was expected and what was counted. Negative differences mean money is missing.', true),
        'icon' => 'fa-history',
        'actions_html' => '<a class="vx-btn vx-btn-primary" href="' . base_url() . 'reports/cashier_shift"><i class="fa fa-money"></i> ' . l('My shift', true) . '</a>',
    )); ?>

    <form method="get" class="vx-toolbar">
        <div class="vx-field"><label class="vx-label" for="start"><?php echo l('From', true); ?></label><input type="date" class="vx-input" id="start" name="start" value="<?php echo vx_e($start); ?>" /></div>
        <div class="vx-field"><label class="vx-label" for="end"><?php echo l('To', true); ?></label><input type="date" class="vx-input" id="end" name="end" value="<?php echo vx_e($end); ?>" /></div>
        <div class="vx-field is-wide"><label class="vx-label" for="user_id"><?php echo l('Cashier', true); ?></label>
            <select class="vx-select" id="user_id" name="user_id">
                <option value=""><?php echo l('All cashiers', true); ?></option>
                <?php foreach ($users as $user) : ?>
                    <option value="<?php echo (int) $user['user_id']; ?>" <?php echo (int) $user['user_id'] === (int) $user_id ? 'selected' : ''; ?>><?php echo vx_e($name($user)); ?></option>
                <?php endforeach; ?>
            </select></div>
        <button type="submit" class="vx-btn vx-btn-primary"><i class="fa fa-filter"></i> <?php echo l('Apply', true); ?></button>
    </form>

    <div class="vx-kpis">
        <div class="vx-kpi"><div class="vx-kpi-label"><i class="fa fa-calendar-check-o"></i><?php echo l('Shifts', true); ?></div><div class="vx-kpi-value"><?php echo (int) $t['shifts']; ?></div><div class="vx-kpi-hint"><?php echo (int) $t['open'] . ' ' . l('still open', true); ?></div></div>
        <div class="vx-kpi"><div class="vx-kpi-label"><i class="fa fa-calculator"></i><?php echo l('Expected', true); ?></div><div class="vx-kpi-value"><?php echo vx_money($t['expected'], $sym); ?></div></div>
        <div class="vx-kpi"><div class="vx-kpi-label"><i class="fa fa-hand-paper-o"></i><?php echo l('Counted', true); ?></div><div class="vx-kpi-value"><?php echo vx_money($t['counted'], $sym); ?></div></div>
        <div class="vx-kpi is-<?php echo vx_tone($t['variance']); ?>"><div class="vx-kpi-label"><i class="fa fa-balance-scale"></i><?php echo l('Total difference', true); ?></div><div class="vx-kpi-value"><?php echo vx_signed_money($t['variance'], $sym); ?></div><div class="vx-kpi-hint"><?php echo l('Short shifts', true) . ': ' . (int) $t['short']; ?></div></div>
    </div>

    <div class="vx-card">
        <?php if (!$shifts) : ?>
            <div class="vx-empty"><div class="vx-empty-icon"><i class="fa fa-inbox"></i></div><h3><?php echo l('No shifts in this period', true); ?></h3><p><?php echo l('Try a wider date range or another cashier.', true); ?></p></div>
        <?php else : ?>
        <div class="vx-table-wrap">
            <table class="vx-table">
                <thead>
                    <tr>
                        <th><?php echo l('Cashier', true); ?></th>
                        <th><?php echo l('Opened', true); ?></th>
                        <th><?php echo l('Closed', true); ?></th>
                        <th class="is-right"><?php echo l('Expected', true); ?></th>
                        <th class="is-right"><?php echo l('Counted', true); ?></th>
                        <th class="is-right"><?php echo l('Difference', true); ?></th>
                        <th class="is-center"><?php echo l('Status', true); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($shifts as $s) :
                    $closed = $s['status'] === 'closed'; $v = (float) $s['variance'];
                    $badge = !$closed ? array('info', l('Open', true)) : ($v == 0 ? array('success', l('Balanced', true)) : ($v < 0 ? array('danger', l('Short', true)) : array('warning', l('Over', true))));
                ?>
                    <tr class="<?php echo $closed && $v < 0 ? 'is-danger' : ''; ?>">
                        <td><?php echo vx_user($name($s)); ?></td>
                        <td class="vx-num"><?php echo vx_e($s['opened_at_local']); ?></td>
                        <td class="vx-num"><?php echo $closed ? vx_e($s['closed_at_local']) : '<span class="vx-soft">—</span>'; ?></td>
                        <td class="is-right vx-num"><?php echo $closed ? vx_money($s['expected_total'], $sym) : '—'; ?></td>
                        <td class="is-right vx-num"><?php echo $closed ? vx_money($s['counted_total'], $sym) : '—'; ?></td>
                        <td class="is-right vx-num vx-strong <?php echo $closed ? ($v < 0 ? 'vx-neg' : ($v > 0 ? 'vx-warn' : 'vx-pos')) : ''; ?>"><?php echo $closed ? vx_signed_money($v, $sym) : '—'; ?></td>
                        <td class="is-center"><span class="vx-badge is-<?php echo $badge[0]; ?>"><?php echo $badge[1]; ?></span></td>
                        <td class="is-right"><a class="vx-btn vx-btn-ghost vx-btn-sm" href="<?php echo base_url(); ?>reports/cashier_shift/view/<?php echo (int) $s['id']; ?>"><?php echo l('Report', true); ?> <i class="fa fa-angle-right"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
