<?php
$tenant = isset($tenant) && is_array($tenant) ? $tenant : array();
$company_id = isset($company_id) ? (int) $company_id : 0;
$currency_symbol = isset($saas_currency_symbol) ? $saas_currency_symbol : saas_currency_symbol();
$format_amount = function ($amount) {
    return format_saas_price_amount($amount);
};
$booking_stats = isset($booking_stats) && is_array($booking_stats) ? $booking_stats : array();
$setup_progress = isset($setup_progress) && is_array($setup_progress) ? $setup_progress : array();
$extensions = isset($extensions) && is_array($extensions) ? $extensions : array();
$channels = isset($channels) && is_array($channels) ? $channels : array();
$activity_log = isset($activity_log) && is_array($activity_log) ? $activity_log : array();
$subscription_payments = isset($subscription_payments) && is_array($subscription_payments) ? $subscription_payments : array();
$payment_total = isset($payment_total) ? (float) $payment_total : 0;
$tiers = isset($pricing_tiers) && is_array($pricing_tiers) ? $pricing_tiers : array();
$activity_log_enabled = !empty($activity_log_enabled);
$setup_done = 0;
$setup_total = count($setup_progress);
foreach ($setup_progress as $item) {
    if (!empty($item['done'])) {
        $setup_done++;
    }
}

$owner_label = '—';
if (!empty($tenant['owner_email'])) {
    $owner_label = htmlspecialchars($tenant['owner_email'], ENT_QUOTES, 'UTF-8');
    if (!empty($tenant['owner_first_name']) || !empty($tenant['owner_last_name'])) {
        $owner_label = htmlspecialchars(trim($tenant['owner_first_name'] . ' ' . $tenant['owner_last_name']), ENT_QUOTES, 'UTF-8')
            . ' &lt;' . htmlspecialchars($tenant['owner_email'], ENT_QUOTES, 'UTF-8') . '&gt;';
    }
}

$last_login = isset($tenant['last_login']) ? trim((string) $tenant['last_login']) : '';
if ($last_login === '' || $last_login === '0000-00-00') {
    $last_login_label = 'Never';
} else {
    $last_login_label = htmlspecialchars($last_login, ENT_QUOTES, 'UTF-8');
}

$state = isset($tenant['subscription_state']) ? (string) $tenant['subscription_state'] : '';
$plan_expiry = isset($tenant['plan_expiry_date']) ? (string) $tenant['plan_expiry_date'] : '';
$days_left = isset($tenant['plan_expiry_days_left']) ? $tenant['plan_expiry_days_left'] : null;

function pa_format_extension_name($name) {
    $name = str_replace(array('-', '_'), ' ', (string) $name);
    return ucwords($name);
}
?>
<div id="tenant-detail-page" data-company-id="<?php echo $company_id; ?>">

<?php if ($this->session->flashdata('admin_message')) { ?>
    <div class="pa-alert pa-alert-success">
        <i class="fa fa-check-circle" aria-hidden="true"></i>
        <?php echo htmlspecialchars($this->session->flashdata('admin_message'), ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php } ?>

<div class="pa-page-header pa-page-header-row">
    <div>
        <p class="pa-breadcrumb">
            <a href="<?php echo base_url('admin/property_list'); ?>">Properties</a>
            <span aria-hidden="true">/</span>
            <?php echo htmlspecialchars(isset($tenant['name']) ? $tenant['name'] : 'Property', ENT_QUOTES, 'UTF-8'); ?>
        </p>
        <h1>
            <?php echo htmlspecialchars(isset($tenant['name']) ? $tenant['name'] : 'Property', ENT_QUOTES, 'UTF-8'); ?>
            <?php if (!empty($tenant['is_deleted'])) { ?>
                <span class="pa-badge pa-badge-muted">Deleted</span>
            <?php } ?>
        </h1>
        <p>
            Property #<?php echo $company_id; ?>
            <?php if ($state !== '') { ?>
                · <span class="pa-badge pa-badge-<?php echo htmlspecialchars($state, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $state)), ENT_QUOTES, 'UTF-8'); ?>
                </span>
            <?php } ?>
            <?php if (!empty($tenant['pricing_tier_name']) && $tenant['pricing_tier_name'] !== '—') { ?>
                · <?php echo htmlspecialchars($tenant['pricing_tier_name'], ENT_QUOTES, 'UTF-8'); ?>
            <?php } ?>
        </p>
    </div>
    <div class="pa-btn-group">
        <a class="pa-btn pa-btn-secondary" href="<?php echo base_url('admin/property_list'); ?>">
            <i class="fa fa-arrow-left" aria-hidden="true"></i> Back to list
        </a>
        <?php if (empty($tenant['is_deleted'])) { ?>
            <a class="pa-btn pa-btn-primary" href="<?php echo base_url('menu/select_hotel/' . $company_id); ?>">
                <i class="fa fa-external-link" aria-hidden="true"></i> Open property
            </a>
        <?php } ?>
    </div>
</div>

<div class="pa-stats">
    <div class="pa-stat-card pa-stat-card--info">
        <div class="pa-stat-label">Last login</div>
        <div class="pa-stat-value pa-stat-value-sm"><?php echo $last_login_label; ?></div>
    </div>
    <div class="pa-stat-card pa-stat-card--accent">
        <div class="pa-stat-label">Bookings (30 days)</div>
        <div class="pa-stat-value"><?php echo (int) (isset($booking_stats['bookings_last_30_days']) ? $booking_stats['bookings_last_30_days'] : 0); ?></div>
        <div class="pa-stat-sub"><?php echo (int) (isset($booking_stats['total_bookings']) ? $booking_stats['total_bookings'] : 0); ?> lifetime</div>
    </div>
    <div class="pa-stat-card pa-stat-card--success">
        <div class="pa-stat-label">Setup progress</div>
        <div class="pa-stat-value"><?php echo $setup_done; ?>/<?php echo $setup_total; ?></div>
    </div>
    <div class="pa-stat-card pa-stat-card--warning">
        <div class="pa-stat-label">Lifetime subscription paid</div>
        <div class="pa-stat-value"><?php echo htmlspecialchars($currency_symbol, ENT_QUOTES, 'UTF-8'); ?><?php echo $format_amount($payment_total); ?></div>
        <div class="pa-stat-sub"><?php echo count($subscription_payments); ?> payment<?php echo count($subscription_payments) === 1 ? '' : 's'; ?></div>
    </div>
</div>

<div class="pa-grid-2">
    <div class="pa-card">
        <div class="pa-card-header"><h2>Overview</h2></div>
        <div class="pa-card-body">
            <dl class="pa-detail-list">
                <div class="pa-detail-row">
                    <dt>Owner</dt>
                    <dd><?php echo $owner_label; ?></dd>
                </div>
                <div class="pa-detail-row">
                    <dt>Contact email</dt>
                    <dd><?php echo !empty($tenant['email']) ? htmlspecialchars($tenant['email'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted">—</span>'; ?></dd>
                </div>
                <div class="pa-detail-row">
                    <dt>Rooms</dt>
                    <dd>
                        <?php
                        $rooms = isset($tenant['number_of_rooms']) ? (int) $tenant['number_of_rooms'] : 0;
                        $rooms_actual = isset($tenant['number_of_rooms_actual']) ? (int) $tenant['number_of_rooms_actual'] : 0;
                        echo $rooms;
                        if ($rooms_actual > 0 && $rooms_actual !== $rooms) {
                            echo ' <span class="text-muted">(' . $rooms_actual . ' configured)</span>';
                        }
                        ?>
                    </dd>
                </div>
                <div class="pa-detail-row">
                    <dt>Created</dt>
                    <dd><?php echo !empty($tenant['creation_date']) ? htmlspecialchars($tenant['creation_date'], ENT_QUOTES, 'UTF-8') : '—'; ?></dd>
                </div>
                <div class="pa-detail-row">
                    <dt>Plan expires</dt>
                    <dd>
                        <?php
                        if ($plan_expiry !== '' && $plan_expiry !== '0000-00-00') {
                            echo htmlspecialchars($plan_expiry, ENT_QUOTES, 'UTF-8');
                            if ($days_left !== null) {
                                if ((int) $days_left < 0) {
                                    echo ' <span class="pa-badge pa-badge-danger">Expired</span>';
                                } elseif ((int) $days_left === 0) {
                                    echo ' <span class="pa-badge pa-badge-warning">Expires today</span>';
                                } else {
                                    echo ' <span class="text-muted">(' . (int) $days_left . ' day' . ((int) $days_left === 1 ? '' : 's') . ' left)</span>';
                                }
                            }
                        } else {
                            echo '<span class="text-muted">—</span>';
                        }
                        ?>
                    </dd>
                </div>
                <div class="pa-detail-row">
                    <dt>Location</dt>
                    <dd>
                        <?php
                        $location_parts = array_filter(array(
                            isset($tenant['city']) ? trim((string) $tenant['city']) : '',
                            isset($tenant['country']) ? trim((string) $tenant['country']) : '',
                        ));
                        echo !empty($location_parts)
                            ? htmlspecialchars(implode(', ', $location_parts), ENT_QUOTES, 'UTF-8')
                            : '<span class="text-muted">—</span>';
                        ?>
                    </dd>
                </div>
                <div class="pa-detail-row">
                    <dt>Payment method</dt>
                    <dd><?php echo !empty($tenant['payment_method']) ? htmlspecialchars($tenant['payment_method'], ENT_QUOTES, 'UTF-8') : '—'; ?></dd>
                </div>
                <div class="pa-detail-row">
                    <dt>Balance</dt>
                    <dd><?php echo htmlspecialchars($currency_symbol, ENT_QUOTES, 'UTF-8') . $format_amount(isset($tenant['balance']) ? (float) $tenant['balance'] : 0); ?></dd>
                </div>
                <?php if (!empty($tenant['conversion_date']) && $tenant['conversion_date'] !== '0000-00-00') { ?>
                <div class="pa-detail-row">
                    <dt>Converted</dt>
                    <dd><?php echo htmlspecialchars($tenant['conversion_date'], ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <?php } ?>
                <?php if (!empty($booking_stats['last_booking_date'])) { ?>
                <div class="pa-detail-row">
                    <dt>Last booking</dt>
                    <dd><?php echo htmlspecialchars($booking_stats['last_booking_date'], ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <?php } ?>
            </dl>
        </div>
    </div>

    <div class="pa-card">
        <div class="pa-card-header"><h2>Admin actions</h2></div>
        <div class="pa-card-body">
            <div class="pa-form-stack">
                <div class="form-group">
                    <label for="tenant-subscription-state">Subscription state</label>
                    <select class="form-control input-sm" id="tenant-subscription-state">
                        <?php
                        $states = array('trialing', 'active', 'unpaid', 'canceled', 'trial_ended');
                        foreach ($states as $st) {
                            $sel = ($state === $st) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($st, ENT_QUOTES, 'UTF-8') . '" ' . $sel . '>'
                                . htmlspecialchars(ucfirst(str_replace('_', ' ', $st)), ENT_QUOTES, 'UTF-8')
                                . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tenant-pricing-tier">Pricing tier</label>
                    <select class="form-control input-sm" id="tenant-pricing-tier">
                        <?php
                        $selected_id = isset($tenant['pricing_tier_id']) ? (int) $tenant['pricing_tier_id'] : 0;
                        foreach ($tiers as $tier) {
                            $id = (int) $tier['id'];
                            $name = isset($tier['name']) ? (string) $tier['name'] : '';
                            $sel = ($selected_id > 0 && $id === $selected_id) ? 'selected' : '';
                            echo '<option value="' . $id . '" ' . $sel . '>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tenant-plan-expiry-date">Plan expiry date</label>
                    <div class="input-group input-group-sm">
                        <input type="date" class="form-control" id="tenant-plan-expiry-date"
                               value="<?php echo htmlspecialchars($plan_expiry, ENT_QUOTES, 'UTF-8'); ?>" />
                        <span class="input-group-btn">
                            <button type="button" class="btn btn-default" id="tenant-plan-expiry-save">Save date</button>
                        </span>
                    </div>
                </div>
                <?php if ($state === 'trialing') { ?>
                <div class="form-group">
                    <label for="tenant-trial-days">Extend trial (days from today)</label>
                    <div class="input-group input-group-sm">
                        <input type="number" min="1" max="365" class="form-control" id="tenant-trial-days"
                               placeholder="Trial days" value="<?php echo (int) (isset($default_trial_days) ? $default_trial_days : 14); ?>" />
                        <span class="input-group-btn">
                            <button type="button" class="btn btn-default" id="tenant-trial-save">Set trial</button>
                        </span>
                    </div>
                </div>
                <?php } ?>

                <hr style="margin: 20px 0; border-color: var(--pa-border, #e2e8f0);" />

                <h3 class="pa-subheading" style="margin-top: 0;">Record manual payment</h3>
                <p class="pa-meta-note">For bank transfers and offline billing. Activates subscription and extends plan expiry.</p>
                <div class="form-group">
                    <label for="tenant-payment-amount">Amount (<?php echo htmlspecialchars($currency_symbol, ENT_QUOTES, 'UTF-8'); ?>)</label>
                    <input type="number" min="0.01" step="0.01" class="form-control input-sm" id="tenant-payment-amount"
                           value="<?php echo htmlspecialchars((string) (isset($default_payment_amount) ? $default_payment_amount : 0), ENT_QUOTES, 'UTF-8'); ?>" />
                </div>
                <div class="form-group">
                    <label for="tenant-payment-period">Renewal period</label>
                    <select class="form-control input-sm" id="tenant-payment-period">
                        <option value="1 month">1 month</option>
                        <option value="1 year">1 year</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tenant-payment-date">Payment date</label>
                    <input type="date" class="form-control input-sm" id="tenant-payment-date"
                           value="<?php echo htmlspecialchars(gmdate('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>" />
                </div>
                <div class="form-group">
                    <label for="tenant-payment-notes">Notes</label>
                    <textarea class="form-control input-sm" id="tenant-payment-notes" rows="2"
                              placeholder="Bank transfer ref, invoice #, etc."></textarea>
                </div>
                <button type="button" class="pa-btn pa-btn-primary pa-btn-sm" id="tenant-record-payment">Record payment</button>
                <button type="button" class="pa-btn pa-btn-secondary pa-btn-sm" id="tenant-extend-grace"
                        data-grace-days="<?php echo (int) (isset($default_grace_days) ? $default_grace_days : saas_paid_plan_grace_days()); ?>">
                    Extend grace (<?php echo (int) (isset($default_grace_days) ? $default_grace_days : saas_paid_plan_grace_days()); ?> days)
                </button>
                <button type="button" class="pa-btn pa-btn-secondary pa-btn-sm" id="tenant-send-reminder">Send reminder</button>
            </div>
        </div>
    </div>
</div>

<div class="pa-grid-2" style="margin-top: 24px;">
    <div class="pa-card">
        <div class="pa-card-header"><h2>Setup progress</h2></div>
        <div class="pa-card-body">
            <?php if (empty($setup_progress)) { ?>
                <div class="pa-empty">No setup data available</div>
            <?php } else { ?>
                <ul class="pa-checklist">
                    <?php foreach ($setup_progress as $item) {
                        $done = !empty($item['done']);
                        ?>
                        <li class="pa-checklist-item<?php echo $done ? ' is-done' : ''; ?>">
                            <i class="fa <?php echo $done ? 'fa-check-circle' : 'fa-circle-o'; ?>" aria-hidden="true"></i>
                            <span>
                                <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php if (!empty($item['detail'])) { ?>
                                    <span class="text-muted">(<?php echo htmlspecialchars($item['detail'], ENT_QUOTES, 'UTF-8'); ?>)</span>
                                <?php } ?>
                            </span>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>
    </div>

    <div class="pa-card">
        <div class="pa-card-header"><h2>Extensions &amp; channels</h2></div>
        <div class="pa-card-body">
            <h3 class="pa-subheading">Active extensions</h3>
            <?php if (empty($extensions)) { ?>
                <p class="text-muted">No optional extensions enabled.</p>
            <?php } else { ?>
                <div class="pa-tag-list">
                    <?php foreach ($extensions as $extension) { ?>
                        <span class="pa-tag"><?php echo htmlspecialchars(pa_format_extension_name($extension['extension_name']), ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php } ?>
                </div>
            <?php } ?>

            <h3 class="pa-subheading" style="margin-top: 20px;">Channels</h3>
            <?php if (empty($channels)) { ?>
                <p class="text-muted">No channel data.</p>
            <?php } else { ?>
                <ul class="pa-channel-list">
                    <?php foreach ($channels as $channel) { ?>
                        <li>
                            <strong><?php echo htmlspecialchars($channel['label'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <?php if (!empty($channel['connected'])) { ?>
                                <span class="pa-badge pa-badge-active">Connected</span>
                            <?php } else { ?>
                                <span class="pa-badge pa-badge-muted">Not connected</span>
                            <?php } ?>
                            <span class="pa-channel-detail"><?php echo htmlspecialchars($channel['detail'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>
    </div>
</div>

<div class="pa-grid-2" style="margin-top: 24px;">
    <div class="pa-card">
        <div class="pa-card-header"><h2>Internal admin notes</h2></div>
        <div class="pa-card-body">
            <p class="pa-meta-note">Persistent notes for support — not visible to the hotel.</p>
            <textarea class="form-control" id="tenant-admin-comment" rows="6"
                      placeholder="Support notes, context, follow-ups…"><?php echo htmlspecialchars(isset($tenant['admin_comment']) ? $tenant['admin_comment'] : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            <div style="margin-top: 12px;">
                <button type="button" class="pa-btn pa-btn-primary pa-btn-sm" id="tenant-save-admin-comment">Save notes</button>
            </div>
        </div>
    </div>

    <div class="pa-card">
        <div class="pa-card-header"><h2>Activity log</h2></div>
        <div class="pa-card-body">
            <?php if (!$activity_log_enabled) { ?>
                <div class="pa-alert pa-alert-warning">
                    Activity log requires migration 019. Run database migrations to enable notes and action history.
                </div>
            <?php } else { ?>
                <div class="form-group">
                    <label for="tenant-activity-note">Add note</label>
                    <textarea class="form-control" id="tenant-activity-note" rows="2" placeholder="Quick support note…"></textarea>
                    <button type="button" class="pa-btn pa-btn-secondary pa-btn-sm" id="tenant-add-activity-note" style="margin-top: 8px;">Add to log</button>
                </div>
            <?php } ?>

            <?php if (empty($activity_log)) { ?>
                <div class="pa-empty" style="margin-top: 16px;">No activity recorded yet</div>
            <?php } else { ?>
                <ul class="pa-activity-log">
                    <?php foreach ($activity_log as $entry) {
                        $author = 'System';
                        if (!empty($entry['user_email'])) {
                            $author = $entry['user_email'];
                        } elseif (!empty($entry['first_name']) || !empty($entry['last_name'])) {
                            $author = trim($entry['first_name'] . ' ' . $entry['last_name']);
                        }
                        $type = isset($entry['log_type']) ? (string) $entry['log_type'] : 'note';
                        ?>
                        <li class="pa-activity-log-item pa-activity-log-item--<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="pa-activity-log-meta">
                                <time><?php echo htmlspecialchars((string) $entry['date_time'], ENT_QUOTES, 'UTF-8'); ?></time>
                                · <?php echo htmlspecialchars($author, ENT_QUOTES, 'UTF-8'); ?>
                                · <?php echo htmlspecialchars(ucfirst($type), ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <div class="pa-activity-log-body"><?php echo nl2br(htmlspecialchars((string) $entry['log'], ENT_QUOTES, 'UTF-8')); ?></div>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>
    </div>
</div>

<div class="pa-card" style="margin-top: 24px;">
    <div class="pa-card-header">
        <h2>Payment history</h2>
        <p style="margin: 8px 0 0; font-size: 13px; color: var(--pa-muted, #64748b);">
            SaaS subscription charges for this property (most recent first).
        </p>
    </div>
    <div class="pa-card-body pa-table-wrap">
        <table class="pa-table">
            <thead>
                <tr>
                    <th>Paid date</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Reference</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subscription_payments)) { ?>
                    <tr>
                        <td colspan="4" class="pa-empty" style="padding: 32px;">No subscription payments recorded</td>
                    </tr>
                <?php } else {
                    foreach ($subscription_payments as $payment) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) $payment['paid_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $payment['payment_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><strong><?php echo htmlspecialchars($currency_symbol, ENT_QUOTES, 'UTF-8'); ?><?php echo $format_amount((float) $payment['amount']); ?></strong></td>
                            <td style="font-size: 12px; word-break: break-all;"><?php echo htmlspecialchars((string) $payment['reference'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php }
                } ?>
            </tbody>
        </table>
    </div>
</div>

</div>
