<?php
$default_trial_days = isset($default_trial_days) ? (int) $default_trial_days : 14;
$pricing_tiers = isset($pricing_tiers) && is_array($pricing_tiers) ? $pricing_tiers : array();
?>
<div class="modal fade" id="saas-add-property-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="saas-add-property-modal-title">Add property (tenant)</h4>
            </div>
            <div class="modal-body form-horizontal">
                <input type="hidden" name="saas_trial_registration_request_id" value="" />
                <div class="form-group" id="saas-add-property-lead-note" style="display:none;">
                    <div class="col-sm-offset-4 col-sm-8">
                        <p class="text-muted" style="margin:0;">
                            <i class="fa fa-envelope" aria-hidden="true"></i>
                            Creating from trial sign-up #<span id="saas-add-property-lead-id"></span>.
                            The lead will be marked converted when the property is created.
                        </p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label">Property name</label>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="saas_property_name" required />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label">Number of rooms</label>
                    <div class="col-sm-8">
                        <input type="number" min="1" class="form-control" name="saas_number_of_rooms" value="10" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label">Region</label>
                    <div class="col-sm-8">
                        <select name="saas_region" class="form-control">
                            <option value="NA">North America</option>
                            <option value="SA">South America</option>
                            <option value="ANZ">Australia and New Zealand</option>
                            <option value="ASIA">Asia</option>
                            <option value="EAF">Europe and Africa</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label">Pricing tier</label>
                    <div class="col-sm-8">
                        <select name="saas_tier_id" class="form-control" id="saas-add-tier-id">
                            <?php foreach ($pricing_tiers as $tier) {
                                $tier_id = (int) $tier['id'];
                                $tier_name = isset($tier['name']) ? (string) $tier['name'] : '';
                                $legacy_type = ((int) $tier['subscription_level'] <= 0) ? 'BASIC' : 'PREMIUM';
                                echo '<option value="' . $tier_id . '" data-legacy-type="' . htmlspecialchars($legacy_type, ENT_QUOTES, 'UTF-8') . '">'
                                    . htmlspecialchars($tier_name, ENT_QUOTES, 'UTF-8')
                                    . '</option>';
                            } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label">Trial days (if trialing)</label>
                    <div class="col-sm-8">
                        <input type="number" min="1" max="365" class="form-control" name="saas_trial_days"
                               value="<?php echo $default_trial_days; ?>" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label">Subscription state</label>
                    <div class="col-sm-8">
                        <select name="saas_subscription_state" class="form-control">
                            <option value="trialing" selected>Trialing</option>
                            <option value="active">Active</option>
                            <option value="unpaid">Unpaid</option>
                        </select>
                    </div>
                </div>
                <hr />
                <p class="col-sm-offset-4 col-sm-8 text-muted">Optional: invite the hotel owner (creates user if needed).</p>
                <div class="form-group">
                    <label class="col-sm-4 control-label">Owner email</label>
                    <div class="col-sm-8">
                        <input type="email" class="form-control" name="saas_owner_email" placeholder="owner@hotel.com" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label">Owner first name</label>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="saas_owner_first_name" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label">Owner last name</label>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="saas_owner_last_name" />
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="saas-add-property-submit">Create property</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
