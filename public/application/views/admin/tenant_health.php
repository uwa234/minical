<?php
$summary = isset($health_summary) && is_array($health_summary) ? $health_summary : array();
$rows = isset($health_rows) && is_array($health_rows) ? $health_rows : array();
$ext_stats = isset($extension_stats) && is_array($extension_stats) ? $extension_stats : array();
$night = isset($night_audit_health) && is_array($night_audit_health) ? $night_audit_health : array();
$cron = isset($cron_health) && is_array($cron_health) ? $cron_health : array();
$sync_issues = isset($channel_sync_issues) && is_array($channel_sync_issues) ? $channel_sync_issues : array();
$filter = isset($filter) ? $filter : 'at_risk';
$active_count = isset($active_tenant_count) ? (int) $active_tenant_count : 0;
$pricing_tiers = isset($pricing_tiers) && is_array($pricing_tiers) ? $pricing_tiers : array();
$default_grace = isset($default_grace_days) ? (int) $default_grace_days : 7;
$search = isset($search_query) ? (string) $search_query : '';
?>
<?php if ($this->session->flashdata('admin_message')) { ?>
    <div class="pa-alert pa-alert-success">
        <i class="fa fa-check-circle" aria-hidden="true"></i>
        <?php echo htmlspecialchars($this->session->flashdata('admin_message'), ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php } ?>

<div class="pa-page-header">
    <h1>Tenant health &amp; support</h1>
    <p>At-risk tenants, extension adoption, cron health, impersonation, and bulk support actions.</p>
</div>

<div class="pa-stats pa-stats--compact">
    <div class="pa-stat-card pa-stat-card--danger">
        <div class="pa-stat-label">Critical</div>
        <div class="pa-stat-value"><?php echo (int) (isset($summary['critical']) ? $summary['critical'] : 0); ?></div>
    </div>
    <div class="pa-stat-card pa-stat-card--warning">
        <div class="pa-stat-label">At risk</div>
        <div class="pa-stat-value"><?php echo (int) (isset($summary['at_risk']) ? $summary['at_risk'] : 0); ?></div>
    </div>
    <div class="pa-stat-card pa-stat-card--info">
        <div class="pa-stat-label">No bookings</div>
        <div class="pa-stat-value"><?php echo (int) (isset($summary['no_bookings']) ? $summary['no_bookings'] : 0); ?></div>
    </div>
    <div class="pa-stat-card pa-stat-card--warning">
        <div class="pa-stat-label">Trial issues</div>
        <div class="pa-stat-value"><?php echo (int) (isset($summary['trial_expiring']) ? $summary['trial_expiring'] : 0); ?></div>
    </div>
    <div class="pa-stat-card pa-stat-card--danger">
        <div class="pa-stat-label">Night audit overdue</div>
        <div class="pa-stat-value"><?php echo (int) (isset($summary['night_audit_overdue']) ? $summary['night_audit_overdue'] : 0); ?></div>
    </div>
    <div class="pa-stat-card pa-stat-card--accent">
        <div class="pa-stat-label">Channel sync stale</div>
        <div class="pa-stat-value"><?php echo (int) (isset($summary['channel_sync_issues']) ? $summary['channel_sync_issues'] : 0); ?></div>
    </div>
</div>

<div class="pa-grid-2">
    <div class="pa-card">
        <div class="pa-card-header">
            <h2>Cron health</h2>
        </div>
        <div class="pa-card-body">
            <table class="pa-table pa-table-compact">
                <tbody>
                    <tr>
                        <th>Trial expiry cron</th>
                        <td>
                            <?php echo htmlspecialchars(isset($cron['trial_cron_schedule']) ? $cron['trial_cron_schedule'] : 'Daily', ENT_QUOTES, 'UTF-8'); ?>
                            · Last run:
                            <?php echo !empty($cron['trial_cron_last_run'])
                                ? htmlspecialchars($cron['trial_cron_last_run'], ENT_QUOTES, 'UTF-8')
                                : '—'; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Pending trial expiry</th>
                        <td>
                            <strong><?php echo (int) (isset($cron['trials_pending_expiry']) ? $cron['trials_pending_expiry'] : 0); ?></strong>
                            properties past expiry date (next cron run)
                        </td>
                    </tr>
                    <tr>
                        <th>Expiring today</th>
                        <td><?php echo (int) (isset($cron['trials_expiring_today']) ? $cron['trials_expiring_today'] : 0); ?> trialing</td>
                    </tr>
                    <tr>
                        <th>Night audit cron</th>
                        <td>
                            <?php echo htmlspecialchars(isset($cron['night_audit_cron_schedule']) ? $cron['night_audit_cron_schedule'] : 'Every 15 min', ENT_QUOTES, 'UTF-8'); ?>
                            · Last run:
                            <?php echo !empty($cron['night_audit_cron_last_run'])
                                ? htmlspecialchars($cron['night_audit_cron_last_run'], ENT_QUOTES, 'UTF-8')
                                : '—'; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Auto night audit enabled</th>
                        <td>
                            <?php echo (int) (isset($night['enabled_count']) ? $night['enabled_count'] : 0); ?> properties ·
                            <strong><?php echo (int) (isset($night['overdue_count']) ? $night['overdue_count'] : 0); ?></strong> overdue
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="pa-meta-note">Trial cron endpoint: <code>/cron/expire_platform_trials</code> · Night audit: <code>/cron/run_automated_night_audit</code></p>
        </div>
    </div>

    <div class="pa-card">
        <div class="pa-card-header">
            <h2>Extension adoption</h2>
            <span class="pa-meta-inline"><?php echo $active_count; ?> active/trialing tenants</span>
        </div>
        <div class="pa-card-body pa-table-wrap">
            <?php if (empty($ext_stats)) { ?>
                <div class="pa-empty"><i class="fa fa-puzzle-piece" aria-hidden="true"></i> No extension data</div>
            <?php } else { ?>
                <table class="pa-table">
                    <thead>
                        <tr><th>Extension</th><th>Tenants</th><th>Adoption</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ext_stats as $ext) {
                        $count = (int) (isset($ext['tenant_count']) ? $ext['tenant_count'] : 0);
                        $pct = isset($ext['adoption_pct']) ? (float) $ext['adoption_pct'] : 0;
                        $label = !empty($ext['display_name']) ? $ext['display_name'] : $ext['extension_name'];
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td><?php echo $count; ?></td>
                            <td>
                                <div class="pa-adoption-bar" aria-hidden="true">
                                    <span style="width: <?php echo min(100, $pct); ?>%;"></span>
                                </div>
                                <?php echo htmlspecialchars(number_format($pct, 1), ENT_QUOTES, 'UTF-8'); ?>%
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>
    </div>
</div>

<div class="pa-card" style="margin-bottom: 24px;">
    <div class="pa-card-header">
        <h2>Bulk actions</h2>
    </div>
    <div class="pa-card-body">
        <div class="pa-bulk-actions">
            <div class="pa-bulk-block">
                <h3>Extend trials</h3>
                <label for="bulk-extend-scope">Scope</label>
                <select id="bulk-extend-scope" class="form-control input-sm">
                    <option value="expiring_7">Trials expiring within 7 days</option>
                    <option value="all_trialing">All trialing tenants</option>
                    <option value="at_risk">At-risk tenants only</option>
                </select>
                <label for="bulk-extend-days">Grace days</label>
                <input type="number" id="bulk-extend-days" class="form-control input-sm" min="1" value="<?php echo $default_grace; ?>" />
                <button type="button" class="pa-btn pa-btn-primary pa-btn-sm" id="bulk-extend-submit">Extend trials</button>
            </div>
            <div class="pa-bulk-block">
                <h3>Email tenants</h3>
                <label for="bulk-email-scope">Scope</label>
                <select id="bulk-email-scope" class="form-control input-sm">
                    <option value="active">All active subscribers</option>
                    <option value="all_trialing">All trialing</option>
                    <option value="expiring_7">Trials expiring within 7 days</option>
                    <option value="at_risk">At-risk tenants</option>
                </select>
                <label for="bulk-email-subject">Subject</label>
                <input type="text" id="bulk-email-subject" class="form-control input-sm" placeholder="Email subject" />
                <label for="bulk-email-body">Message</label>
                <textarea id="bulk-email-body" class="form-control input-sm" rows="3" placeholder="Plain text message…"></textarea>
                <button type="button" class="pa-btn pa-btn-secondary pa-btn-sm" id="bulk-email-submit">Send emails</button>
            </div>
            <div class="pa-bulk-block">
                <h3>Bulk tier change</h3>
                <label for="bulk-tier-scope">Scope</label>
                <select id="bulk-tier-scope" class="form-control input-sm">
                    <option value="selected">Selected rows in table</option>
                    <option value="active">All active subscribers</option>
                    <option value="all_trialing">All trialing</option>
                    <option value="at_risk">At-risk tenants</option>
                </select>
                <label for="bulk-tier-id">Pricing tier</label>
                <select id="bulk-tier-id" class="form-control input-sm">
                    <?php foreach ($pricing_tiers as $tier) { ?>
                        <option value="<?php echo (int) $tier['id']; ?>"><?php echo htmlspecialchars($tier['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php } ?>
                </select>
                <button type="button" class="pa-btn pa-btn-secondary pa-btn-sm" id="bulk-tier-submit">Apply tier</button>
            </div>
        </div>
        <div id="bulk-action-result" class="pa-bulk-result" aria-live="polite"></div>
    </div>
</div>

<form method="get" action="<?php echo base_url('admin/tenant_health'); ?>" class="pa-filters" style="margin-bottom: 24px;">
    <label for="filter">Show</label>
    <select name="filter" id="filter" class="form-control input-sm">
        <option value="at_risk" <?php echo $filter === 'at_risk' ? 'selected' : ''; ?>>At-risk &amp; critical</option>
        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All tenants</option>
        <option value="no_bookings" <?php echo $filter === 'no_bookings' ? 'selected' : ''; ?>>No bookings</option>
        <option value="trial_expiring" <?php echo $filter === 'trial_expiring' ? 'selected' : ''; ?>>Trial expiring / expired</option>
        <option value="night_audit" <?php echo $filter === 'night_audit' ? 'selected' : ''; ?>>Night audit overdue</option>
        <option value="channel_sync" <?php echo $filter === 'channel_sync' ? 'selected' : ''; ?>>Channel sync stale</option>
    </select>
    <input type="text" name="search_query" class="form-control input-sm" placeholder="Search property or owner"
           value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" />
    <button type="submit" class="pa-btn pa-btn-secondary pa-btn-sm">Apply</button>
</form>

<div class="pa-card">
    <div class="pa-card-header">
        <h2>At-risk tenants</h2>
        <span class="pa-meta-inline"><?php echo count($rows); ?> shown</span>
    </div>
    <div class="pa-card-body pa-table-wrap">
        <table class="pa-table" id="tenant-health-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="tenant-health-select-all" aria-label="Select all" /></th>
                    <th>Health</th>
                    <th>Property</th>
                    <th>State</th>
                    <th>Issues</th>
                    <th>Bookings</th>
                    <th>Owner</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)) { ?>
                    <tr><td colspan="8" class="pa-empty" style="padding: 32px;">No tenants match this filter</td></tr>
                <?php } else {
                    foreach ($rows as $row) {
                        $cid = (int) $row['company_id'];
                        $score = (int) $row['health_score'];
                        $risk = isset($row['risk_level']) ? $row['risk_level'] : 'healthy';
                        $badge_class = $risk === 'critical' ? 'pa-badge-unpaid' : ($risk === 'at_risk' ? 'pa-badge-trial_ended' : 'pa-badge-active');
                        ?>
                        <tr data-company-id="<?php echo $cid; ?>">
                            <td><input type="checkbox" class="tenant-health-select" value="<?php echo $cid; ?>" aria-label="Select property" /></td>
                            <td>
                                <span class="pa-health-score pa-health-score--<?php echo htmlspecialchars($risk, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $score; ?></span>
                                <span class="pa-badge <?php echo $badge_class; ?>"><?php echo ucfirst(str_replace('_', ' ', $risk)); ?></span>
                            </td>
                            <td>
                                <a href="<?php echo base_url('admin/property_detail/' . $cid); ?>">
                                    <?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </td>
                            <td>
                                <span class="pa-badge pa-badge-<?php echo htmlspecialchars($row['subscription_state'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $row['subscription_state'])); ?>
                                </span>
                                <?php if (!empty($row['trial_expiry_date'])) { ?>
                                    <br /><span class="text-muted">Trial: <?php echo htmlspecialchars($row['trial_expiry_date'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php } ?>
                            </td>
                            <td class="pa-flag-cell">
                                <?php
                                if (!empty($row['flag_labels'])) {
                                    foreach ($row['flag_labels'] as $label) {
                                        echo '<span class="pa-flag-chip">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
                                    }
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td><?php echo (int) $row['total_bookings']; ?> <span class="text-muted">(30d: <?php echo (int) $row['bookings_last_30_days']; ?>)</span></td>
                            <td>
                                <?php
                                if (!empty($row['owner_email'])) {
                                    echo htmlspecialchars($row['owner_name'] ?: $row['owner_email'], ENT_QUOTES, 'UTF-8');
                                    if ($row['owner_name']) {
                                        echo '<br /><span class="text-muted">' . htmlspecialchars($row['owner_email'], ENT_QUOTES, 'UTF-8') . '</span>';
                                    }
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td class="pa-actions-cell">
                                <a class="pa-btn pa-btn-primary pa-btn-sm" href="<?php echo base_url('admin/impersonate_tenant/' . $cid); ?>">
                                    Login as tenant
                                </a>
                            </td>
                        </tr>
                    <?php }
                } ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($night['overdue'])) { ?>
<div class="pa-card" style="margin-top: 24px;">
    <div class="pa-card-header">
        <h2>Night audit overdue</h2>
    </div>
    <div class="pa-card-body pa-table-wrap">
        <table class="pa-table">
            <thead><tr><th>Property</th><th>Selling date</th><th>Calendar date</th><th>Scheduled run</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($night['overdue'] as $na) { ?>
                <tr>
                    <td><a href="<?php echo base_url('admin/property_detail/' . (int) $na['company_id']); ?>"><?php echo htmlspecialchars($na['name'], ENT_QUOTES, 'UTF-8'); ?></a></td>
                    <td><?php echo htmlspecialchars($na['selling_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($na['calendar_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($na['run_time'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><a class="pa-btn pa-btn-secondary pa-btn-sm" href="<?php echo base_url('admin/impersonate_tenant/' . (int) $na['company_id']); ?>">Open property</a></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php } ?>
