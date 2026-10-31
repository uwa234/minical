<?php
$e = function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$money = function ($value) use ($currency_symbol, $e) { $value = (float) $value; return ($value < 0 ? '-' : '') . $e($currency_symbol) . number_format(abs($value), 2); };
$totals = array('balance' => 0, 'overdue' => 0);
foreach ($accounts as $a) { $totals['balance'] += (float) $a['balance']; $totals['overdue'] += (float) $a['overdue']; }
?>
<div class="mc-page corporate-accounts-page">
    <?php $this->load->view('includes/mc_page_header', array(
        'title' => l('Corporate accounts', true),
        'subtitle' => l('Companies and organisations you bill on account. Bookings where they are the paying customer are charged to their account.', true),
        'icon' => 'fa-building',
    )); ?>

    <div class="main-card mb-3 card mc-card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th><?php echo l('Account', true); ?></th>
                        <th class="text-right"><?php echo l('Balance', true); ?></th>
                        <th class="text-right"><?php echo l('Credit limit', true); ?></th>
                        <th class="text-right"><?php echo l('Available credit', true); ?></th>
                        <th class="text-right"><?php echo l('Overdue', true); ?></th>
                        <th class="text-right"><?php echo l('Open bookings', true); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$accounts) : ?>
                    <tr><td colspan="7" class="text-muted"><?php echo l('No corporate accounts yet. Add one below.', true); ?></td></tr>
                <?php endif; ?>
                <?php foreach ($accounts as $a) : ?>
                    <tr class="<?php echo $a['over_limit'] ? 'danger' : ''; ?>">
                        <td><strong><?php echo $e($a['customer_name']); ?></strong>
                            <?php if ($a['over_limit']) : ?><span class="label label-danger"><?php echo l('Over limit', true); ?></span><?php endif; ?>
                            <br><small class="text-muted"><?php echo $e(trim($a['email'] . ' ' . $a['phone'])); ?></small></td>
                        <td class="text-right"><?php echo $money($a['balance']); ?></td>
                        <td class="text-right"><?php echo $a['credit_limit'] !== null ? $money($a['credit_limit']) : '<span class="text-muted">' . l('No limit', true) . '</span>'; ?></td>
                        <td class="text-right <?php echo $a['available'] !== null && $a['available'] < 0 ? 'text-danger' : ''; ?>"><?php echo $a['available'] !== null ? $money($a['available']) : '—'; ?></td>
                        <td class="text-right <?php echo (float) $a['overdue'] > 0 ? 'text-danger' : ''; ?>"><?php echo $a['payment_terms_days'] !== null ? $money($a['overdue']) : '—'; ?></td>
                        <td class="text-right"><?php echo (int) $a['open_bookings']; ?></td>
                        <td><a href="<?php echo base_url(); ?>corporate_account/statement/<?php echo (int) $a['customer_id']; ?>"><?php echo l('Statement', true); ?></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <?php if ($accounts) : ?>
                <tfoot>
                    <tr>
                        <th><?php echo l('Total', true); ?></th>
                        <th class="text-right"><?php echo $money($totals['balance']); ?></th>
                        <th colspan="2"></th>
                        <th class="text-right"><?php echo $money($totals['overdue']); ?></th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <div class="main-card mb-3 card mc-card hidden-print">
        <div class="card-body">
            <h4><?php echo l('Add corporate account', true); ?></h4>
            <form method="post" action="<?php echo base_url(); ?>corporate_account/save">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="customer_id"><?php echo l('Existing customer', true); ?></label>
                            <select class="form-control" id="customer_id" name="customer_id">
                                <option value=""><?php echo l('— Create a new company below —', true); ?></option>
                                <?php foreach ($customers as $c) : if ((int) $c['is_corporate_account'] === 1) { continue; } ?>
                                    <option value="<?php echo (int) $c['customer_id']; ?>"><?php echo $e($c['customer_name'] . ($c['email'] ? ' (' . $c['email'] . ')' : '')); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="customer_name"><?php echo l('New company name', true); ?></label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" maxlength="255" />
                        </div>
                        <div class="form-group">
                            <label for="email"><?php echo l('Email', true); ?></label>
                            <input type="email" class="form-control" id="email" name="email" maxlength="200" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone"><?php echo l('Phone', true); ?></label>
                            <input type="text" class="form-control" id="phone" name="phone" maxlength="20" />
                        </div>
                        <div class="form-group">
                            <label for="address"><?php echo l('Address', true); ?></label>
                            <input type="text" class="form-control" id="address" name="address" />
                        </div>
                        <div class="row">
                            <div class="col-sm-6 form-group">
                                <label for="credit_limit"><?php echo l('Credit limit', true); ?></label>
                                <input type="number" step="0.01" min="0" class="form-control" id="credit_limit" name="credit_limit" placeholder="<?php echo $e(l('No limit', true)); ?>" />
                            </div>
                            <div class="col-sm-6 form-group">
                                <label for="payment_terms_days"><?php echo l('Payment terms (days)', true); ?></label>
                                <input type="number" step="1" min="0" class="form-control" id="payment_terms_days" name="payment_terms_days" value="30" />
                            </div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-success"><i class="fa fa-plus"></i> <?php echo l('Add account', true); ?></button>
                <p class="help-block"><?php echo l('To bill a stay to this account, choose the company as the paying customer on the booking.', true); ?></p>
            </form>
        </div>
    </div>
</div>
