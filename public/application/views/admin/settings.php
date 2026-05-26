<?php if ($this->session->flashdata('admin_message')) { ?>
    <div class="pa-alert pa-alert-success"><i class="fa fa-check-circle" aria-hidden="true"></i><?php echo htmlspecialchars($this->session->flashdata('admin_message'), ENT_QUOTES, 'UTF-8'); ?></div>
<?php } ?>
<?php if ($this->session->flashdata('admin_error')) { ?>
    <div class="pa-alert pa-alert-danger"><i class="fa fa-exclamation-circle" aria-hidden="true"></i><?php echo htmlspecialchars($this->session->flashdata('admin_error'), ENT_QUOTES, 'UTF-8'); ?></div>
<?php } ?>

<div class="pa-page-header">
    <h1>Platform settings</h1>
    <p>Configure trials, marketing copy, and public pricing tiers.</p>
</div>

<div class="pa-settings-grid">
    <div class="pa-card">
        <div class="pa-card-header"><h2>General</h2></div>
        <div class="pa-card-body">
        <form method="post" action="<?php echo base_url('admin/settings'); ?>" class="form-horizontal">
            <input type="hidden" name="save_settings" value="1" />
            <div class="form-group">
                <label class="col-sm-5 control-label">Default trial (days)</label>
                <div class="col-sm-4">
                    <input type="number" min="1" max="365" class="form-control" name="default_trial_days"
                           value="<?php echo (int) $default_trial_days; ?>" required />
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-5 control-label">Marketing hero title</label>
                <div class="col-sm-7">
                    <input type="text" class="form-control" name="marketing_hero_title"
                           value="<?php echo htmlspecialchars($marketing_hero_title, ENT_QUOTES, 'UTF-8'); ?>" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-5 control-label">Marketing hero subtitle</label>
                <div class="col-sm-7">
                    <textarea class="form-control" name="marketing_hero_subtitle" rows="2"><?php echo htmlspecialchars($marketing_hero_subtitle, ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-offset-5 col-sm-7">
                    <button type="submit" class="pa-btn pa-btn-primary">Save settings</button>
                </div>
            </div>
        </form>
        </div>
    </div>

    <div class="pa-card">
        <div class="pa-card-header"><h2><?php echo $edit_tier ? 'Edit pricing tier' : 'Add pricing tier'; ?></h2></div>
        <div class="pa-card-body">
        <form method="post" action="<?php echo base_url('admin/settings'); ?>" class="form-horizontal">
            <input type="hidden" name="save_tier" value="1" />
            <?php if ($edit_tier) { ?>
                <input type="hidden" name="tier_id" value="<?php echo (int) $edit_tier['id']; ?>" />
            <?php } ?>
            <div class="form-group">
                <label class="col-sm-4 control-label">Name</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" name="tier_name" required
                           value="<?php echo $edit_tier ? htmlspecialchars($edit_tier['name'], ENT_QUOTES, 'UTF-8') : ''; ?>" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-4 control-label">Min rooms</label>
                <div class="col-sm-8">
                    <input type="number" min="1" class="form-control" name="tier_min_rooms" required
                           value="<?php echo $edit_tier ? (int) $edit_tier['min_rooms'] : 1; ?>" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-4 control-label">Max rooms</label>
                <div class="col-sm-8">
                    <input type="number" min="1" class="form-control" name="tier_max_rooms"
                           placeholder="Leave empty for unlimited"
                           value="<?php echo ($edit_tier && $edit_tier['max_rooms'] !== null) ? (int) $edit_tier['max_rooms'] : ''; ?>" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-4 control-label">Monthly price</label>
                <div class="col-sm-8">
                    <input type="number" step="0.01" min="0" class="form-control" name="tier_monthly_price" required
                           value="<?php echo $edit_tier ? htmlspecialchars($edit_tier['monthly_price'], ENT_QUOTES, 'UTF-8') : ''; ?>" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-4 control-label">Currency</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" name="tier_currency" maxlength="8"
                           value="<?php echo $edit_tier ? htmlspecialchars($edit_tier['currency'], ENT_QUOTES, 'UTF-8') : 'USD'; ?>" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-4 control-label">Plan level</label>
                <div class="col-sm-8">
                    <select name="tier_subscription_level" class="form-control">
                        <option value="0" <?php echo ($edit_tier && (int)$edit_tier['subscription_level'] === 0) ? 'selected' : ''; ?>>Basic</option>
                        <option value="1" <?php echo ($edit_tier && (int)$edit_tier['subscription_level'] === 1) ? 'selected' : ''; ?>>Premium</option>
                        <option value="2" <?php echo ($edit_tier && (int)$edit_tier['subscription_level'] === 2) ? 'selected' : ''; ?>>Elite</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-4 control-label">Features (one per line)</label>
                <div class="col-sm-8">
                    <textarea class="form-control" name="tier_features" rows="4"><?php
                        if ($edit_tier && !empty($edit_tier['features'])) {
                            echo htmlspecialchars(implode("\n", $edit_tier['features']), ENT_QUOTES, 'UTF-8');
                        }
                    ?></textarea>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-4 control-label">Sort order</label>
                <div class="col-sm-8">
                    <input type="number" class="form-control" name="tier_sort_order"
                           value="<?php echo $edit_tier ? (int) $edit_tier['sort_order'] : 0; ?>" />
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-offset-4 col-sm-8">
                    <label><input type="checkbox" name="tier_is_active" value="1"
                        <?php echo (!$edit_tier || !empty($edit_tier['is_active'])) ? 'checked' : ''; ?> /> Active on marketing page</label>
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-offset-4 col-sm-8">
                    <button type="submit" class="pa-btn pa-btn-primary"><?php echo $edit_tier ? 'Update tier' : 'Add tier'; ?></button>
                    <?php if ($edit_tier) { ?>
                        <a class="pa-btn pa-btn-secondary" href="<?php echo base_url('admin/settings'); ?>">Cancel</a>
                    <?php } ?>
                </div>
            </div>
        </form>
        </div>
    </div>
</div>

<div class="pa-card">
    <div class="pa-card-header"><h2>Pricing tiers</h2></div>
    <div class="pa-card-body pa-table-wrap">
<table class="pa-table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Rooms</th>
            <th>Price / mo</th>
            <th>Level</th>
            <th>Active</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($pricing_tiers)) { ?>
            <tr><td colspan="6" class="text-muted">No tiers defined</td></tr>
        <?php } else {
            foreach ($pricing_tiers as $tier) {
                $room_range = (int) $tier['min_rooms'] . ' – ';
                $room_range .= ($tier['max_rooms'] === null || $tier['max_rooms'] === '') ? '∞' : (int) $tier['max_rooms'];
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($tier['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo $room_range; ?></td>
                    <td><?php echo htmlspecialchars($tier['currency'], ENT_QUOTES, 'UTF-8'); ?>
                        <?php echo number_format((float) $tier['monthly_price'], 2); ?></td>
                    <td><?php echo (int) $tier['subscription_level']; ?></td>
                    <td><?php echo !empty($tier['is_active']) ? 'Yes' : 'No'; ?></td>
                    <td>
                        <a class="pa-btn pa-btn-secondary pa-btn-sm" href="<?php echo base_url('admin/settings?edit_tier=' . (int) $tier['id']); ?>">Edit</a>
                        <form method="post" action="<?php echo base_url('admin/settings'); ?>" style="display:inline;" onsubmit="return confirm('Delete this tier?');">
                            <input type="hidden" name="delete_tier" value="1" />
                            <input type="hidden" name="tier_id" value="<?php echo (int) $tier['id']; ?>" />
                            <button type="submit" class="pa-btn pa-btn-sm" style="background:var(--pa-danger-soft);color:var(--pa-danger);border:1px solid rgba(220,38,38,0.25);">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php }
        } ?>
    </tbody>
</table>
    </div>
</div>
