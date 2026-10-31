<?php
$er_bulk_inputs = array(
    '1 ' . l('Adult', true) => 'adult_1_rate',
    '2 ' . l('Adults', true) => 'adult_2_rate',
    '3 ' . l('Adults', true) => 'adult_3_rate',
    '4 ' . l('Adults', true) => 'adult_4_rate',
    l('Additional Adults', true) => 'additional_adult_rate',
    l('Additional Children', true) => 'additional_child_rate',
    l('Minimum LOS', true) => 'minimum_length_of_stay',
    l('Maximum LOS', true) => 'maximum_length_of_stay',
);

$er_bulk_toggles = array(
    l('Closed to Arrival', true) => array('name' => 'closed_to_arrival', 'open' => '0', 'closed' => '1', 'open_label' => l('Open', true), 'closed_label' => l('Closed', true)),
    l('Closed to Departure', true) => array('name' => 'closed_to_departure', 'open' => '0', 'closed' => '1', 'open_label' => l('Open', true), 'closed_label' => l('Closed', true)),
);
?>
<div id="general_rates" class="tab-pane fade in active">
    <div class="modal-body er-modal-body" id="image_edit_modal_body">
        <p class="er-bulk-hint">
            <i class="fa fa-info-circle"></i>
            <?php echo l('Leave a field blank to keep existing values unchanged for the selected date range.', true); ?>
        </p>
        <div class="er-bulk-form">
            <?php foreach ($er_bulk_inputs as $label => $input_name): ?>
            <div class="er-bulk-row">
                <label class="er-bulk-label" for="<?php echo $input_name; ?>"><?php echo $label; ?></label>
                <div class="er-bulk-field">
                    <input
                        type="text"
                        name="<?php echo $input_name; ?>"
                        id="<?php echo $input_name; ?>"
                        class="form-control modifiable"
                        aria-label="<?php echo $label; ?>"
                    >
                </div>
            </div>
            <?php endforeach; ?>

            <?php foreach ($er_bulk_toggles as $label => $toggle): ?>
            <div class="er-bulk-row er-bulk-row--toggle">
                <label class="er-bulk-label" for="<?php echo $toggle['name']; ?>"><?php echo $label; ?></label>
                <div class="er-bulk-field">
                    <div class="btn-group er-bulk-toggle" data-toggle="buttons">
                        <label class="openBTN btn btn-primary active">
                            <input
                                type="radio"
                                checked
                                name="<?php echo $toggle['name']; ?>"
                                class="form-control modifiable default_radio"
                                value="<?php echo $toggle['open']; ?>"
                                autocomplete="off"
                            ><?php echo $toggle['open_label']; ?>
                        </label>
                        <label class="closeBTN btn btn-primary">
                            <input
                                type="radio"
                                name="<?php echo $toggle['name']; ?>"
                                class="form-control modifiable"
                                value="<?php echo $toggle['closed']; ?>"
                                autocomplete="off"
                            ><?php echo $toggle['closed_label']; ?>
                        </label>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="er-bulk-row er-bulk-row--toggle">
                <label class="er-bulk-label" for="can_be_sold_online"><?php echo l('Can be sold online', true); ?></label>
                <div class="er-bulk-field">
                    <div class="btn-group er-bulk-toggle" data-toggle="buttons">
                        <label class="openBTN btn btn-primary active">
                            <input
                                type="radio"
                                checked
                                name="can_be_sold_online"
                                class="form-control modifiable default_radio"
                                value="1"
                                autocomplete="off"
                            ><?php echo l('Yes', true); ?>
                        </label>
                        <label class="closeBTN btn btn-primary">
                            <input
                                type="radio"
                                name="can_be_sold_online"
                                class="form-control modifiable"
                                value="0"
                                autocomplete="off"
                            ><?php echo l('No', true); ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
