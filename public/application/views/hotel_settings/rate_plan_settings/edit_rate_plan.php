<?php
$has_rate_plans = isset($rate_plans) && is_array($rate_plans) && count($rate_plans) > 0;
?>

<?php if (!$has_rate_plans): ?>
<div class="rp-empty">
    <div class="rp-empty-icon" aria-hidden="true">
        <i class="fa fa-tags"></i>
    </div>
    <h3 class="rp-empty-title">No rate plans yet</h3>
    <p class="rp-empty-text">Create a rate plan for this room type to set pricing, extras, and online booking options.</p>
    <button type="button" class="btn btn-primary add-rate-plan-button">
        <i class="fa fa-plus"></i> <?php echo l('add_rate_plan'); ?>
    </button>
</div>
<?php else: ?>

<div class="table-responsive">
<table class="table table-hover rp-table rate-plans-table">
    <thead>
        <tr>
            <th><?php echo l('Name'); ?></th>
            <th><?php echo l(' Room Type'); ?></th>
            <th><?php echo l('Charge Type'); ?></th>
            <th><?php echo l('Currency'); ?></th>
            <th><?php echo l('Display'); ?></th>
            <th class="rp-th-actions"><?php echo l('Action'); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php
    $count = 1;
    foreach ($rate_plans as $rate_plan):
        $room_type_name = '';
        foreach ($room_types as $room_type) {
            if ($rate_plan['room_type_id'] == $room_type['id']) {
                $room_type_name = $room_type['name'];
                break;
            }
        }

        $charge_type_name = '';
        if ($charge_types && count($charge_types) > 0) {
            foreach ($charge_types as $charge_type) {
                if ($rate_plan['charge_type_id'] == $charge_type['id']) {
                    $charge_type_name = $charge_type['name'];
                    break;
                }
            }
        }

        $currency_label = '';
        foreach ($currencies as $currency) {
            if ($rate_plan['currency_id'] == $currency['currency_id']) {
                $currency_label = $currency['currency_code'] . ' (' . $currency['currency_name'] . ')';
                break;
            }
        }

        $is_online = ($rate_plan['is_shown_in_online_booking_engine'] == '1');
    ?>
        <tr class="rate-plan-row-<?php echo $rate_plan['rate_plan_id']; ?>" id="<?php echo $rate_plan['rate_plan_id']; ?>">
            <td data-label="<?php echo l('Name', true); ?>">
                <span class="rp-plan-name"><?php echo htmlspecialchars($rate_plan['rate_plan_name']); ?></span>
            </td>
            <td data-label="<?php echo l(' Room Type', true); ?>">
                <span class="rp-meta"><?php echo htmlspecialchars($room_type_name); ?></span>
            </td>
            <td data-label="<?php echo l('Charge Type', true); ?>">
                <span class="rp-meta"><?php echo htmlspecialchars($charge_type_name); ?></span>
            </td>
            <td data-label="<?php echo l('Currency', true); ?>">
                <span class="rp-meta"><?php echo htmlspecialchars($currency_label); ?></span>
            </td>
            <td data-label="<?php echo l('Display', true); ?>">
                <?php if ($is_online): ?>
                    <span class="rp-badge rp-badge--online"><i class="fa fa-globe"></i> Online</span>
                <?php else: ?>
                    <span class="rp-badge rp-badge--hidden"><i class="fa fa-eye-slash"></i> Hidden</span>
                <?php endif; ?>
            </td>
            <td class="rp-td-actions" data-label="<?php echo l('Action', true); ?>">
                <div class="rp-actions">
                    <button type="button" class="btn btn-default btn-sm edit-new-rate-plan" data-room_type_id="<?php echo $rate_plan['room_type_id']; ?>" id="<?php echo $rate_plan['rate_plan_id']; ?>">
                        <i class="fa fa-pencil"></i> <?php echo l('Edit Rate Plan', true); ?>
                    </button>
                    <a href="<?php echo base_url() . 'settings/rates/edit_rates/' . $rate_plan['rate_plan_id']; ?>" class="btn btn-success btn-sm">
                        <i class="fa fa-money"></i> <?php echo l('Edit Rates', true); ?>
                    </a>
                    <div class="btn-group">
                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                            <?php echo l('More', true); ?> <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right other-actions" role="menu">
                            <li>
                                <a href="#" class="replicate-rate-plan-button" id="<?php echo $rate_plan['rate_plan_id']; ?>">
                                    <i class="fa fa-copy"></i> <?php echo l('Replicate (Make a copy)', true); ?>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="delete-rate-plan-button" id="<?php echo $rate_plan['rate_plan_id']; ?>">
                                    <i class="fa fa-trash text-danger"></i> <?php echo l('Delete', true); ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php endif; ?>

<input type="hidden" name="subscription_level" class="subscription_level" value="<?php echo $this->company_subscription_level; ?>">
<input type="hidden" name="subscription_state" class="subscription_state" value="<?php echo $this->company_subscription_state; ?>">
<input type="hidden" name="limit_feature" class="limit_feature" value="<?php echo $this->company_feature_limit; ?>">

<div class="modal fade rp-modal" id="edit-rate-plan" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo l('Edit Rate Plan', true); ?></h4>
            </div>
            <div class="modal-body rp-form"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo l('Cancel', true); ?></button>
                <button type="button" class="btn btn-success update-rate-plan-button" count="<?php echo isset($count) ? $count : 1; ?>">
                    <i class="fa fa-check"></i> <?php echo l('Update Rate Plan', true); ?>
                </button>
            </div>
        </div>
    </div>
</div>
