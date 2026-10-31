<?php
$e = function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$money = function ($value) use ($currency_symbol, $e) { $value = (float) $value; return ($value < 0 ? "-" : "") . $e($currency_symbol) . number_format(abs($value), 2); };
$actions = $is_manager
    ? '<a class="btn btn-default" href="' . base_url() . 'reports/cashier_shift/history"><i class="fa fa-history"></i> ' . l('All shifts', true) . '</a>'
    : '';
?>
<div class="mc-page cashier-shift-page">
    <?php $this->load->view('includes/mc_page_header', array(
        'title' => l('Cashier shift', true),
        'subtitle' => l('Open your shift with the cash in your drawer, then count each payment type when you close.', true),
        'icon' => 'fa-money',
        'actions_html' => $actions,
    )); ?>

    <div class="main-card mb-3 card mc-card">
        <div class="card-body">
        <?php if (!$shift) : ?>
            <form method="post" action="<?php echo base_url(); ?>reports/cashier_shift/open" class="form-inline">
                <div class="form-group">
                    <label for="opening_float"><?php echo l('Opening float (cash in drawer)', true); ?></label>
                    <input type="number" step="0.01" min="0" class="form-control" id="opening_float" name="opening_float" value="0" required />
                </div>
                <button type="submit" class="btn btn-success"><i class="fa fa-play"></i> <?php echo l('Open shift', true); ?></button>
            </form>
        <?php else : ?>
            <p><?php echo l('Shift opened', true); ?>: <strong><?php echo $e($opened_at_local); ?></strong>
               &nbsp;·&nbsp; <?php echo l('Opening float', true); ?>: <strong><?php echo $money($shift['opening_float']); ?></strong></p>

            <form method="post" action="<?php echo base_url(); ?>reports/cashier_shift/close"
                  onsubmit="return confirm('<?php echo $e(l('Close this shift? You cannot edit the counts afterwards.', true)); ?>');">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><?php echo l('Payment type', true); ?></th>
                            <th class="text-right"><?php echo l('Payments taken', true); ?></th>
                            <th class="text-right"><?php echo l('Expected in hand', true); ?></th>
                            <th class="text-right" style="width:200px"><?php echo l('Counted', true); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $row) : ?>
                        <tr>
                            <td><?php echo $e($row['payment_type']); ?>
                                <?php if ($row['opening_float'] > 0) : ?><small class="text-muted">(<?php echo l('includes float', true); ?>)</small><?php endif; ?></td>
                            <td class="text-right"><?php echo $money($row['payments']); ?> <small class="text-muted">× <?php echo (int) $row['payment_count']; ?></small></td>
                            <td class="text-right"><?php echo $money($row['expected']); ?></td>
                            <td class="text-right">
                                <input type="number" step="0.01" class="form-control text-right" name="counted[<?php echo (int) $row['payment_type_id']; ?>]" placeholder="0.00" />
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="form-group">
                    <label for="notes"><?php echo l('Notes (explain any difference)', true); ?></label>
                    <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fa fa-lock"></i> <?php echo l('Close shift', true); ?></button>
            </form>
        <?php endif; ?>
        </div>
    </div>
</div>
