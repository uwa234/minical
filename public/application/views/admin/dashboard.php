<?php
$currency_symbol = isset($saas_currency_symbol) ? $saas_currency_symbol : saas_currency_symbol();
$format_amount = function ($amount) {
    return format_saas_price_amount($amount);
};
$lifetime_total = isset($subscription_revenue_lifetime['total_revenue']) ? (float) $subscription_revenue_lifetime['total_revenue'] : 0;
$mrr = isset($mrr_estimate) ? (float) $mrr_estimate : 0;
$arr = isset($arr_estimate) ? (float) $arr_estimate : ($mrr * 12);
$month_metrics = isset($month_saas_metrics) && is_array($month_saas_metrics) ? $month_saas_metrics : array();
$debtor_total = isset($debtor_outstanding) && is_array($debtor_outstanding) ? $debtor_outstanding : array('total' => 0, 'count' => 0);
$conversions = isset($month_metrics['conversions']) ? (int) $month_metrics['conversions'] : 0;
$new_trials = isset($month_metrics['new_trials']) ? (int) $month_metrics['new_trials'] : 0;
$conversion_rate = array_key_exists('conversion_rate', $month_metrics) ? $month_metrics['conversion_rate'] : null;
$churn_count = isset($month_metrics['churn_count']) ? (int) $month_metrics['churn_count'] : 0;
$month_label = isset($month_metrics['month_label']) ? (string) $month_metrics['month_label'] : date('F Y');
$outstanding_amount = isset($debtor_total['total']) ? (float) $debtor_total['total'] : 0;
$debtor_count = isset($debtor_total['count']) ? (int) $debtor_total['count'] : 0;
$comm_summary = isset($communication_summary) && is_array($communication_summary) ? $communication_summary : array();
$active_announcements = isset($comm_summary['active_announcements']) ? (int) $comm_summary['active_announcements'] : 0;
$email_template_count = isset($comm_summary['email_templates']) ? (int) $comm_summary['email_templates'] : 0;
$webhooks_configured = !empty($comm_summary['webhooks_configured']);
?>
<?php if ($this->session->flashdata('admin_message')) { ?>
    <div class="pa-alert pa-alert-success">
        <i class="fa fa-check-circle" aria-hidden="true"></i>
        <?php echo htmlspecialchars($this->session->flashdata('admin_message'), ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php } ?>

<div class="pa-page-header">
    <h1>Platform overview</h1>
    <p>Monitor tenants, subscriptions, revenue, and trial health at a glance.</p>
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
</div>

<h2 class="pa-stats-heading">Revenue &amp; subscriptions</h2>
<div class="pa-stats pa-stats--wide">
    <div class="pa-stat-card pa-stat-card--warning">
        <div class="pa-stat-label">Lifetime revenue</div>
        <div class="pa-stat-value pa-stat-value-sm"><?php echo htmlspecialchars($currency_symbol, ENT_QUOTES, 'UTF-8'); ?><?php echo $format_amount($lifetime_total); ?></div>
        <div class="pa-stat-sub">Cash collected · all time</div>
    </div>
    <div class="pa-stat-card pa-stat-card--accent">
        <div class="pa-stat-label">MRR</div>
        <div class="pa-stat-value pa-stat-value-sm"><?php echo htmlspecialchars($currency_symbol, ENT_QUOTES, 'UTF-8'); ?><?php echo $format_amount($mrr); ?></div>
        <div class="pa-stat-sub">ARR <?php echo htmlspecialchars($currency_symbol, ENT_QUOTES, 'UTF-8'); ?><?php echo $format_amount($arr); ?> · active plans</div>
    </div>
    <div class="pa-stat-card pa-stat-card--success">
        <div class="pa-stat-label">Trial conversion</div>
        <div class="pa-stat-value pa-stat-value-sm">
            <?php echo $conversion_rate === null ? '—' : htmlspecialchars(number_format($conversion_rate, 1), ENT_QUOTES, 'UTF-8') . '%'; ?>
        </div>
        <div class="pa-stat-sub">
            <?php echo $conversions; ?> converted · <?php echo $new_trials; ?> new trials · <?php echo htmlspecialchars($month_label, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    </div>
    <div class="pa-stat-card pa-stat-card--danger">
        <div class="pa-stat-label">Churn this month</div>
        <div class="pa-stat-value"><?php echo $churn_count; ?></div>
        <div class="pa-stat-sub"><?php echo htmlspecialchars($month_label, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
    <div class="pa-stat-card pa-stat-card--danger">
        <div class="pa-stat-label">Outstanding debtors</div>
        <div class="pa-stat-value pa-stat-value-sm"><?php echo htmlspecialchars($currency_symbol, ENT_QUOTES, 'UTF-8'); ?><?php echo $format_amount($outstanding_amount); ?></div>
        <div class="pa-stat-sub">
            <?php echo $debtor_count; ?> propert<?php echo $debtor_count === 1 ? 'y' : 'ies'; ?> requiring payment
            · <a href="<?php echo base_url('admin/debtors'); ?>">View list</a>
        </div>
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

<h2 class="pa-stats-heading">Growth &amp; communication</h2>
<div class="pa-stats pa-stats--wide">
    <div class="pa-stat-card pa-stat-card--info">
        <div class="pa-stat-label">Email templates</div>
        <div class="pa-stat-value"><?php echo $email_template_count; ?></div>
        <div class="pa-stat-sub">Trial outreach, payment reminders, win-back</div>
    </div>
    <div class="pa-stat-card pa-stat-card--accent">
        <div class="pa-stat-label">Active announcements</div>
        <div class="pa-stat-value"><?php echo $active_announcements; ?></div>
        <div class="pa-stat-sub">Tenant in-app banners</div>
    </div>
    <div class="pa-stat-card pa-stat-card--<?php echo $webhooks_configured ? 'success' : 'warning'; ?>">
        <div class="pa-stat-label">Webhook / Slack</div>
        <div class="pa-stat-value pa-stat-value-sm"><?php echo $webhooks_configured ? 'Configured' : 'Not set'; ?></div>
        <div class="pa-stat-sub">Trial sign-ups, payments, debtors</div>
    </div>
</div>

<div class="pa-grid-2">
    <div class="pa-card">
        <div class="pa-card-header">
            <h2>Growth &amp; communication</h2>
        </div>
        <div class="pa-card-body">
            <ul class="pa-feature-list">
                <li><strong>Email templates</strong> — Outreach to trial leads, payment reminders, win-back for churned tenants</li>
                <li><strong>In-app announcements</strong> — Banner to all tenants (maintenance, new features)</li>
                <li><strong>Webhook / Slack alert</strong> — Notify on new trial signup, payment received, debtor overdue</li>
            </ul>
            <p class="pa-meta-note" style="margin-top:16px;">
                <a class="pa-btn pa-btn-primary pa-btn-sm" href="<?php echo base_url('admin/communication'); ?>">
                    <i class="fa fa-bullhorn" aria-hidden="true"></i> Manage communication
                </a>
            </p>
        </div>
    </div>
</div>

<div class="pa-btn-group">
    <a class="pa-btn pa-btn-primary" href="<?php echo base_url('admin/property_list'); ?>">
        <i class="fa fa-building" aria-hidden="true"></i> Manage properties
    </a>
    <a class="pa-btn pa-btn-secondary" href="<?php echo base_url('admin/tenant_health'); ?>">
        <i class="fa fa-heartbeat" aria-hidden="true"></i> Tenant health
    </a>
    <a class="pa-btn pa-btn-secondary" href="<?php echo base_url('admin/analytics'); ?>">
        <i class="fa fa-bar-chart" aria-hidden="true"></i> Growth &amp; analytics
    </a>
    <a class="pa-btn pa-btn-secondary" href="<?php echo base_url('admin/revenue'); ?>">
        <i class="fa fa-line-chart" aria-hidden="true"></i> Revenue report
    </a>
    <a class="pa-btn pa-btn-secondary" href="<?php echo base_url('admin/debtors'); ?>">
        <i class="fa fa-exclamation-circle" aria-hidden="true"></i> Subscription debtors
    </a>
    <a class="pa-btn pa-btn-secondary" href="<?php echo base_url('admin/communication'); ?>">
        <i class="fa fa-bullhorn" aria-hidden="true"></i> Communication
    </a>
</div>
