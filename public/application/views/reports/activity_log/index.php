<?php
$e = function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$money = function ($value) use ($currency_symbol, $e) { $value = (float) $value; return ($value < 0 ? '-' : '') . $e($currency_symbol) . number_format(abs($value), 2); };
$categories = array('risk' => l('Deletions, refunds and changes', true), 'money' => l('All charges and payments', true), 'bookings' => l('All booking changes', true), 'all' => l('Everything', true));
$csv_url = base_url() . 'reports/activity_log?' . http_build_query(array('start' => $start, 'end' => $end, 'category' => $category, 'user_id' => $user_id ?: '', 'format' => 'csv'));
$actions = '<a class="btn btn-default" href="' . $e($csv_url) . '"><i class="fa fa-download"></i> ' . l('Export CSV', true) . '</a> '
    . '<button class="btn btn-default" onclick="window.print()"><i class="fa fa-print"></i> ' . l('Print', true) . '</button>';
?>
<div class="mc-page activity-log-page" id="printable-container">
    <?php $this->load->view('includes/mc_page_header', array(
        'title' => l('Activity log', true),
        'subtitle' => l('Who deleted, refunded, voided or changed what, and when. Times are in the hotel\'s time zone.', true),
        'icon' => 'fa-shield',
        'actions_html' => $actions,
    )); ?>

    <div class="main-card mb-3 card mc-card">
        <div class="card-body">
            <form method="get" class="form-inline hidden-print mc-toolbar" style="margin-bottom:15px">
                <label for="start"><?php echo l('from'); ?></label>
                <input type="date" class="form-control" id="start" name="start" value="<?php echo $e($start); ?>" />
                <label for="end"><?php echo l('to'); ?></label>
                <input type="date" class="form-control" id="end" name="end" value="<?php echo $e($end); ?>" />
                <label for="category"><?php echo l('Show', true); ?></label>
                <select class="form-control" id="category" name="category">
                    <?php foreach ($categories as $key => $label) : ?>
                        <option value="<?php echo $key; ?>" <?php echo $key === $category ? 'selected' : ''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="user_id"><?php echo l('User', true); ?></label>
                <select class="form-control" id="user_id" name="user_id">
                    <option value=""><?php echo l('All', true); ?></option>
                    <?php foreach ($users as $user) : $n = trim($user['first_name'] . ' ' . $user['last_name']); ?>
                        <option value="<?php echo (int) $user['user_id']; ?>" <?php echo (int) $user['user_id'] === (int) $user_id ? 'selected' : ''; ?>><?php echo $e($n !== '' ? $n : $user['email']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-success"><i class="fa fa-refresh"></i> <?php echo l('Show', true); ?></button>
            </form>

            <?php if ($summary) : ?>
                <h4><?php echo l('By user', true); ?></h4>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th><?php echo l('User', true); ?></th>
                            <th class="text-right"><?php echo l('Sensitive actions', true); ?></th>
                            <th class="text-right"><?php echo l('Charges deleted / reversed', true); ?></th>
                            <th class="text-right"><?php echo l('Payments deleted / voided', true); ?></th>
                            <th class="text-right"><?php echo l('Refunds', true); ?></th>
                            <th class="text-right"><?php echo l('Booking changes', true); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($summary as $name => $s) : ?>
                        <tr>
                            <td><?php echo $e($name); ?></td>
                            <td class="text-right"><strong><?php echo (int) $s['events']; ?></strong></td>
                            <td class="text-right"><?php echo $money($s['deleted_charges']); ?></td>
                            <td class="text-right"><?php echo $money($s['deleted_payments']); ?></td>
                            <td class="text-right"><?php echo $money($s['refunds']); ?></td>
                            <td class="text-right"><?php echo (int) $s['booking_changes']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if ($truncated) : ?>
                <div class="alert alert-warning"><?php echo l('Showing the latest 1,000 entries. Narrow the dates or filters to see the rest.', true); ?></div>
            <?php endif; ?>

            <table class="table table-striped table-condensed">
                <thead>
                    <tr>
                        <th><?php echo l('Time', true); ?></th>
                        <th><?php echo l('User', true); ?></th>
                        <th><?php echo l('Booking', true); ?></th>
                        <th><?php echo l('Action', true); ?></th>
                        <th><?php echo l('Details', true); ?></th>
                        <th class="text-right"><?php echo l('Amount', true); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$events) : ?>
                    <tr><td colspan="6" class="text-muted"><?php echo l('Nothing recorded for these filters.', true); ?></td></tr>
                <?php endif; ?>
                <?php foreach ($events as $ev) : ?>
                    <tr class="<?php echo $ev['is_risk'] ? 'warning' : ''; ?>">
                        <td style="white-space:nowrap"><?php echo $e($ev['local_time']); ?></td>
                        <td><?php echo $e($ev['user_name']); ?></td>
                        <td><a class="hidden-print" href="<?php echo base_url(); ?>invoice/show_invoice/<?php echo (int) $ev['booking_id']; ?>">#<?php echo (int) $ev['booking_id']; ?></a><span class="visible-print-inline">#<?php echo (int) $ev['booking_id']; ?></span></td>
                        <td><?php echo $e($ev['action']); ?></td>
                        <td><?php echo $e($ev['details']); ?></td>
                        <td class="text-right"><?php echo $ev['amount'] !== null && $ev['amount'] !== '' ? $money($ev['amount']) : ''; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
