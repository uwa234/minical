<?php
$e = function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$money = function ($value) use ($currency_symbol, $e) { $value = (float) $value; return ($value < 0 ? '-' : '') . $e($currency_symbol) . number_format(abs($value), 2); };
$date = function ($value) { return $value ? date('d M Y', strtotime($value)) : '—'; };
$over_limit = $account['credit_limit'] !== null && $balance > (float) $account['credit_limit'];
$actions = '<button class="btn btn-default" onclick="window.print()"><i class="fa fa-print"></i> ' . l('Print statement', true) . '</button> '
    . '<a class="btn btn-default" href="' . base_url() . 'corporate_account"><i class="fa fa-building"></i> ' . l('All accounts', true) . '</a>';
?>
<div class="mc-page corporate-statement-page" id="printable-container">
    <?php $this->load->view('includes/mc_page_header', array(
        'title' => l('Statement of account', true),
        'subtitle' => $e($account['customer_name']) . ' · ' . l('as of', true) . ' ' . $e($date($selling_date)),
        'icon' => 'fa-file-text-o',
        'actions_html' => $actions,
    )); ?>

    <div class="main-card mb-3 card mc-card">
        <div class="card-body">
            <div class="row">
                <div class="col-sm-6">
                    <strong><?php echo $e($company['name']); ?></strong><br>
                    <?php echo $e(trim($company['address'] . ' ' . $company['city'])); ?><br>
                    <?php echo $e(trim($company['phone'] . ' ' . $company['email'])); ?>
                </div>
                <div class="col-sm-6 text-right">
                    <?php echo l('Bill to', true); ?>:<br>
                    <strong><?php echo $e($account['customer_name']); ?></strong><br>
                    <?php echo $e(trim($account['address'] . ' ' . $account['city'])); ?><br>
                    <?php echo $e(trim($account['email'] . ' ' . $account['phone'])); ?>
                </div>
            </div>
            <hr>
            <div class="row text-center">
                <div class="col-xs-3"><small class="text-muted"><?php echo l('Balance due', true); ?></small><h3><?php echo $money($balance); ?></h3></div>
                <div class="col-xs-3"><small class="text-muted"><?php echo l('Credit limit', true); ?></small><h3><?php echo $account['credit_limit'] !== null ? $money($account['credit_limit']) : l('None', true); ?></h3></div>
                <div class="col-xs-3"><small class="text-muted"><?php echo l('Payment terms', true); ?></small><h3><?php echo $account['payment_terms_days'] !== null ? (int) $account['payment_terms_days'] . ' ' . l('days', true) : '—'; ?></h3></div>
                <div class="col-xs-3"><small class="text-muted"><?php echo l('Overdue', true); ?></small><h3 class="<?php echo $overdue > 0 ? 'text-danger' : ''; ?>"><?php echo $money($overdue); ?></h3></div>
            </div>
            <?php if ($over_limit) : ?>
                <div class="alert alert-danger"><?php echo l('This account is over its credit limit. Collect payment before extending more credit.', true); ?></div>
            <?php endif; ?>

            <h4><?php echo l('Ageing', true); ?></h4>
            <table class="table table-bordered text-center">
                <tr><th class="text-center"><?php echo l('0–30 days', true); ?></th><th class="text-center"><?php echo l('31–60 days', true); ?></th><th class="text-center"><?php echo l('61–90 days', true); ?></th><th class="text-center"><?php echo l('Over 90 days', true); ?></th></tr>
                <tr>
                    <td><?php echo $money($buckets['current']); ?></td>
                    <td><?php echo $money($buckets['31_60']); ?></td>
                    <td><?php echo $money($buckets['61_90']); ?></td>
                    <td class="<?php echo $buckets['over_90'] > 0 ? 'text-danger' : ''; ?>"><?php echo $money($buckets['over_90']); ?></td>
                </tr>
            </table>

            <h4><?php echo l('Open bookings', true); ?></h4>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th><?php echo l('Booking', true); ?></th>
                        <th><?php echo l('Room', true); ?></th>
                        <th><?php echo l('Check-in', true); ?></th>
                        <th><?php echo l('Check-out', true); ?></th>
                        <th class="text-right"><?php echo l('Days since check-out', true); ?></th>
                        <th class="text-right"><?php echo l('Balance', true); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$bookings) : ?>
                    <tr><td colspan="6" class="text-muted"><?php echo l('Nothing outstanding.', true); ?></td></tr>
                <?php endif; ?>
                <?php foreach ($bookings as $b) : ?>
                    <tr class="<?php echo $b['overdue'] ? 'danger' : ''; ?>">
                        <td><a class="hidden-print" href="<?php echo base_url(); ?>invoice/show_invoice/<?php echo (int) $b['booking_id']; ?>">#<?php echo (int) $b['booking_id']; ?></a><span class="visible-print-inline">#<?php echo (int) $b['booking_id']; ?></span></td>
                        <td><?php echo $e($b['room_name']); ?></td>
                        <td><?php echo $e($date($b['check_in_date'])); ?></td>
                        <td><?php echo $e($date($b['check_out_date'])); ?></td>
                        <td class="text-right"><?php echo (int) $b['days']; ?></td>
                        <td class="text-right"><?php echo $money($b['balance']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><tr><th colspan="5"><?php echo l('Total due', true); ?></th><th class="text-right"><?php echo $money($balance); ?></th></tr></tfoot>
            </table>

            <h4><?php echo l('Recent payments', true); ?></h4>
            <table class="table table-condensed">
                <thead><tr><th><?php echo l('Date', true); ?></th><th><?php echo l('Booking', true); ?></th><th><?php echo l('Method', true); ?></th><th><?php echo l('Description', true); ?></th><th class="text-right"><?php echo l('Amount', true); ?></th></tr></thead>
                <tbody>
                <?php if (!$payments) : ?>
                    <tr><td colspan="5" class="text-muted"><?php echo l('No payments yet.', true); ?></td></tr>
                <?php endif; ?>
                <?php foreach ($payments as $p) : ?>
                    <tr>
                        <td><?php echo $e($date($p['selling_date'])); ?></td>
                        <td>#<?php echo (int) $p['booking_id']; ?></td>
                        <td><?php echo $e($p['payment_type']); ?></td>
                        <td><?php echo $e($p['description']); ?></td>
                        <td class="text-right"><?php echo $money($p['amount']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="main-card mb-3 card mc-card hidden-print">
        <div class="card-body">
            <h4><?php echo l('Account settings', true); ?></h4>
            <form method="post" action="<?php echo base_url(); ?>corporate_account/update/<?php echo (int) $account['customer_id']; ?>" class="form-inline">
                <label for="credit_limit"><?php echo l('Credit limit', true); ?></label>
                <input type="number" step="0.01" min="0" class="form-control" id="credit_limit" name="credit_limit" value="<?php echo $account['credit_limit'] !== null ? $e($account['credit_limit']) : ''; ?>" placeholder="<?php echo $e(l('No limit', true)); ?>" />
                <label for="payment_terms_days"><?php echo l('Payment terms (days)', true); ?></label>
                <input type="number" step="1" min="0" class="form-control" id="payment_terms_days" name="payment_terms_days" value="<?php echo $account['payment_terms_days'] !== null ? (int) $account['payment_terms_days'] : ''; ?>" />
                <button type="submit" class="btn btn-primary"><?php echo l('Save', true); ?></button>
                <button type="submit" name="remove" value="1" class="btn btn-link text-danger" onclick="return confirm('<?php echo $e(l('Stop treating this customer as a corporate account? Their bookings are not changed.', true)); ?>');"><?php echo l('Remove corporate account', true); ?></button>
            </form>
        </div>
    </div>
</div>
