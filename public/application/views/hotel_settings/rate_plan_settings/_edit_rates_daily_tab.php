<?php
$er_days = array(
    'mon' => l('Mon', true),
    'tue' => l('Tue', true),
    'wed' => l('Wed', true),
    'thu' => l('Thu', true),
    'fri' => l('Fri', true),
    'sat' => l('Sat', true),
    'sun' => l('Sun', true),
);

$er_daily_inputs = array(
    '1 ' . l('Adult', true) => 'adult_1_rate',
    '2 ' . l('Adults', true) => 'adult_2_rate',
    '3 ' . l('Adults', true) => 'adult_3_rate',
    '4 ' . l('Adults', true) => 'adult_4_rate',
    l('Additional Adults', true) => 'additional_adult_rate',
    l('Additional Children', true) => 'additional_child_rate',
    l('Minimum LOS', true) => 'minimum_length_of_stay',
    l('Maximum LOS', true) => 'maximum_length_of_stay',
);

$er_daily_toggles = array(
    l('Closed to Arrival', true) => array('name' => 'closed_to_arrival', 'open' => '0', 'closed' => '1', 'open_label' => l('Open', true), 'closed_label' => l('Closed', true)),
    l('Closed to Departure', true) => array('name' => 'closed_to_departure', 'open' => '0', 'closed' => '1', 'open_label' => l('Open', true), 'closed_label' => l('Closed', true)),
    l('Can be sold online', true) => array('name' => 'can_be_sold_online', 'open' => '1', 'closed' => '0', 'open_label' => l('Yes', true), 'closed_label' => l('No', true)),
);
?>
<div id="day_specific_rates" class="tab-pane fade">
    <div class="modal-body er-modal-body" id="image_edit_modal_body2">
        <div class="er-daily-grid-wrap">
            <p class="er-daily-hint">
                <i class="fa fa-info-circle"></i>
                <?php echo l('Set different values for each day of the week. Use the arrow on Monday to copy that value to all days.', true); ?>
            </p>
            <div class="er-daily-grid-scroll">
                <table class="er-daily-grid">
                    <thead>
                        <tr>
                            <th class="er-daily-th-metric"><?php echo l('Metric', true); ?></th>
                            <?php foreach ($er_days as $day_key => $day_label): ?>
                            <th class="er-daily-th-day<?php echo ($day_key === 'sat' || $day_key === 'sun') ? ' er-daily-th-weekend' : ''; ?>">
                                <?php echo $day_label; ?>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($er_daily_inputs as $label => $input_name): ?>
                        <tr>
                            <td class="er-daily-td-metric"><?php echo $label; ?></td>
                            <?php foreach ($er_days as $day_key => $day_label): ?>
                            <td class="er-daily-td-input">
                                <div class="input-group input-group-sm er-daily-input-group">
                                    <input
                                        type="text"
                                        name="<?php echo $input_name . '_' . $day_key; ?>"
                                        id="<?php echo $input_name . '_' . $day_key; ?>"
                                        class="form-control modifiable"
                                        aria-label="<?php echo $label . ' ' . $day_label; ?>"
                                    >
                                    <?php if ($day_key === 'mon'): ?>
                                    <span class="input-group-btn">
                                        <input type="checkbox" name="<?php echo $input_name; ?>_all" id="all_<?php echo $input_name; ?>" class="copy_to_all" style="display:none">
                                        <label class="btn btn-default er-copy-btn" for="all_<?php echo $input_name; ?>" title="<?php echo l('Copy to all', true); ?>">
                                            <i class="fa fa-arrow-right" aria-hidden="true"></i>
                                        </label>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>

                        <?php foreach ($er_daily_toggles as $label => $toggle): ?>
                        <tr class="er-daily-row-toggle">
                            <td class="er-daily-td-metric"><?php echo $label; ?></td>
                            <?php foreach ($er_days as $day_key => $day_label):
                                $field_name = $toggle['name'] . '_' . $day_key;
                            ?>
                            <td class="er-daily-td-toggle">
                                <div class="btn-group er-daily-toggle" data-toggle="buttons">
                                    <label class="openBTN btn btn-primary active">
                                        <input
                                            type="radio"
                                            checked
                                            name="<?php echo $field_name; ?>"
                                            id="<?php echo $field_name; ?>_1"
                                            class="form-control modifiable default_radio"
                                            value="<?php echo $toggle['open']; ?>"
                                            autocomplete="off"
                                        ><?php echo $toggle['open_label']; ?>
                                    </label>
                                    <label class="closeBTN btn btn-primary">
                                        <input
                                            type="radio"
                                            name="<?php echo $field_name; ?>"
                                            id="<?php echo $field_name; ?>_2"
                                            class="form-control modifiable"
                                            value="<?php echo $toggle['closed']; ?>"
                                            autocomplete="off"
                                        ><?php echo $toggle['closed_label']; ?>
                                    </label>
                                </div>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
