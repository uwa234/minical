<input id="submit" type="hidden" value="<?php echo (isset($submit))?$submit:''; ?>" />

<?php
$outstanding_balances_view = !empty($outstanding_balances_view);
$list_form_action = isset($list_form_action) ? $list_form_action : base_url('customer/show_customers');
?>

<div class="accounting-page mc-page">
<header class="accounting-header">
    <div class="accounting-header-text">
        <div class="accounting-header-icon<?php echo $outstanding_balances_view ? ' accounting-header-icon--warning' : ''; ?>" aria-hidden="true">
            <i class="fa fa-<?php echo $outstanding_balances_view ? 'exclamation-circle' : 'users'; ?>"></i>
        </div>
        <div>
            <?php if ($outstanding_balances_view): ?>
                <h1 class="accounting-title"><?php echo l('outstanding_balances', true); ?></h1>
                <p class="accounting-subtitle"><?php echo l('outstanding_balances_subtitle', true); ?></p>
            <?php else: ?>
                <h1 class="accounting-title"><?php echo l('accounting'); ?></h1>
                <p class="accounting-subtitle">Guests, folios, charges, payments, and balances.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="accounting-header-actions">
        <a id="create-new-customer" class="btn btn-primary">
            <i class="fa fa-plus" aria-hidden="true"></i>
            <?php echo l('create_new_customer'); ?>
        </a>
    </div>
</header>

<ul class="nav nav-tabs accounting-tabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link <?php echo !$outstanding_balances_view ? 'active' : ''; ?>" href="<?php echo base_url('customer/show_customers'); ?>">
            <?php echo l('all_customers', true); ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $outstanding_balances_view ? 'active' : ''; ?>" href="<?php echo base_url('customer/outstanding_balances'); ?>">
            <?php echo l('outstanding_balances', true); ?>
        </a>
    </li>
</ul>

<div class="accounting-card">
    <div class="accounting-card-body">

        <div class="accounting-filters hidden-print">
            <form method="get" action="<?php echo $list_form_action; ?>" autocomplete="off" class="form-inline">
                <input class="form-control" name="search_query" type="text" value="<?php echo isset($search_query)?$search_query:''; ?>" placeholder="<?=l('Search Keywords', true);?>"/>

                <select class="form-control" name="customer_type_id">
                    <option value=""><?php echo l('all_customer_types'); ?></option>
                    <?php foreach ($customer_types as $customer_type): ?>
                        <option value="<?php echo $customer_type['id']; ?>" <?php if ($customer_type_id == $customer_type['id']) echo 'SELECTED'; ?>>
                            <?php echo $customer_type['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label class="checkbox-inline" style="margin:0;">
                    <input type="checkbox" name="show_deleted" id="show_deleted" value="checked"
                    <?php
                        $show_deleted = isset($show_deleted)?$show_deleted:'';
                        if ($show_deleted == 'checked') echo "checked='true'";
                    ?> />
                    <?php echo l('show_deleted_customers'); ?>
                </label>

                <button type="submit" name="submit" value="search" id="search_submit" class="btn btn-default"><?php echo l('search_customers'); ?></button>

                <div class="pull-right-wep">
                    <span class="h-fix-wep"><?=l("Display");?> <?=l("Results");?></span>
                    <select class="form-control per_page" name="per_page">
                        <option value="30" <?php echo (isset($_GET['per_page']) && $_GET['per_page'] == 30) ? 'selected' : ''; ?>>30</option>
                        <option value="50" <?php echo (isset($_GET['per_page']) && $_GET['per_page'] == 50) ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo (isset($_GET['per_page']) && $_GET['per_page'] == 100) ? 'selected' : ''; ?>>100</option>
                    </select>
                    <span class="h-fix-wep"><?=l("Selling Date");?>:</span>
                    <input class="form-control search_submit-wep" id="search_date" name="date" type="text" value="<?=$selling_date;?>" placeholder="<?=l('Selling Date', true);?>"/>
                </div>
            </form>
        </div>

        <div class="table-responsive accounting-table-wrap">
            <table class="table table-hover accounting-table">
                <thead>
                    <tr>
                        <th><?php echo l('id'); ?></th>
                        <th><a href="<?php echo base_url()."customer/set_order_by/customer_name"; ?>"><?php echo l('name'); ?></a></th>
                        <th><?php echo l('phone'); ?></th>
                        <th><?php echo l('status'); ?></th>
                        <th class="text-right"><a href="<?php echo base_url()."customer/set_order_by/last_check_out_date"; ?>"><?php echo l('last_check_out'); ?></a></th>
                        <th class="text-right"><a href="<?php echo base_url()."customer/set_order_by/charge_total"; ?>"><?php echo l('charge_total'); ?></a></th>
                        <th class="text-right"><a href="<?php echo base_url()."customer/set_order_by/payment_total"; ?>"><?php echo l('payment_total'); ?></a></th>
                        <th class="text-right"><a href="<?php echo base_url()."customer/set_order_by/balance"; ?>"><?php echo l('balance'); ?></a></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($outstanding_balances_view && (!isset($rows) || count($rows) === 0)): ?>
                    <tr>
                        <td colspan="9" class="accounting-empty">
                            <?php echo l('no_customers_with_outstanding_balance', true); ?>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php
                    if (isset($rows)) {
                        foreach ($rows as $r) {
                            if (!$r->customer_name) {
                                $r->customer_name = '[not entered]';
                            }
                    ?>
                    <tr class="customer-tr" name="<?php echo $r->customer_id; ?>">
                        <td><?php echo $r->customer_id; ?></td>
                        <td>
                            <a href="<?php echo base_url().'customer/history/'.$r->customer_id; ?>">
                                <?php echo $r->customer_name; ?>
                                <?php if ($r->is_deleted): ?>
                                    <span class="deleted-customer"> - <?php echo l('DELETED', true); ?></span>
                                <?php endif; ?>
                            </a>
                        </td>
                        <td><?php echo $r->phone; ?></td>
                        <td>
                            <?php
                            if ($r->customer_type_id == VIP) {
                                echo l('VIP', true);
                            } elseif ($r->customer_type_id == BLACKLIST) {
                                echo l('Blacklist', true);
                            } else {
                                foreach ($customer_types as $customer_type) {
                                    if ($customer_type['id'] == $r->customer_type_id) {
                                        echo $customer_type['name'];
                                    }
                                }
                            }
                            ?>
                        </td>
                        <td class="text-right">
                            <?php echo ($this->enable_hourly_booking == 1 ? ($r->last_check_out_date ? date('Y-m-d h:i A', strtotime($r->last_check_out_date)) : '') : ($r->last_check_out_date ? date('Y-m-d', strtotime($r->last_check_out_date)) : '')); ?>
                        </td>
                        <td class="text-right"><?php echo number_format($r->charge_total, 2, ".", ","); ?></td>
                        <td class="text-right"><?php echo number_format($r->payment_total, 2, ".", ","); ?></td>
                        <td class="text-right <?php echo ($r->balance > 0) ? 'text-danger font-weight-bold' : ''; ?>">
                            <?php echo number_format($r->balance, 2, ".", ","); ?>
                        </td>
                        <td class="center delete-td">
                            <div class="dropdown pull-right">
                                <button class="btn btn-default btn-xs dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="true">
                                    <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                    <li role="presentation">
                                        <a role="menuitem" class="customer-profile" id="<?php echo $r->customer_id; ?>">
                                            <?php echo l('show_profile'); ?>
                                        </a>
                                    </li>
                                    <li role="presentation">
                                        <a role="menuitem" href="<?php echo base_url().'customer/history/'.$r->customer_id; ?>">
                                            <?php echo l('show_history'); ?>
                                        </a>
                                    </li>
                                    <li role="presentation">
                                        <a role="menuitem" class="delete_customer" showdelete="<?php echo $show_deleted; ?>" name="<?php echo $r->customer_id; ?>" href="#">
                                            <?php echo l('delete_customer'); ?>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?php
                        }
                    }
                    if (isset($rows) && count($rows) > 0) {
                        $charge_total = $payment_total = $balance_total = 0;
                        foreach ($rows as $value) {
                            $arr = json_decode(json_encode($value), true);
                            if (array_key_exists('charge_total', $arr)) {
                                $charge_total += $value->charge_total;
                            }
                            if (array_key_exists('payment_total', $arr)) {
                                $payment_total += $value->payment_total;
                            }
                            if (array_key_exists('balance', $arr)) {
                                $balance_total += $value->balance;
                            }
                        }
                    ?>
                    <tr>
                        <td colspan="5" class="text-right"><?php echo l('Total', true); ?>:</td>
                        <td class="text-right"><?php echo number_format($charge_total, 2, ".", ","); ?></td>
                        <td class="text-right"><?php echo number_format($payment_total, 2, ".", ","); ?></td>
                        <td class="text-right"><?php echo number_format($balance_total, 2, ".", ","); ?></td>
                        <td></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="accounting-pagination hidden-print">
            <h4><?php echo $this->pagination->create_links(); ?></h4>
            <?php $q_string = ($_SERVER["QUERY_STRING"] != '') ? ("?".$_SERVER["QUERY_STRING"]) : ''; ?>
            <a href="<?php echo base_url()."customer/download_csv_export".$q_string; ?>"><?php echo l('download_csv_export'); ?></a>
        </div>

    </div>
</div>
</div>
