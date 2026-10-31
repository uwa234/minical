<div class="rp-page mc-page">

<header class="rp-header">
    <div class="rp-header-text">
        <div class="rp-header-icon" aria-hidden="true">
            <i class="fa fa-tags"></i>
        </div>
        <div>
            <h1 class="rp-title"><?php echo l('rate_plan') . ' ' . l('settings'); ?></h1>
            <p class="rp-subtitle">Configure pricing packages for each room type, set currencies, and control online booking visibility.</p>
        </div>
    </div>
    <div class="rp-header-actions">
        <button type="button" class="btn btn-primary add-rate-plan-button">
            <i class="fa fa-plus"></i> <?php echo l('add_rate_plan'); ?>
        </button>
    </div>
</header>

<!-- Image Edit Modal -->
<div class="modal fade rp-modal" id="image_edit_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel"><?php echo l('edit_image'); ?></h4>
            </div>
            <div class="modal-body text-center" id="image_edit_modal_body"></div>
            <div class="modal-footer image-modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo l('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<div class="rp-layout">
    <aside class="rp-sidebar">
        <div class="rp-sidebar-head">
            <h2 class="rp-sidebar-title"><?php echo l('room_types'); ?></h2>
            <p class="rp-sidebar-hint">Drag to reorder</p>
        </div>
        <div class="rp-sidebar-body">
            <div id="room-type-list"></div>
        </div>
    </aside>

    <div class="rp-main">
        <div class="rp-content-card">
            <div class="rp-content-toolbar">
                <h2 class="rp-content-toolbar-title"><?php echo l('rate_plans'); ?></h2>
                <button type="button" class="btn btn-primary btn-sm add-rate-plan-button">
                    <i class="fa fa-plus"></i> <?php echo l('add_rate_plan'); ?>
                </button>
            </div>
            <div class="rp-content-body">
                <div class="rate-plans"></div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" name="subscription_level" class="subscription_level" value="<?php echo $this->company_subscription_level; ?>">
<input type="hidden" name="subscription_state" class="subscription_state" value="<?php echo $this->company_subscription_state; ?>">
<input type="hidden" name="limit_feature" class="limit_feature" value="<?php echo $this->company_feature_limit; ?>">

<div class="modal fade rp-modal" id="new-rate-plan" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo l('Add New Rate Plan', true); ?></h4>
            </div>
            <div class="modal-body rp-form"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo l('Cancel', true); ?></button>
                <button type="button" class="btn btn-success add-new-rate-plan-button">
                    <i class="fa fa-check"></i> <?php echo l('Add Rate Plan', true); ?>
                </button>
            </div>
        </div>
    </div>
</div>

</div>
