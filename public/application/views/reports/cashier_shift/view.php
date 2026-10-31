<?php
$e = function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$money = function ($value) use ($currency_symbol, $e) { $value = (float) $value; return ($value < 0 ? "-" : "") . $e($currency_symbol) . number_format(abs($value), 2); };
$cashier = trim($shift['first_name'] . ' ' . $shift['last_name']);
$cashier = $cashier !== '' ? $cashier : $shift['email'];
$is_closed = $shift['status'] === 'closed';
$actions = '<button class="btn btn-default" onclick="window.print()"><i class="fa fa-print"></i> ' . l('Print', true) . '</button>';
if ($is_manager) {
    $actions .= ' <a class="btn btn-default" href="' . base_url() . 'reports/cashier_shift/history"><i class="fa fa-history"></i> ' . l('All shifts', true) . '</a>';
}
?>
<div class="mc-page cashier-shift-page" id="printable-container">
    <?php $this->load->view('includes/mc_page_header', array(
        'title' => l('Cashier shift report', true),
        'subtitle' => $e($cashier) . ' · ' . $e($opened_at_local) . ($is_closed ? ' – ' . $e($closed_at_local) : ' · ' . l('Open', true)),
        'icon' => 'fa-money',
        'actions_html' => $actions,
    )); ?>

    <div class="main-card mb-3 card mc-card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th><?php echo l('Payment type', true); ?></th>
                        <th class="text-right"><?php echo l('Payments taken', true); ?></th>
                        <th class="text-right"><?php echo l('Opening float', true); ?></th>
                        <th class="text-right"><?php echo l('Expected', true); ?></th>
                        <?php if ($is_closed) : ?>
                            <th class="text-right"><?php echo l('Counted', true); ?></th>
                            <th class="text-right"><?php echo l('Difference', true); ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row) : ?>
                    <tr>
                        <td><?php echo $e($row['payment_type']); ?></td>
                        <td class="text-right"><?php echo $money($row['payments']); ?> <small class="text-muted">× <?php echo (int) $row['payment_count']; ?></small></td>
                        <td class="text-right"><?php echo $row['opening_float'] > 0 ? $money($row['opening_float']) : '—'; ?></td>
                        <td class="text-right"><?php echo $money($row['expected']); ?></td>
                        <?php if ($is_closed) : ?>
                            <td class="text-right"><?php echo $money($row['counted']); ?></td>
                            <td class="text-right <?php echo $row['variance'] < 0 ? 'text-danger' : ($row['variance'] > 0 ? 'text-warning' : 'text-success'); ?>">
                                <?php echo ($row['variance'] > 0 ? '+' : '') . $money($row['variance']); ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <?php if ($is_closed) : ?>
                <tfoot>
                    <tr>
                        <th colspan="3"><?php echo l('Total', true); ?></th>
                        <th class="text-right"><?php echo $money($shift['expected_total']); ?></th>
                        <th class="text-right"><?php echo $money($shift['counted_total']); ?></th>
                        <th class="text-right <?php echo $shift['variance'] < 0 ? 'text-danger' : ($shift['variance'] > 0 ? 'text-warning' : 'text-success'); ?>">
                            <?php echo ($shift['variance'] > 0 ? '+' : '') . $money($shift['variance']); ?>
                        </th>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
            <?php if ($is_closed && trim((string) $shift['notes']) !== '') : ?>
                <p><strong><?php echo l('Notes', true); ?>:</strong> <?php echo nl2br($e($shift['notes'])); ?></p>
            <?php endif; ?>
            <p class="text-muted small"><?php echo l('Expected amounts include only payments this cashier recorded in the PMS during the shift.', true); ?></p>
        </div>
    </div>
</div>
