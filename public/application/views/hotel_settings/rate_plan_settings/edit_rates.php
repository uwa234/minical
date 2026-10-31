<div class="er-page">

<input name="tab_identification" id="tab_identification" type="hidden" value="1" />
<input name="room_type_id" type="hidden" value="<?php if (!empty($room_type_id)) echo $room_type_id; ?>" />
<input name="rate_plan_id" value="<?php echo $rate_plan_id; ?>" hidden/>
<input name="today" type="hidden" value="<?php echo $today; ?>" />

<header class="er-header">
    <div class="er-header-main">
        <a href="<?php echo base_url() . 'settings/rates/rate_plans#' . $rate_plan_id; ?>" class="er-back-link" title="<?php echo l('Back', true); ?>">
            <i class="fa fa-arrow-left"></i>
        </a>
        <div class="er-header-icon" aria-hidden="true">
            <i class="fa fa-calendar"></i>
        </div>
        <div>
            <h1 class="er-title"><?php echo l('Edit Rates for', true); ?> <?php echo htmlspecialchars($rate_plan_name); ?></h1>
            <p class="er-subtitle">
                <?php echo l('Manage daily pricing, restrictions, and availability for', true); ?>
                <a href="<?php echo base_url() . 'settings/rates/rate_plans#' . $rate_plan_id; ?>"><?php echo htmlspecialchars($room_type['name']); ?></a>.
            </p>
            <div class="er-header-meta">
                <span class="er-chip er-chip--plan"><i class="fa fa-tag"></i> <?php echo htmlspecialchars($rate_plan_name); ?></span>
                <span class="er-chip er-chip--room"><i class="fa fa-bed"></i> <?php echo htmlspecialchars($room_type['name']); ?></span>
            </div>
        </div>
    </div>
</header>

<!-- Rate edit modal -->
<div class="modal fade rate-data er-modal" id="create_rate_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel"><i class="fa fa-pencil-square-o"></i> <?php echo l('Edit Rate', true); ?></h4>
            </div>

            <div class="er-date-range">
                <div class="er-date-range-inner">
                    <span class="er-date-range-label"><?php echo l('Modify rates between', true); ?></span>
                    <input name="date_start" id="date_start" class="date form-control" value="<?php echo $today; ?>" placeholder="<?php echo l('Start Date', true); ?>">
                    <span class="er-date-range-label"><?php echo l('and', true); ?></span>
                    <?php $end_placeholder = l('Forever', true); ?>
                    <input name="date_end" id="date_end" class="date form-control" value="" placeholder="<?php echo $end_placeholder; ?>">
                </div>
            </div>

            <div class="er-modal-tabs">
                <ul class="nav nav-tabs">
                    <li class="active"><a href="#general_rates" id="general_rates_tab"><?php echo l('Bulk Update', true); ?></a></li>
                    <li><a href="#day_specific_rates" id="day_specific_rates_tab"><?php echo l('Daily Rates', true); ?></a></li>
                </ul>
            </div>
            <div class="">
                <div class="tab-content">
                    <?php $this->load->view('hotel_settings/rate_plan_settings/_edit_rates_bulk_tab'); ?>
                    <?php $this->load->view('hotel_settings/rate_plan_settings/_edit_rates_daily_tab'); ?>
                </div>
                </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo l('Close', true); ?></button>
                <button class="btn btn-primary" id="modify_rates_button"><i class="fa fa-check"></i> <?php echo l('Save', true); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Supplied rate modal -->
<div class="modal fade rate-data er-modal" id="create_supplied_rate_modal" tabindex="-1" role="dialog" aria-labelledby="suppliedModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="suppliedModalLabel"><i class="fa fa-exchange"></i> <?php echo l('Edit Supplied Rate', true); ?></h4>
            </div>

            <div class="er-date-range">
                <div class="er-date-range-inner">
                    <span class="er-date-range-label"><?php echo l('Modify supplied rates between ', true); ?></span>
                    <input name="supplied_rate_date_start" id="supplied_rate_date_start" class="date form-control" value="<?php echo $today; ?>">
                    <span class="er-date-range-label"><?php echo l('and', true); ?></span>
                    <input name="supplied_rate_date_end" id="supplied_rate_date_end" class="date form-control" value="">
                </div>
            </div>

            <div class="modal-body er-modal-body" id="image_edit_modal_body1">
                <div class='form-horizontal m-121'>
                    <label style="padding-top: 30px;" for="" class="col-sm-3">

                    </label>
                    <?php
                    $input_names = Array(
                        '1 '.l('Adult') => 'supplied_adult_1_rate',
                        '2 '.l('Adults') => 'supplied_adult_2_rate',
                        '3 '.l('Adults') => 'supplied_adult_3_rate',
                        '4 '.l('Adults') => 'supplied_adult_4_rate',
                    );

                    $index = 1;
                    foreach ($input_names as $label => $input_name):
                        ?>
<!--                        <div class="form-group">
                            <label style="padding-top: 30px;" for="<?php echo $input_name; ?>" class="col-sm-2 control-label">
    <?php echo $label; ?>
                            </label>
                            <div class="col-sm-10">
                                <div class="input-group input-group-sm">
                                    <div class="col-sm-12 no-padding">
                                        <div class="col-sm-1">
                                            <label> Mon </label>
                                            <input type="text" name="<?php echo $input_name; ?>_mon" id="<?php echo $input_name; ?>_mon" class="form-control modifiable" autocomplete="off">
                                        </div>
                                        <div class="col-sm-1">
                                            <label> Tue </label>
                                            <input type="text" name="<?php echo $input_name; ?>_tue" id="<?php echo $input_name; ?>_tue" class="form-control modifiable" autocomplete="off">
                                        </div>
                                        <div class="col-sm-1">
                                            <label> Wed </label>
                                            <input type="text" name="<?php echo $input_name; ?>_wed" id="<?php echo $input_name; ?>_wed" class="form-control modifiable" autocomplete="off">
                                        </div>
                                        <div class="col-sm-1">
                                            <label> Thu </label>
                                            <input type="text" name="<?php echo $input_name; ?>_thu" id="<?php echo $input_name; ?>_thu" class="form-control modifiable" autocomplete="off">
                                        </div>
                                        <div class="col-sm-1">
                                            <label> Fri </label>
                                            <input type="text" name="<?php echo $input_name; ?>_fri" id="<?php echo $input_name; ?>_fri" class="form-control modifiable" autocomplete="off">
                                        </div>
                                        <div class="col-sm-1">
                                            <label> Sat </label>
                                            <input type="text" name="<?php echo $input_name; ?>_sat" id="<?php echo $input_name; ?>_sat" class="form-control modifiable" autocomplete="off">
                                        </div>
                                        <div class="col-sm-1">
                                            <label> Sun </label>
                                            <input type="text" name="<?php echo $input_name; ?>_sun" id="<?php echo $input_name; ?>_sun" class="form-control modifiable" autocomplete="off">
                                        </div>
                                        <div class="col-sm-2 no-padding">
                                            <div style="width:100%;padding: 6px 5px; margin-top: 22px;" class="custom-input-addon">
                                                <input type=checkbox name='<?php echo $input_name; ?>_all' id='all_<?php echo $input_name; ?>'> Copy to all 
                                            </div>
                                        </div>
                                        <div class="col-sm-2">
                                            <div class="rightMarginApplycanges" style="" class="custom-input-addon">
                                                <input type=checkbox name='<?php echo $input_name; ?>_modified' class="modified"> Apply changes
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>-->
<div class="form-group">
                                            
                                            <div class="col-sm-12">

                                                <div class="row seven-cols">

                                                    <!----start-7-colum------>

                                                    <!---End-7-colum--------->

                                                    <div class="input-group input-group-sm">
                                                        
                                                        <div class="col-sm-12 no-padding">
                                                            <?php if ($index == 1) { ?>
                                                                <div class="row">
                                                                    <div class="col-xs-6">
                                                                        <div class="row">
                                                                            <div class="col-xs-3">

                                                                            </div>
                                                                            <div class="col-xs-3">
                                                                                <label> <?php echo l('Mon', true); ?> </label>
                                                                            </div>
                                                                            <div class="col-xs-3">
                                                                                <label> <?php echo l('Tue', true); ?> </label>
                                                                            </div>
                                                                            <div class="col-xs-3">
                                                                                <label> <?php echo l('Wed', true); ?> </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-xs-6">
                                                                        <div class="row">
                                                                            <div class="col-xs-3">
                                                                                <label> <?php echo l('Thu', true); ?> </label>
                                                                            </div>
                                                                            <div class="col-xs-3">
                                                                                <label> <?php echo l('Fri', true); ?> </label>
                                                                            </div>
                                                                            <div class="col-xs-3">
                                                                                <label> <?php echo l('Sat', true); ?> </label>
                                                                            </div>
                                                                            <div class="col-xs-3">
                                                                                <label> <?php echo l('Sun', true); ?> </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div><br/>
                                                            <?php } ?>
                                                            <div class="row text-center">
                                                                <div class="col-xs-6">
                                                                    <div class="row">
                                                                        <div class="col-xs-3 text-right">
                                                                            <label style="padding-top: 5px;" for="<?php echo $input_name; ?>" class="">
                                                                                <?php echo $label; ?>
                                                                            </label><br/>
                                                                        </div>
                                                                        <div class="col-xs-3">
                                                                            <div class="input-group">
                                                                                <input type="text" name="<?php echo $input_name; ?>_mon" id="<?php echo $input_name; ?>_mon" class="form-control modifiable" aria-label="...">


                                                                                <div class="input-group-btn">
                                                                                    
                                                                                    <!--<label class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" ><span class="caret"></span></label>-->
                                                                                    <!--<ul class="dropdown-menu dropdown-menu-right normalTextlabel">-->
<!--                                                                                        <li><a><input style="display: none;" type="radio" name="<?php echo $input_name; ?>_apply_change_mon" id="apply_change_mon_1_<?php echo $input_name; ?>" value="1" class="modifiable_2"><label for="apply_change_mon_1_<?php echo $input_name; ?>">Null value</label></a></li>-->
                                                                                        <!--<li><a><input style="display: none;" type="radio" name="<?php echo $input_name; ?>_apply_change_mon" id="apply_change_mon_2_<?php echo $input_name; ?>" value="2" class="modifiable_2"><label for="apply_change_mon_2_<?php echo $input_name; ?>" >Do not change</label></a></li>-->
                                                                                        <!--<li><a><input style="display: none;" type=checkbox name='<?php echo $input_name; ?>_all' id='all_<?php echo $input_name; ?>' class="copy_to_all" ><label for="all_<?php echo $input_name; ?>">Copy to all</label></a></li>-->
                                                                                    
 <span class="input-group-btn">
     <input style="display: none;" type=checkbox name='<?php echo $input_name; ?>_all' id='all_<?php echo $input_name; ?>' class="copy_to_all">
     <label class="btn btn-default" for="all_<?php echo $input_name; ?>"><span title="Copy to all"><i class="fa fa-caret-right" aria-hidden="true"></i></span>
</label>
        
 </span>                                                                                       
                                                                                        
                                                                                    <!--</ul>-->
                                                                                </div><!-- /btn-group -->
                                                                            </div><!-- /input-group -->
                                                                            <br/><br/>
                                                                        </div>
                                                                        <div class="col-xs-3">
                                                                            <div class="input-group">
                                                                                <input type="text" name="<?php echo $input_name; ?>_tue" id="<?php echo $input_name; ?>_tue" class="form-control modifiable" aria-label="...">
                                                                                <div class="input-group-btn">
                                                                                    <!--<label class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" ><span class="caret"></span></label>-->
                                                                                    <!--<ul class="dropdown-menu dropdown-menu-right normalTextlabel">-->
<!--                                                                                        <li><a><input style="display: none;" type="radio" name="<?php echo $input_name; ?>_apply_change_tue" id="apply_change_tue_1_<?php echo $input_name; ?>" value="1" class="modifiable_2"><label for="apply_change_tue_1_<?php echo $input_name; ?>">Null value</label></a></li>-->
                                                                                        <!--<li><a><input style="display: none;" type="radio" name="<?php echo $input_name; ?>_apply_change_tue" id="apply_change_tue_2_<?php echo $input_name; ?>" value="2" class="modifiable_2"><label for="apply_change_tue_2_<?php echo $input_name; ?>">Do not change</label></a></li>-->

                                                                                    <!--</ul>-->
                                                                                </div><!-- /btn-group -->
                                                                            </div><!-- /input-group -->
                                                                            <br/><br/>
                                                                        </div>
                                                                        <div class="col-xs-3">
                                                                            <div class="input-group">
                                                                                <input type="text" name="<?php echo $input_name; ?>_wed" id="<?php echo $input_name; ?>_wed" class="form-control modifiable" aria-label="...">
                                                                                <div class="input-group-btn">
                                                                                    <!--<label class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="caret"></span></label>-->
                                                                                    <!--<ul class="dropdown-menu dropdown-menu-right normalTextlabel">-->
<!--                                                                                        <li><a><input style="display: none;" type="radio" name="<?php echo $input_name; ?>_apply_change_wed" id="apply_change_wed_1_<?php echo $input_name; ?>" value="1" class="modifiable_2"><label for="apply_change_wed_1_<?php echo $input_name; ?>">Null value</label></a></li>-->
                                                                                        <!--<li><a><input style="display: none;" type="radio" name="<?php echo $input_name; ?>_apply_change_wed" id="apply_change_wed_2_<?php echo $input_name; ?>" value="2" class="modifiable_2"><label for="apply_change_wed_2_<?php echo $input_name; ?>">Do not change</label></a></li>-->

                                                                                    <!--</ul>-->
                                                                                </div><!-- /btn-group -->
                                                                            </div><!-- /input-group -->
                                                                            <br/><br/>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-xs-6">
                                                                    <div class="row">
                                                                        <div class="col-xs-3">
                                                                            <div class="input-group">
                                                                                <input type="text" name="<?php echo $input_name; ?>_thu" id="<?php echo $input_name; ?>_thu" class="form-control modifiable" aria-label="...">
                                                                                <div class="input-group-btn">
                                                                                    <!--<label class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="caret"></span></label>-->
                                                                                    <!--<ul class="dropdown-menu dropdown-menu-right normalTextlabel">-->
<!--                                                                                        <li><a><input style="display: none;" type="radio" name="<?php echo $input_name; ?>_apply_change_thu" id="apply_change_thu_1_<?php echo $input_name; ?>" value="1" class="modifiable_2"><label for="apply_change_thu_1_<?php echo $input_name; ?>">Null value</label></a></li>-->
                                                                                        <!--<li><a><input style="display: none;" type="radio" name="<?php echo $input_name; ?>_apply_change_thu" id="apply_change_thu_2_<?php echo $input_name; ?>" value="2" class="modifiable_2"><label for="apply_change_thu_2_<?php echo $input_name; ?>">Do not change</label></a></li>-->

                                                                                    <!--</ul>-->
                                                                                </div><!-- /btn-group -->
                                                                            </div><!-- /input-group -->
                                                                            <br/><br/>
                                                                        </div>
                                                                        <div class="col-xs-3">
                                                                            <div class="input-group">
                                                                                <input type="text" name="<?php echo $input_name; ?>_fri" id="<?php echo $input_name; ?>_fri" class="form-control modifiable" aria-label="...">
                                                                                <div class="input-group-btn">
                                                                                    <!--<label class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="caret"></span></label>-->
                                                                                    <!--<ul class="dropdown-menu dropdown-menu-right normalTextlabel">-->
<!--                                                                                        <li><a><input style="display: none;" type="radio" name="<?php echo $input_name; ?>_apply_change_fri" id="apply_change_fri_1_<?php echo $input_name; ?>" value="1" class="modifiable_2"><label for="apply_change_fri_1_<?php echo $input_name; ?>">Null value</label></a></li>-->
                                                                                        <!--<li><a><input style="display: none;" type="radio" name="<?php echo $input_name; ?>_apply_change_fri" id="apply_change_fri_2_<?php echo $input_name; ?>" value="2" class="modifiable_2"><label for="apply_change_fri_2_<?php echo $input_name; ?>">Do not change</label></a></li>-->

                                                                                    <!--</ul>-->
                                                                                </div><!-- /btn-group -->
                                                                            </div><!-- /input-group -->
                                                                            <br/><br/>
                                                                        </div>
                                                                        <div class="col-xs-3">
                                                                            <div class="input-group">
                                                                                <input type="text" name="<?php echo $input_name; ?>_sat" id="<?php echo $input_name; ?>_sat" class="form-control modifiable" aria-label="...">
                                                                                <div class="input-group-btn">
                                                                                    <!--<label class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="caret"></span></label>-->
                                                                                    <!--<ul class="dropdown-menu dropdown-menu-right normalTextlabel">-->
<!--                                                                                        <li><a><input style="display: none;" type="radio" name="<?php echo $input_name; ?>_apply_change_sat" id="apply_change_sat_1_<?php echo $input_name; ?>" value="1" class="modifiable_2"><label for="apply_change_sat_1_<?php echo $input_name; ?>">Null value</label></a></li>-->
                                                                                        <!--<li><a><input style="display: none;" type="radio" name="<?php echo $input_name; ?>_apply_change_sat" id="apply_change_sat_2_<?php echo $input_name; ?>" value="2" class="modifiable_2"><label for="apply_change_sat_2_<?php echo $input_name; ?>">Do not change</label></a></li>-->

                                                                                    <!--</ul>-->
                                                                                </div><!-- /btn-group -->
                                                                            </div><!-- /input-group -->
                                                                            <br/><br/>
                                                                        </div>
                                                                        <div class="col-xs-3">
                                                                            <div class="input-group">
                                                                                <input type="text" name="<?php echo $input_name; ?>_sun" id="<?php echo $input_name; ?>_sun" class="form-control modifiable" aria-label="...">
                                                                                <div class="input-group-btn">
                                                                                    <!--<label class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="caret"></span></label>-->
                                                                                    <!--<ul class="dropdown-menu dropdown-menu-right normalTextlabel">-->
<!--                                                                                        <li><a><input style="display: none;" type="radio" name="<?php echo $input_name; ?>_apply_change_sun" id="apply_change_sun_1_<?php echo $input_name; ?>" value="1" class="modifiable_2"><label for="apply_change_sun_1_<?php echo $input_name; ?>">Null value</label></a></li>-->
                                                                                        <!--<li><a><input style="display: none;" type="radio" name="<?php echo $input_name; ?>_apply_change_sun" id="apply_change_sun_2_<?php echo $input_name; ?>" value="2" class="modifiable_2"><label for="apply_change_sun_2_<?php echo $input_name; ?>">Do not change</label></a></li>-->

                                                                                    <!--</ul>-->
                                                                                </div><!-- /btn-group -->
                                                                            </div><!-- /input-group -->
                                                                            <br/><br/>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
<br/>
                        <?php
                        $index = $index + 1;
                    endforeach;
                    ?>

                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo l('Close', true); ?></button>
                <button class="btn btn-primary" id="modify_supplied_rates_button"><i class="fa fa-check"></i> <?php echo l('Save', true); ?></button>
            </div>
        </div>
    </div>
</div>

<div class="er-toolbar-card">
    <div class="er-toolbar-actions">
        <button id="modify-rates" class="btn btn-primary">
            <i class="fa fa-pencil"></i> <?php echo l('Modify Rates', true); ?>
        </button>
        <?php if ($this->session->userdata('user_role') == "is_admin"): ?>
        <button id="modify-supplied-rates" class="btn btn-outline">
            <i class="fa fa-exchange"></i> <?php echo l('Modify Supplied Rates', true); ?>
        </button>
        <?php endif; ?>
    </div>
    <div class="er-nav-group">
        <button type="button" class="er-nav-btn" id="show-previous-month">
            <i class="fa fa-chevron-left"></i> <?php echo l('Previous 4 weeks', true); ?>
        </button>
        <span class="er-nav-label" id="er-date-range-label"><?php echo l('4-week view', true); ?></span>
        <button type="button" class="er-nav-btn" id="show-next-month">
            <?php echo l('Next 4 weeks', true); ?> <i class="fa fa-chevron-right"></i>
        </button>
    </div>
</div>

<div class="er-calendar-card">
    <div class="er-calendar-head">
        <div>
            <h2 class="er-calendar-title"><?php echo l('Rate calendar', true); ?></h2>
            <p class="er-calendar-hint"><?php echo l('Scroll horizontally to view all dates. Supplied rates appear in parentheses for admins.', true); ?></p>
        </div>
    </div>
    <div class="er-calendar-body" id="er-calendar-body">
        <table id="rate-detail-table" class="er-rate-table"></table>
    </div>
</div>

</div>
