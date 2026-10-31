<?php
$sym = $currency_symbol;
$actions = $is_manager
    ? '<a class="vx-btn vx-btn-secondary" href="' . base_url() . 'reports/cashier_shift/history"><i class="fa fa-history"></i> ' . l('All shifts', true) . '</a>'
    : '';
?>
<div class="vx-app">
    <?php $this->load->view('veurion/header', array(
        'eyebrow' => l('Front desk', true),
        'title' => l('Cashier shift', true),
        'subtitle' => $shift
            ? l('Your shift is open. Count what you have for each payment type, then close the shift.', true)
            : l('Start your shift with the cash in your drawer. Payments you record are added up for you.', true),
        'icon' => 'fa-money',
        'actions_html' => $actions,
    )); ?>

<?php if (!$shift) : ?>
    <div class="vx-card">
        <div class="vx-start">
            <div class="vx-start-art">
                <h2><?php echo l('Ready to take payments?', true); ?></h2>
                <p><?php echo l('Opening a shift keeps your takings separate from everyone else\'s, so the count at the end is quick and fair.', true); ?></p>
                <ul class="vx-start-steps">
                    <li><b>1</b><?php echo l('Count the cash in your drawer and enter it below', true); ?></li>
                    <li><b>2</b><?php echo l('Record payments as usual during your shift', true); ?></li>
                    <li><b>3</b><?php echo l('At the end, count each payment type and close', true); ?></li>
                </ul>
            </div>
            <form class="vx-start-form" method="post" action="<?php echo base_url(); ?>reports/cashier_shift/open">
                <div class="vx-field">
                    <label class="vx-label" for="opening_float"><?php echo l('Opening float', true); ?></label>
                    <div class="vx-input-money">
                        <span><?php echo vx_e(vx_symbol($sym)); ?></span>
                        <input type="number" step="0.01" min="0" class="vx-input" id="opening_float" name="opening_float" value="0" required autofocus />
                    </div>
                    <p class="vx-help"><?php echo l('The cash already in the drawer before your first payment.', true); ?></p>
                </div>
                <button type="submit" class="vx-btn vx-btn-primary vx-btn-lg"><i class="fa fa-play"></i> <?php echo l('Open my shift', true); ?></button>
            </form>
        </div>
    </div>
<?php else :
    $payments_total = 0; $expected_total = 0; $payment_count = 0;
    foreach ($rows as $row) { $payments_total += $row['payments']; $expected_total += $row['expected']; $payment_count += $row['payment_count']; }
    $hours = max(0, (time() - strtotime($shift['opened_at'] . ' UTC')) / 3600);
    $duration = $hours < 1 ? round($hours * 60) . ' ' . l('min', true) : floor($hours) . 'h ' . round(($hours - floor($hours)) * 60) . 'm';
?>
    <div class="vx-kpis">
        <div class="vx-kpi"><div class="vx-kpi-label"><i class="fa fa-clock-o"></i><?php echo l('Shift opened', true); ?></div><div class="vx-kpi-value" style="font-size:18px"><?php echo vx_e($opened_at_local); ?></div><div class="vx-kpi-hint"><?php echo l('Open for', true) . ' ' . vx_e($duration); ?></div></div>
        <div class="vx-kpi"><div class="vx-kpi-label"><i class="fa fa-inbox"></i><?php echo l('Opening float', true); ?></div><div class="vx-kpi-value"><?php echo vx_money($shift['opening_float'], $sym); ?></div><div class="vx-kpi-hint"><?php echo l('Counted under cash', true); ?></div></div>
        <div class="vx-kpi"><div class="vx-kpi-label"><i class="fa fa-credit-card"></i><?php echo l('Payments taken', true); ?></div><div class="vx-kpi-value"><?php echo vx_money($payments_total, $sym); ?></div><div class="vx-kpi-hint"><?php echo (int) $payment_count . ' ' . l('payments', true); ?></div></div>
        <div class="vx-kpi is-brand"><div class="vx-kpi-label"><i class="fa fa-calculator"></i><?php echo l('Expected in hand', true); ?></div><div class="vx-kpi-value"><?php echo vx_money($expected_total, $sym); ?></div><div class="vx-kpi-hint"><?php echo l('Float plus payments', true); ?></div></div>
    </div>

    <form method="post" action="<?php echo base_url(); ?>reports/cashier_shift/close" id="vx-close-shift"
          onsubmit="return confirm('<?php echo vx_e(l('Close this shift? Counts cannot be changed afterwards.', true)); ?>');">
        <div class="vx-card">
            <div class="vx-card-header">
                <div><div class="vx-card-title"><i class="fa fa-list-ol"></i><?php echo l('Count and close', true); ?></div>
                <div class="vx-card-subtitle"><?php echo l('Enter what you actually have: cash in the drawer, the POS terminal settlement slip, and transfers you can see in the bank app.', true); ?></div></div>
            </div>
            <div class="vx-table-wrap">
                <table class="vx-table">
                    <thead>
                        <tr>
                            <th><?php echo l('Payment type', true); ?></th>
                            <th class="is-right"><?php echo l('Payments', true); ?></th>
                            <th class="is-right"><?php echo l('Expected', true); ?></th>
                            <th class="is-right" style="width:220px"><?php echo l('Counted', true); ?></th>
                            <th class="is-right" style="width:150px"><?php echo l('Difference', true); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $extra_rows = 0; foreach ($rows as $row) :
                        $is_extra = $row['expected'] == 0 && $row['payment_count'] == 0 && stripos($row['payment_type'], 'cash') === false;
                        if ($is_extra) { $extra_rows++; }
                    ?>
                        <tr data-expected="<?php echo vx_e($row['expected']); ?>" class="<?php echo $is_extra ? 'vx-row-extra' : ''; ?>">
                            <td><div class="vx-cell-title"><?php echo vx_e($row['payment_type']); ?></div>
                                <?php if ($row['opening_float'] > 0) : ?><div class="vx-cell-sub"><?php echo l('Includes float of', true) . ' ' . vx_money($row['opening_float'], $sym); ?></div><?php endif; ?></td>
                            <td class="is-right vx-num"><?php echo vx_money($row['payments'], $sym); ?><div class="vx-cell-sub"><?php echo (int) $row['payment_count']; ?> × </div></td>
                            <td class="is-right vx-num vx-strong"><?php echo vx_money($row['expected'], $sym); ?></td>
                            <td class="is-right">
                                <div class="vx-input-money"><span><?php echo vx_e(vx_symbol($sym)); ?></span>
                                <input type="number" step="0.01" class="vx-input js-counted" name="counted[<?php echo (int) $row['payment_type_id']; ?>]" placeholder="0.00" /></div>
                            </td>
                            <td class="is-right vx-num js-diff"><span class="vx-soft">—</span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($extra_rows) : ?>
                        <tr class="vx-row-toggle"><td colspan="5">
                            <button type="button" class="vx-btn vx-btn-ghost vx-btn-sm" onclick="this.closest('table').classList.add('vx-show-extra'); this.closest('tr').remove();">
                                <i class="fa fa-plus"></i> <?php echo sprintf(l('Show %d more payment types', true), $extra_rows); ?>
                            </button>
                        </td></tr>
                    <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td><?php echo l('Total', true); ?></td>
                            <td class="is-right vx-num"><?php echo vx_money($payments_total, $sym); ?></td>
                            <td class="is-right vx-num"><?php echo vx_money($expected_total, $sym); ?></td>
                            <td class="is-right vx-num" id="vx-counted-total">—</td>
                            <td class="is-right vx-num" id="vx-diff-total">—</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="vx-card-body">
                <div class="vx-field" style="margin:0">
                    <label class="vx-label" for="notes"><?php echo l('Notes', true); ?></label>
                    <textarea class="vx-textarea" id="notes" name="notes" placeholder="<?php echo vx_e(l('Explain any difference, e.g. change given from personal cash', true)); ?>"></textarea>
                </div>
            </div>
            <div class="vx-card-footer">
                <span class="vx-muted"><i class="fa fa-lock"></i> <?php echo l('Closing locks the counts and produces a printable shift report.', true); ?></span>
                <button type="submit" class="vx-btn vx-btn-primary vx-btn-lg"><i class="fa fa-check"></i> <?php echo l('Close shift', true); ?></button>
            </div>
        </div>
    </form>

    <script>
    (function () {
        var symbol = <?php echo json_encode(vx_symbol($sym)); ?>;
        function fmt(v) {
            var s = Math.abs(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            return (v < 0 ? '−' : (v > 0 ? '+' : '')) + symbol + s;
        }
        function tone(v) { v = Math.round(v * 100) / 100; return v === 0 ? 'vx-pos' : (v < 0 ? 'vx-neg' : 'vx-warn'); }
        function recalc() {
            var counted = 0, diff = 0, any = false;
            document.querySelectorAll('#vx-close-shift tbody tr[data-expected]').forEach(function (tr) {
                var input = tr.querySelector('.js-counted'), cell = tr.querySelector('.js-diff');
                if (input.value === '') { cell.innerHTML = '<span class="vx-soft">—</span>'; return; }
                any = true;
                var c = parseFloat(input.value) || 0, d = c - parseFloat(tr.getAttribute('data-expected'));
                counted += c; diff += d;
                cell.innerHTML = '<span class="vx-strong ' + tone(d) + '">' + fmt(d) + '</span>';
            });
            document.getElementById('vx-counted-total').textContent = any ? fmt(counted).replace('+', '') : '—';
            var total = document.getElementById('vx-diff-total');
            total.innerHTML = any ? '<span class="' + tone(diff) + '">' + fmt(diff) + '</span>' : '—';
        }
        document.querySelectorAll('#vx-close-shift .js-counted').forEach(function (i) { i.addEventListener('input', recalc); });
    })();
    </script>
<?php endif; ?>
</div>
