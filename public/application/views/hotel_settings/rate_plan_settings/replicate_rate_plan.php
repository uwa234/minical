<?php
$room_type_name = '';
foreach ($room_types as $room_type) {
    if ($rate_plan['room_type_id'] == $room_type['id']) {
        $room_type_name = $room_type['name'];
        break;
    }
}

$charge_type_name = '';
foreach ($charge_types as $charge_type) {
    if ($rate_plan['charge_type_id'] == $charge_type['id']) {
        $charge_type_name = $charge_type['name'];
        break;
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
        <input name="rate-plan-name" class="form-control input-sm" type="text" value="<?php echo htmlspecialchars($rate_plan['rate_plan_name'] . ' (Copy)'); ?>" readonly/>
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
                <i class="fa fa-money"></i> <?php echo l('Edit Rate', true); ?>
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
