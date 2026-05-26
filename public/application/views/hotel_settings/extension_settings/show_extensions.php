<div class="extensions-page mc-page">
    <header class="extensions-header">
        <div class="extensions-header-inner">
            <div class="extensions-header-text">
                <div class="extensions-header-icon" aria-hidden="true">
                    <i class="fa fa-puzzle-piece"></i>
                </div>
                <div>
                    <h1 class="extensions-title"><?php echo l('Extensions'); ?></h1>
                    <p class="extensions-subtitle">Enable integrations, channel managers, payment gateways, and more.</p>
                </div>
            </div>
        </div>
    </header>

    <?php if((isset($is_vendor[0]) && $this->user_permission == 'is_admin') || $this->is_super_admin == 1){ ?>
        <nav class="extensions-nav" aria-label="<?php echo l('Extensions', true); ?>">
            <a class="<?php if($this->uri->segment(2) == '') echo 'active'; ?>" href="<?php echo base_url().'extensions'?>"><?php echo l('Installed Extensions', true); ?></a>
            <a class="<?php if($this->uri->segment(2) == 'show_vendors_extensions') echo 'active'; ?>" href="<?php echo base_url().'extensions/show_vendors_extensions'?>"><?php echo l('All Extensions', true); ?></a>
        </nav>
    <?php } ?>

    <div class="extensions-toolbar">
        <div class="extensions-toolbar-filters extension-filter">
            <label for="status"><?php echo l('status');?></label>
            <select name="status" id="status" class="form-control" onchange="getval(this);">
                <option value="all"><?php echo l('All');?></option>
                <option value="active"><?php echo l('Active');?></option>
                <option value="not_active"><?php echo l('not-Active');?></option>
            </select>
            <label for="category"><?php echo l('categories');?></label>
            <select name="category" id="category" class="form-control" onchange="getcat(this);">
                <option value="all"><?php echo l('All');?></option>
                <option value="channel_manager"><?php echo l('Channel Manager');?></option>
                <option value="check_in_automation"><?php echo l('Check-in Automation');?></option>
                <option value="marketing"><?php echo l('Marketing');?></option>
                <option value="online_booking_engine"><?php echo l('Online Booking Engine');?></option>
                <option value="payment_process"><?php echo l('Payment Process');?></option>
                <option value="pos"><?php echo l('POS');?></option>
                <option value="promo_discount"><?php echo l('Promo/Discount');?></option>
                <option value="yield_management"><?php echo l('Yield Management');?></option>
            </select>
            <input type="text" name="search_pos" placeholder="<?php echo l('Search Extensions', true); ?>" class="form-control search_ext">
        </div>
    </div>


<?php $is_favourite = false; 
if(isset($extensions) && $extensions){ 
    foreach ($extensions as $extension){
        if(isset($extension['is_favourite']) && $extension['is_favourite']){
            $is_favourite = true;
        }
    }
} 
if($is_favourite) { ?>
    <section class="extensions-favourites">
    <h2 class="extensions-section-title"><i class="fa fa-heart" aria-hidden="true"></i> <?php echo l('Favourites');?></h2>
<?php } ?>
<div class="extensions-card-panel <?php echo $is_favourite ? 'extensions-favourites-panel' : ''; ?>">
    <div class="extension-card">

        <?php
        //Columns must be a factor of 12 (1,2,3,4,6,12)
        $numOfCols = 3;
        $rowCount = 0;
        $bootstrapColWidth = 12 / $numOfCols;
        ?>
        <div class="row">
            <?php if(isset($extensions) && $extensions) :
                foreach ($extensions as $extension){
                    if(isset($extension['is_favourite']) && $extension['is_favourite']){
                        ?>
                        <div class="col-md-<?php echo $bootstrapColWidth; ?>" style="padding-right: 0px">
                            <div class="extension_block">
                                <div class="main-extension">
                                    <div class="icon">
                                        <img src="<?php  if(isset($extension['logo']) && $extension['logo']){
                                            if(strpos($extension['logo'], 'http')  !== false){
                                                echo $extension['logo'];
                                            } else{
                                                echo base_url().'application/extensions/'.$extension['extension_folder_name'].'/'.$extension['logo'];
                                            }
                                        
                                } elseif(isset($extension['image_name']) && $extension['image_name']){
                                    if(strpos($extension['image_name'], 'http')  !== false){
                                        echo $extension['image_name'];
                                    } else {
                                        echo base_url().'/images/'. $extension['image_name'];
                                    }
                                    
                                } else {
                                    echo '';
                                } ?>" style="width: 30px;height: 30px">
                                    </div>
                                    <div class="extension-content">
                                        <b style="font-size: 13px;">
                                            <a href="<?php echo (isset($extension['marketplace_product_link']) && $extension['marketplace_product_link'] && $this->is_partner_owner == 1 ? $extension['marketplace_product_link'] : "javascript:")?>">
                                                <?php
                                            $name = $extension['extension_name'];
                                            $extension_name = str_replace("_"," ",$name);
                                            echo ucwords(l($extension_name, true)); ?>
                                            </a>
                                            <span>
                                                <?php if(isset($extension['is_favourite']) && $extension['is_favourite'] == 1){?>
                                                    <a href="javascript:" data-value="0" class="fa fa-heart pull-right favourite-button" name="<?php echo $extension['extension_folder_name']; ?>" style="font-size: 15px;background-color: white;border: none;color:red; "></a>
                                                <?php }else{?>
                                                    <a href="javascript:" data-value= "1" class="fa fa-heart-o pull-right favourite-button" name="<?php echo $extension['extension_folder_name']; ?>" style="font-size: 15px;background-color: white;border: none; color: grey; "></a>
                                                <?php } ?>
                                            </span>
                                        </b>

                                        <div>
                                            <?php if(isset($extension['is_admin_module']) && $extension['is_admin_module']){ ?>
                                                <span style="font-size: 11px;color: gray;font-weight: 500;padding: 0px 0 5px;">VENDOR ONLY</span>
                                            <?php } ?>


                                            <?php if((isset($extension['supported_in_minimal']) && $extension['supported_in_minimal'] == 1) || $this->company_subscription_level == 1) { ?>
                                            <?php } else { ?>
                                                <span style="font-size: 10px;color: red;font-weight: 600;padding: 0px 0 5px;">PREMIUM EXTENSION</span>
                                            <?php } ?>


                                            <p class="extension-discription" ><?php echo  strlen($extension['description']) > 200 ? substr($extension['description'],0,200)."..." : $extension['description']; ?>
                                                <?php if(isset($extension['marketplace_product_link']) && $extension['marketplace_product_link'] && $this->is_partner_owner == 1){ ?>
                                                    <!-- <a href="<?php echo (isset($extension['marketplace_product_link']) && $extension['marketplace_product_link'] ? $extension['marketplace_product_link']: "")?>" style="font-size: 14px"><?php //echo l('more');?></a> -->
                                                <?php } ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="features-div-padding">
                                    <div class="checkbox checbox-switch switch-primary" style="margin-bottom: 5px;margin-top: 5px">
                                        <a href="<?php if(isset( $extension['setting_link']) && $extension['setting_link'] ){
                                            echo $extension['setting_link'];
                                        } else {
                                            echo '';}?>"
                                           class="ml-4"
                                           style="font-size: 25px;"
                                           name="<?php echo $extension['extension_folder_name']; ?>"
                                           data-status="<?php echo $extension['is_active']; ?>">

                                            <?php if($extension['is_active'] == 1 && $extension['setting_link'] !=null){
                                                echo '<i class="pe-7s-config text-primary"></i>';
                                            }else{
                                                echo '';
                                            } ?>
                                        </a>

                                        <a href="<?php if(isset( $extension['view_link']) && $extension['view_link'] ){
                                            echo $extension['view_link'];
                                        } else {
                                            echo '';}?>"
                                           class=""
                                           style="font-size: 25px;"
                                           name="<?php echo $extension['extension_folder_name']; ?>"
                                           data-status="<?php echo $extension['is_active']; ?>">

                                            <?php if($extension['is_active'] == 1 && $extension['view_link'] !=null){
                                                echo '<i class="pe-7s-look  text-primary"></i>';
                                            }?>
                                        </a>
                                        <label class="extension-box" style="padding-right: 1.5rem !important;">
                                            <input type="checkbox" class="extension-status-button" data-status="<?php echo $extension['is_active']; ?>" name="<?php echo $extension['extension_folder_name']; ?>"
                                                <?= $extension['is_active'] ? 'checked=checked' : ''; ?> <?php if((isset($extension['supported_in_minimal']) && $extension['supported_in_minimal'] == 1) || $this->company_subscription_level == 1) {} else { echo "disabled"; } ?>/>
                                            <?php if($this->user_permission != 'is_employee'){ ?>
                                                <?php if((isset($extension['supported_in_minimal']) && $extension['supported_in_minimal'] == 1) || $this->company_subscription_level == 1) { ?>
                                                    <?php if($extension['extension_name'] != 'Vendor Core Features' && $extension['extension_name'] != 'Subscription' && $extension['extension_name'] != 'Vendor monthly report') { ?>
                                                        <span></span>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <span class="premium_extension" style="background-color: darkgrey;"></span>
                                                <?php } ?>
                                            <?php } ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                        $rowCount++;
                        if($rowCount % $numOfCols == 0) echo '</div><div class="row">';
                    }}
                ?>
            <?php else : ?>
                <!-- <h3><?php echo l('No extensions have been found.', true); ?></h3> -->
            <?php endif; ?>
        </div>
    </div>
</div>
<?php if($is_favourite) { ?></section><?php } ?>
<?php if($is_favourite) { ?>
<h2 class="extensions-section-title"><?php echo l('Extensions');?></h2>
<?php } ?>


<div class="extensions-card-panel">
    <div class="extension-card">

        <?php
        //Columns must be a factor of 12 (1,2,3,4,6,12)
        $numOfCols = 3;
        $rowCount = 0;
        $bootstrapColWidth = 12 / $numOfCols;
        ?>
        <div class="extension_view" >
            <div class="row">
                <?php if(isset($extensions) && $extensions) :
                    foreach ($extensions as $extension){
                        ?>
                        <div class="col-md-<?php echo $bootstrapColWidth; ?>" style="padding-right: 0px">
                            <div class="extension_block">
                                <div class="main-extension">
                                    <div class="icon">
                                        <img src="<?php  if(isset($extension['logo']) && $extension['logo']){
                                            if(strpos($extension['logo'], 'http')  !== false){
                                                echo $extension['logo'];
                                            } else{
                                                echo base_url().'application/extensions/'.$extension['extension_folder_name'].'/'.$extension['logo'];
                                            }
                                        
                                } elseif(isset($extension['image_name']) && $extension['image_name']){
                                    if(strpos($extension['image_name'], 'http')  !== false){
                                        echo $extension['image_name'];
                                    } else {
                                        echo base_url().'/images/'. $extension['image_name'];
                                    }
                                    
                                } else {
                                    echo '';
                                } ?>" style="width: 30px;height: 30px">
                                    </div>
                                    <div class="extension-content">
                                        <b style="font-size: 13px;">
                                            <a href="<?php echo (isset($extension['marketplace_product_link']) && $extension['marketplace_product_link'] && $this->is_partner_owner == 1 ? $extension['marketplace_product_link']: "javascript:")?>" style="font-size: 14px">
                                                <?php
                                            $name = $extension['extension_name'];
                                            $extension_name = str_replace("_"," ",$name);
                                            echo ucwords(l($extension_name, true)); ?>
                                            </a>
                                            <span>
                                                <?php if(isset($extension['is_favourite']) && $extension['is_favourite'] == 1){?>
                                                    <a href="javascript:" data-value="0" class="fa fa-heart pull-right favourite-button" name="<?php echo $extension['extension_folder_name']; ?>" style="font-size: 15px;background-color: white;border: none;color:red; "></a>
                                                <?php }else{?>
                                                    <a href="javascript:" data-value= "1" class="fa fa-heart-o pull-right favourite-button" name="<?php echo $extension['extension_folder_name']; ?>" style="font-size: 15px;background-color: white;border: none; color: grey; "></a>
                                                <?php } ?>
                                            </span>
                                        </b>

                                        <div>
                                            <?php if(isset($extension['is_admin_module']) && $extension['is_admin_module']){ ?>
                                                <span style="font-size: 11px;color: gray;font-weight: 500;padding: 0px 0 5px;">VENDOR ONLY</span>
                                            <?php } ?>

                                            <?php if((isset($extension['supported_in_minimal']) && $extension['supported_in_minimal'] == 1) || $this->company_subscription_level == 1) { ?>
                                            <?php } else { ?>
                                                <span style="font-size: 10px;color: red;font-weight: 600;padding: 0px 0 5px;">PREMIUM EXTENSION</span>
                                            <?php } ?>

                                            <p class="extension-discription" style= "margin-bottom: 0px"><?php echo 

                                                strlen($extension['description']) > 150 ? substr($extension['description'],0,150)."..." : $extension['description']; ?>
                                                <?php if(isset($extension['marketplace_product_link']) && $extension['marketplace_product_link'] && $this->is_partner_owner == 1){ ?>
                                                    <!-- <a href="<?php echo (isset($extension['marketplace_product_link']) && $extension['marketplace_product_link'] ? $extension['marketplace_product_link']: "")?>" style="font-size: 14px"><?php //echo l('more');?></a> -->
                                                <?php }?>
                                            </p>
                                        </div>

                                    </div>
                                </div>

                                <div class="features-div-padding">

                                    <div class="checkbox checbox-switch switch-primary" style="margin-bottom: 5px;margin-top: 5px">
                                        <a href="<?php if(isset( $extension['setting_link']) && $extension['setting_link'] ){
                                            echo $extension['setting_link'];
                                        } else {
                                            echo '';}?>"
                                           class="ml-4"
                                           style="font-size: 25px;"
                                           name="<?php echo $extension['extension_folder_name']; ?>"
                                           data-status="<?php echo $extension['is_active']; ?>">

                                            <?php if($extension['is_active'] == 1 && $extension['setting_link'] !=null && $this->user_permission != 'is_employee'){
                                                echo '<i class="pe-7s-config text-primary"></i>';
                                            } else {
                                                echo '';
                                            } ?>
                                        </a>

                                        <a href="<?php if(isset( $extension['view_link']) && $extension['view_link'] ){
                                            echo $extension['view_link'];
                                        } else {
                                            echo '';}?>"
                                           class=""
                                           style="font-size: 25px;"
                                           name="<?php echo $extension['extension_folder_name']; ?>"
                                           data-status="<?php echo $extension['is_active']; ?>">

                                            <?php if($extension['is_active'] == 1 && $extension['view_link'] !=null){
                                                echo '<i class="pe-7s-look  text-primary"></i>';
                                            }?>
                                        </a>
                                        <label class="extension-box" style="padding-right: 1.5rem !important;">
                                            <input type="checkbox" class="extension-status-button" data-status="<?php echo $extension['is_active']; ?>" name="<?php echo $extension['extension_folder_name']; ?>"
                                                <?= $extension['is_active'] ? 'checked=checked' : ''; ?> <?php if((isset($extension['supported_in_minimal']) && $extension['supported_in_minimal'] == 1) || $this->company_subscription_level == 1) {} else { echo "disabled"; } ?>/>
                                            <?php if($this->user_permission != 'is_employee'){ ?>
                                                <?php if((isset($extension['supported_in_minimal']) && $extension['supported_in_minimal'] == 1) || $this->company_subscription_level == 1) { ?>
                                                    <?php if($extension['extension_name'] != 'Vendor Core Features' && $extension['extension_name'] != 'Subscription' && $extension['extension_name'] != 'Vendor monthly report') { ?>
                                                        <span></span>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <span class="premium_extension" style="background-color: darkgrey;"></span>
                                                <?php } ?>
                                            <?php } ?>
                                        </label>
                                    </div>
                                </div>


                            </div>

                        </div>
                        <?php
                        $rowCount++;
                        if($rowCount % $numOfCols == 0) echo '</div><div class="row">';
                    }
                    ?>
                <?php else : ?>
                    <?php if((isset($is_vendor[0]) && $this->user_permission == 'is_admin') || $this->is_super_admin == 1){ ?>
                        <h4><?php $href = base_url()."extensions/show_vendors_extensions"; echo l('No extensions found! Try installing a few, Go to <a href="'.$href.'">All Extensions</a>', true); ?></h4>
                    <?php } else { ?>
                        <h4><?php echo l('No extensions found!'); ?></h4>
                    <?php } ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>


<div class="modal fade" id="premium_extension">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="font-size: 15px;">
                Please select a <b>Partner</b> to get access to this extension.
                <a target="_blank" href="<?php echo base_url().'partners' ?>">
                    Select a Partner
                </a>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>