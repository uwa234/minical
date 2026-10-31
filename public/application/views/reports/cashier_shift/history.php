<?php
$e = function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$money = function ($value) use ($currency_symbol, $e) { $value = (float) $value; return ($value < 0 ? "-" : "") . $e($currency_symbol) . number_format(abs($value), 2); };
$name = function ($row) { $n = trim($row['first_name'] . ' ' . $row['last_name']); return $n !== '' ? $n : $row['email']; };
$total_variance = 0;
foreach ($shifts as $s) { $total_variance += (float) $s['variance']; }
?>
<div class="mc-page cashier-shift-page">
    <?php $this->load->view('includes/mc_page_header', array(
        'title' => l('Cashier shifts', true),
        'subtitle' => l('Every shift with its expected and counted totals. Negative differences mean money is missing.', true),
        'icon' => 'fa-history',
        'actions_html' => '<a class="btn btn-default" href="' . base_url() . 'reports/cashier_shift"><i class="fa fa-money"></i> ' . l('My shift', true) . '</a>',
    )); ?>

    <div class="main-card mb-3 card mc-card">
        <div class="card-body">
            <form method="get" class="form-inline hidden-print mc-toolbar" style="margin-bottom:15px">
                <label for="start"><?php echo l('from'); ?></label>
                <input type="date" class="form-control" id="start" name="start" value="<?php echo $e($start); ?>" />
                <label for="end"><?php echo l('to'); ?></label>
                <input type="date" class="form-control" id="end" name="end" value="<?php echo $e($end); ?>" />
                <label for="user_id"><?php echo l('Cashier', true); ?></label>
                <select class="form-control" id="user_id" name="user_id">
                    <option value=""><?php echo l('All', true); ?></option>
                    <?php foreach ($users as $user) : ?>
                        <option value="<?php echo (int) $user['user_id']; ?>" <?php echo (int) $user['user_id'] === (int) $user_id ? 'selected' : ''; ?>><?php echo $e($name($user)); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-success"><i class="fa fa-refresh"></i> <?php echo l('Show', true); ?></button>
            </form>

            <table class="table table-striped">
                <thead>
                    <tr>
                        <th><?php echo l('Cashier', true); ?></th>
                        <th><?php echo l('Opened', true); ?></th>
                        <th><?php echo l('Closed', true); ?></th>
                        <th class="text-right"><?php echo l('Expected', true); ?></th>
                        <th class="text-right"><?php echo l('Counted', true); ?></th>
                        <th class="text-right"><?php echo l('Difference', true); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$shifts) : ?>
                    <tr><td colspan="7" class="text-muted"><?php echo l('No shifts in this period.', true); ?></td></tr>
                <?php endif; ?>
                <?php foreach ($shifts as $s) : ?>
                    <tr>
                        <td><?php echo $e($name($s)); ?></td>
                        <td><?php echo $e($s['opened_at_local']); ?></td>
                        <td><?php echo $s['status'] === 'closed' ? $e($s['closed_at_local']) : '<span class="label label-info">' . l('Open', true) . '</span>'; ?></td>
                        <td class="text-right"><?php echo $s['status'] === 'closed' ? $money($s['expected_total']) : '—'; ?></td>
                        <td class="text-right"><?php echo $s['status'] === 'closed' ? $money($s['counted_total']) : '—'; ?></td>
                        <td class="text-right <?php echo (float) $s['variance'] < 0 ? 'text-danger' : ((float) $s['variance'] > 0 ? 'text-warning' : ''); ?>">
                            <?php echo $s['status'] === 'closed' ? (((float) $s['variance'] > 0 ? '+' : '') . $money($s['variance'])) : '—'; ?>
                        </td>
                        <td><a href="<?php echo base_url(); ?>reports/cashier_shift/view/<?php echo (int) $s['id']; ?>"><?php echo l('View', true); ?></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <?php if ($shifts) : ?>
                <tfoot>
                    <tr>
                        <th colspan="5"><?php echo l('Total difference', true); ?></th>
                        <th class="text-right <?php echo $total_variance < 0 ? 'text-danger' : ''; ?>"><?php echo ($total_variance > 0 ? '+' : '') . $money($total_variance); ?></th>
                        <th></th>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
