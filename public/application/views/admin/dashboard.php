<?php
$currency_symbol = isset($saas_currency_symbol) ? $saas_currency_symbol : saas_currency_symbol();
$format_amount = function ($amount) {
    return format_saas_price_amount($amount);
};
$lifetime_total = isset($subscription_revenue_lifetime['total_revenue']) ? (float) $subscription_revenue_lifetime['total_revenue'] : 0;
?>
<?php if ($this->session->flashdata('admin_message')) { ?>
    <div class="pa-alert pa-alert-success">
        <i class="fa fa-check-circle" aria-hidden="true"></i>
        <?php echo htmlspecialchars($this->session->flashdata('admin_message'), ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php } ?>

<div class="pa-page-header">
    <h1>Platform overview</h1>
    <p>Monitor tenants, subscriptions, and trial health at a glance.</p>
</div>

<div class="pa-stats">
    <div class="pa-stat-card pa-stat-card--accent">
        <div class="pa-stat-label">Total tenants</div>
        <div class="pa-stat-value"><?php echo (int) $state_counts['total']; ?></div>
    </div>
    <div class="pa-stat-card pa-stat-card--info">
        <div class="pa-stat-label">Trialing</div>
        <div class="pa-stat-value"><?php echo (int) $state_counts['trialing']; ?></div>
    </div>
    <div class="pa-stat-card pa-stat-card--success">
        <div class="pa-stat-label">Active</div>
        <div class="pa-stat-value"><?php echo (int) $state_counts['active']; ?></div>
    </div>
    <div class="pa-stat-card pa-stat-card--warning">
        <div class="pa-stat-label">Lifetime subscription revenue</div>
        <div class="pa-stat-value"><?php echo htmlspecialchars($currency_symbol, ENT_QUOTES, 'UTF-8'); ?><?php echo $format_amount($lifetime_total); ?></div>
    </div>
</div>

<div class="pa-grid-2">
    <div class="pa-card">
        <div class="pa-card-header">
            <h2>Subscription breakdown</h2>
        </div>
        <div class="pa-card-body pa-table-wrap">
            <table class="pa-table">
                <thead>
                    <tr><th>State</th><th>Count</th></tr>
                </thead>
                <tbody>
                <?php foreach (array('trialing', 'active', 'unpaid', 'canceled', 'trial_ended') as $st) { ?>
                    <tr>
                        <td>
                            <span class="pa-badge pa-badge-<?php echo htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $st)); ?>
                            </span>
                        </td>
                        <td><strong><?php echo (int) $state_counts[$st]; ?></strong></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
            <p class="pa-meta-note">
                Default trial length: <strong><?php echo (int) $default_trial_days; ?> days</strong>
                — <a href="<?php echo base_url('admin/settings'); ?>">Edit in settings</a>
            </p>
        </div>
    </div>

    <div class="pa-card">
        <div class="pa-card-header">
            <h2>Trials expiring within 7 days</h2>
            <?php if (!empty($trials_expiring)) { ?>
                <span class="pa-badge pa-badge-unpaid"><?php echo count($trials_expiring); ?> upcoming</span>
            <?php } ?>
        </div>
        <div class="pa-card-body pa-table-wrap">
            <?php if (empty($trials_expiring)) { ?>
                <div class="pa-empty">
                    <i class="fa fa-calendar-check-o" aria-hidden="true"></i>
                    No trials expiring soon
                </div>
            <?php } else { ?>
                <table class="pa-table">
                    <thead>
                        <tr><th>Property</th><th>Expires</th><th>Owner</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($trials_expiring as $t) { ?>
                        <tr>
                            <td>
                                <a href="<?php echo base_url('admin/property_detail/' . (int) $t['company_id']); ?>">
                                    <?php echo htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($t['trial_expiry_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($t['owner_email'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>
    </div>
</div>

<div class="pa-grid-2">
    <div class="pa-card">
        <div class="pa-card-header">
            <h2>Trial sign-ups awaiting conversion</h2>
            <?php if (!empty($new_trial_leads_count)) { ?>
                <span class="pa-badge pa-badge-info"><?php echo (int) $new_trial_leads_count; ?> new</span>
            <?php } ?>
        </div>
        <div class="pa-card-body pa-table-wrap">
            <?php if (empty($recent_trial_leads)) { ?>
                <div class="pa-empty">
                    <i class="fa fa-envelope-open-o" aria-hidden="true"></i>
                    No unconverted trial sign-ups
                </div>
            <?php } else { ?>
                <table class="pa-table">
                    <thead>
                        <tr><th>Property</th><th>Contact</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recent_trial_leads as $lead) {
                        $contact = trim($lead['first_name'] . ' ' . $lead['last_name']);
                        $lead_id = (int) $lead['trial_registration_request_id'];
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($lead['property_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span class="text-muted"> · <?php echo (int) $lead['number_of_rooms']; ?> rooms</span>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($contact, ENT_QUOTES, 'UTF-8'); ?>
                                <br /><span class="text-muted"><?php echo htmlspecialchars($lead['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td>
                                <span class="pa-badge pa-badge-<?php echo htmlspecialchars($lead['status'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo ucfirst(htmlspecialchars($lead['status'], ENT_QUOTES, 'UTF-8')); ?>
                                </span>
                            </td>
                            <td class="pa-actions-cell">
                                <a class="pa-btn pa-btn-primary pa-btn-sm"
                                   href="<?php echo base_url('admin/trial_registrations?from_lead=' . $lead_id); ?>">
                                    Create property
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
            <p class="pa-meta-note">
                <a href="<?php echo base_url('admin/trial_registrations'); ?>">View all trial sign-ups</a>
            </p>
        </div>
    </div>
</div>

<div class="pa-btn-group">
    <a class="pa-btn pa-btn-primary" href="<?php echo base_url('admin/property_list'); ?>">
        <i class="fa fa-building" aria-hidden="true"></i> Manage properties
    </a>
    <a class="pa-btn pa-btn-secondary" href="<?php echo base_url('admin/revenue'); ?>">
        <i class="fa fa-line-chart" aria-hidden="true"></i> Revenue report
    </a>
    <a class="pa-btn pa-btn-secondary" href="<?php echo base_url('admin/debtors'); ?>">
        <i class="fa fa-exclamation-circle" aria-hidden="true"></i> Subscription debtors
    </a>
</div>
