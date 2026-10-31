<?php
$sym = $currency_symbol;
$cashier = trim($shift['first_name'] . ' ' . $shift['last_name']);
$cashier = $cashier !== '' ? $cashier : $shift['email'];
$is_closed = $shift['status'] === 'closed';
$variance = (float) $shift['variance'];
$status = !$is_closed ? array('info', l('Open', true)) : ($variance == 0 ? array('success', l('Balanced', true)) : ($variance < 0 ? array('danger', l('Short', true)) : array('warning', l('Over', true))));
$actions = '<button class="vx-btn vx-btn-secondary" onclick="window.print()"><i class="fa fa-print"></i> ' . l('Print report', true) . '</button>';
if ($is_manager) {
    $actions .= '<a class="vx-btn vx-btn-secondary" href="' . base_url() . 'reports/cashier_shift/history"><i class="fa fa-history"></i> ' . l('All shifts', true) . '</a>';
}
?>
<div class="vx-app">
    <?php $this->load->view('veurion/header', array(
        'eyebrow' => l('Cashier shift report', true),
        'title' => vx_e($cashier),
        'subtitle' => vx_e($opened_at_local) . ($is_closed ? ' → ' . vx_e($closed_at_local) : ' · ' . l('still open', true))
            . ' &nbsp;<span class="vx-badge is-' . $status[0] . '">' . $status[1] . '</span>',
        'icon' => 'fa-file-text-o',
        'actions_html' => $actions,
    )); ?>

    <div class="vx-kpis">
        <div class="vx-kpi"><div class="vx-kpi-label"><i class="fa fa-inbox"></i><?php echo l('Opening float', true); ?></div><div class="vx-kpi-value"><?php echo vx_money($shift['opening_float'], $sym); ?></div></div>
        <div class="vx-kpi"><div class="vx-kpi-label"><i class="fa fa-calculator"></i><?php echo l('Expected', true); ?></div>
            <div class="vx-kpi-value"><?php echo $is_closed ? vx_money($shift['expected_total'], $sym) : '—'; ?></div></div>
        <div class="vx-kpi"><div class="vx-kpi-label"><i class="fa fa-hand-paper-o"></i><?php echo l('Counted', true); ?></div>
            <div class="vx-kpi-value"><?php echo $is_closed ? vx_money($shift['counted_total'], $sym) : '—'; ?></div></div>
        <div class="vx-kpi is-<?php echo $is_closed ? vx_tone($variance) : 'brand'; ?>"><div class="vx-kpi-label"><i class="fa fa-balance-scale"></i><?php echo l('Difference', true); ?></div>
            <div class="vx-kpi-value"><?php echo $is_closed ? vx_signed_money($variance, $sym) : '—'; ?></div>
            <div class="vx-kpi-hint"><?php echo $is_closed ? ($variance == 0 ? l('Everything matches', true) : ($variance < 0 ? l('Money is missing', true) : l('More than expected', true))) : l('Shown when the shift closes', true); ?></div></div>
    </div>

    <div class="vx-card">
        <div class="vx-card-header"><div class="vx-card-title"><i class="fa fa-list-ol"></i><?php echo l('By payment type', true); ?></div></div>
        <div class="vx-table-wrap">
            <table class="vx-table">
                <thead>
                    <tr>
                        <th><?php echo l('Payment type', true); ?></th>
                        <th class="is-right"><?php echo l('Payments', true); ?></th>
                        <th class="is-right"><?php echo l('Float', true); ?></th>
                        <th class="is-right"><?php echo l('Expected', true); ?></th>
                        <?php if ($is_closed) : ?><th class="is-right"><?php echo l('Counted', true); ?></th><th class="is-right"><?php echo l('Difference', true); ?></th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php $extra_rows = 0; foreach ($rows as $row) :
                    $is_extra = $row['expected'] == 0 && $row['payment_count'] == 0 && (!$is_closed || $row['counted'] == 0);
                    if ($is_extra) { $extra_rows++; }
                ?>
                    <tr class="<?php echo trim(($is_closed && $row['variance'] < 0 ? 'is-danger' : '') . ($is_extra ? ' vx-row-extra' : '')); ?>">
                        <td class="vx-cell-title"><?php echo vx_e($row['payment_type']); ?></td>
                        <td class="is-right vx-num"><?php echo vx_money($row['payments'], $sym); ?> <span class="vx-soft">× <?php echo (int) $row['payment_count']; ?></span></td>
                        <td class="is-right vx-num"><?php echo $row['opening_float'] > 0 ? vx_money($row['opening_float'], $sym) : '<span class="vx-soft">—</span>'; ?></td>
                        <td class="is-right vx-num vx-strong"><?php echo vx_money($row['expected'], $sym); ?></td>
                        <?php if ($is_closed) : ?>
                            <td class="is-right vx-num"><?php echo vx_money($row['counted'], $sym); ?></td>
                            <td class="is-right vx-num"><span class="vx-badge is-plain is-<?php echo vx_tone($row['variance']); ?>"><?php echo vx_signed_money($row['variance'], $sym); ?></span></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if ($extra_rows) : ?>
                    <tr class="vx-row-toggle vx-no-print"><td colspan="<?php echo $is_closed ? 6 : 4; ?>">
                        <button type="button" class="vx-btn vx-btn-ghost vx-btn-sm" onclick="this.closest('table').classList.add('vx-show-extra'); this.closest('tr').remove();">
                            <i class="fa fa-plus"></i> <?php echo sprintf(l('Show %d unused payment types', true), $extra_rows); ?>
                        </button>
                    </td></tr>
                <?php endif; ?>
                </tbody>
                <?php if ($is_closed) : ?>
                <tfoot>
                    <tr>
                        <td colspan="3"><?php echo l('Total', true); ?></td>
                        <td class="is-right vx-num"><?php echo vx_money($shift['expected_total'], $sym); ?></td>
                        <td class="is-right vx-num"><?php echo vx_money($shift['counted_total'], $sym); ?></td>
                        <td class="is-right vx-num"><span class="vx-<?php echo $variance < 0 ? 'neg' : ($variance > 0 ? 'warn' : 'pos'); ?>"><?php echo vx_signed_money($variance, $sym); ?></span></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
        <?php if ($is_closed && trim((string) $shift['notes']) !== '') : ?>
            <div class="vx-card-footer" style="justify-content:flex-start"><i class="fa fa-sticky-note-o vx-muted"></i> <span><?php echo nl2br(vx_e($shift['notes'])); ?></span></div>
        <?php endif; ?>
    </div>
    <p class="vx-help"><?php echo l('Expected amounts include only payments this cashier recorded in the PMS during the shift. Restaurant POS takings are closed in the POS.', true); ?></p>
</div>
