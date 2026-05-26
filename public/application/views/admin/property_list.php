<div class="pa-page-header pa-page-header-row">
    <div>
        <h1>SaaS properties</h1>
        <p>Manage tenants, subscriptions, trials, and property owners.</p>
    </div>
    <button type="button" class="pa-btn pa-btn-primary" data-toggle="modal" data-target="#saas-add-property-modal">
        <i class="fa fa-plus" aria-hidden="true"></i> Add property
    </button>
</div>

<form method="get" action="<?php echo base_url('admin/property_list'); ?>" class="pa-filters">
        <label for="state">Subscription</label>
        <select name="state" id="state" class="form-control input-sm">
            <option value="all" <?php echo $filter_state === 'all' ? 'selected' : ''; ?>>All</option>
            <option value="trialing" <?php echo $filter_state === 'trialing' ? 'selected' : ''; ?>>Trialing</option>
            <option value="active" <?php echo $filter_state === 'active' ? 'selected' : ''; ?>>Active</option>
            <option value="unpaid" <?php echo $filter_state === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
            <option value="canceled" <?php echo $filter_state === 'canceled' ? 'selected' : ''; ?>>Canceled</option>
            <option value="trial_ended" <?php echo $filter_state === 'trial_ended' ? 'selected' : ''; ?>>Trial ended</option>
        </select>
        <input type="text" name="search_query" class="form-control input-sm" placeholder="Search name or email"
               value="<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>" />
        <label class="pa-filter-checkbox">
            <input type="checkbox" name="include_deleted" value="1"
                <?php echo $this->input->get('include_deleted') === '1' ? 'checked' : ''; ?> />
            Include soft-deleted
        </label>
        <button type="submit" class="pa-btn pa-btn-secondary pa-btn-sm">Filter</button>
    </form>

<div class="pa-card">
    <div class="pa-card-body pa-table-wrap">
    <table class="pa-table" id="saas-property-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Property</th>
                <th>Owner</th>
                <th>Rooms</th>
                <th>Subscription</th>
                <th>Plan level</th>
                <th>Trial expires</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($properties)) { ?>
                <tr>
                    <td colspan="9" class="text-muted">No properties found.</td>
                </tr>
            <?php } else {
                foreach ($properties as $property) {
                    $owner_label = '';
                    if (!empty($property['owner_email'])) {
                        $owner_label = htmlspecialchars($property['owner_email'], ENT_QUOTES, 'UTF-8');
                        if (!empty($property['owner_first_name']) || !empty($property['owner_last_name'])) {
                            $owner_label = htmlspecialchars(trim($property['owner_first_name'] . ' ' . $property['owner_last_name']), ENT_QUOTES, 'UTF-8')
                                . ' &lt;' . htmlspecialchars($property['owner_email'], ENT_QUOTES, 'UTF-8') . '&gt;';
                        }
                    } else {
                        $owner_label = '<span class="text-muted">—</span>';
                    }
                    ?>
                    <tr data-company-id="<?php echo (int) $property['company_id']; ?>">
                        <td><?php echo (int) $property['company_id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($property['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <?php if (!empty($property['is_deleted'])) { ?>
                                <span class="pa-badge pa-badge-muted">Deleted</span>
                            <?php } ?>
                        </td>
                        <td><?php echo $owner_label; ?></td>
                        <td><?php echo (int) $property['number_of_rooms']; ?></td>
                        <td>
                            <select class="form-control input-sm saas-subscription-state" data-company-id="<?php echo (int) $property['company_id']; ?>">
                                <?php
                                $states = array('trialing', 'active', 'unpaid', 'canceled', 'trial_ended');
                                foreach ($states as $st) {
                                    $sel = (isset($property['subscription_state']) && $property['subscription_state'] === $st) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($st, ENT_QUOTES, 'UTF-8') . '" ' . $sel . '>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $st)), ENT_QUOTES, 'UTF-8') . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                        <td>
                            <select class="form-control input-sm saas-subscription-level" data-company-id="<?php echo (int) $property['company_id']; ?>">
                                <option value="0" <?php echo (int) $property['subscription_level'] === 0 ? 'selected' : ''; ?>>Basic</option>
                                <option value="1" <?php echo (int) $property['subscription_level'] === 1 ? 'selected' : ''; ?>>Premium</option>
                            </select>
                        </td>
                        <td class="saas-trial-cell">
                            <?php
                            $exp = isset($property['trial_expiry_date']) ? $property['trial_expiry_date'] : '';
                            if ($exp) {
                                echo htmlspecialchars($exp, ENT_QUOTES, 'UTF-8');
                            } else {
                                echo '<span class="text-muted">—</span>';
                            }
                            ?>
                            <div class="input-group input-group-sm" style="margin-top:4px; max-width:200px;">
                                <input type="number" min="1" max="365" class="form-control saas-trial-days" placeholder="Days"
                                       data-company-id="<?php echo (int) $property['company_id']; ?>" title="Extend trial from today" />
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default saas-trial-save-btn"
                                            data-company-id="<?php echo (int) $property['company_id']; ?>">Set</button>
                                </span>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($property['creation_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="pa-actions-cell">
                            <a class="pa-btn pa-btn-secondary pa-btn-sm" href="<?php echo base_url('menu/select_hotel/' . (int) $property['company_id']); ?>">Open</a>
                            <button type="button" class="pa-btn pa-btn-secondary pa-btn-sm saas-edit-property-btn"
                                    data-company-id="<?php echo (int) $property['company_id']; ?>"
                                    data-company-name="<?php echo htmlspecialchars($property['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-company-email="<?php echo htmlspecialchars(isset($property['email']) ? $property['email'] : '', ENT_QUOTES, 'UTF-8'); ?>"
                                    data-number-of-rooms="<?php echo (int) $property['number_of_rooms']; ?>">
                                Edit
                            </button>
                            <button type="button" class="pa-btn pa-btn-secondary pa-btn-sm saas-assign-owner-btn"
                                    data-company-id="<?php echo (int) $property['company_id']; ?>"
                                    data-company-name="<?php echo htmlspecialchars($property['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-owner-email="<?php echo htmlspecialchars(isset($property['owner_email']) ? $property['owner_email'] : '', ENT_QUOTES, 'UTF-8'); ?>"
                                    data-owner-first-name="<?php echo htmlspecialchars(isset($property['owner_first_name']) ? $property['owner_first_name'] : '', ENT_QUOTES, 'UTF-8'); ?>"
                                    data-owner-last-name="<?php echo htmlspecialchars(isset($property['owner_last_name']) ? $property['owner_last_name'] : '', ENT_QUOTES, 'UTF-8'); ?>">
                                Assign owner
                            </button>
                            <button type="button" class="pa-btn pa-btn-danger pa-btn-sm saas-delete-property-btn"
                                    data-company-id="<?php echo (int) $property['company_id']; ?>"
                                    data-company-name="<?php echo htmlspecialchars($property['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                Delete
                            </button>
                        </td>
                    </tr>
                <?php }
            } ?>
        </tbody>
    </table>
    </div>
</div>

<div class="modal fade" id="saas-add-property-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">Add property (tenant)</h4>
            </div>
            <div class="modal-body form-horizontal">
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
                    <label class="col-sm-4 control-label">Pricing plan</label>
                    <div class="col-sm-8">
                        <select name="saas_subscription_type" class="form-control">
                            <option value="BASIC">Basic</option>
                            <option value="PREMIUM">Premium</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label">Trial days (if trialing)</label>
                    <div class="col-sm-8">
                        <input type="number" min="1" max="365" class="form-control" name="saas_trial_days"
                               value="<?php echo (int) $default_trial_days; ?>" />
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

<div class="modal fade" id="saas-edit-property-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">Edit property — <span id="saas-edit-property-name"></span></h4>
            </div>
            <div class="modal-body form-horizontal">
                <input type="hidden" name="saas_edit_company_id" value="" />
                <div class="form-group">
                    <label class="col-sm-4 control-label">Property name</label>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="saas_edit_property_name" required />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label">Property contact email</label>
                    <div class="col-sm-8">
                        <input type="email" class="form-control" name="saas_edit_property_email" />
                        <p class="help-block text-muted" style="margin-bottom:0;">Shown on invoices and guest communications. Use Assign owner to change the Owner column.</p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label">Number of rooms</label>
                    <div class="col-sm-8">
                        <input type="number" min="1" class="form-control" name="saas_edit_number_of_rooms" />
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="saas-edit-property-submit">Save changes</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="saas-delete-property-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">Permanently delete property</h4>
            </div>
            <div class="modal-body">
                <p class="text-danger">
                    <strong>This cannot be undone.</strong> All bookings, customers, rooms, rates, and related data for
                    <strong id="saas-delete-property-name"></strong> will be removed from the database.
                </p>
                <input type="hidden" name="saas_delete_company_id" value="" />
                <div class="form-group">
                    <label for="saas_delete_confirm_name">Type the property name to confirm</label>
                    <input type="text" class="form-control" id="saas_delete_confirm_name" name="saas_delete_confirm_name"
                           autocomplete="off" placeholder="Exact property name" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="saas-delete-property-submit">Delete permanently</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="saas-assign-owner-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">Assign owner — <span id="saas-assign-owner-property-name"></span></h4>
            </div>
            <div class="modal-body form-horizontal">
                <input type="hidden" name="saas_assign_company_id" value="" />
                <div class="form-group">
                    <label class="col-sm-4 control-label">Email</label>
                    <div class="col-sm-8">
                        <input type="email" class="form-control" name="saas_assign_owner_email" required />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label">First name</label>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="saas_assign_owner_first_name" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label">Last name</label>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="saas_assign_owner_last_name" />
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="saas-assign-owner-submit">Save</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
