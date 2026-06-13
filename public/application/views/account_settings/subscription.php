<?php
$trial_context = isset($trial_context) ? $trial_context : null;
$current_plan_name = isset($current_plan_name) ? $current_plan_name : '';
$subscription_state = isset($subscription_state) ? $subscription_state : '';
$pricing_tiers = isset($pricing_tiers) && is_array($pricing_tiers) ? $pricing_tiers : array();
$room_count = isset($room_count) ? (int) $room_count : 1;
$paystack_configured = !empty($paystack_configured);
$subscription_flash = isset($subscription_flash) && is_array($subscription_flash) ? $subscription_flash : null;
$plan_expiry_context = isset($plan_expiry_context) && is_array($plan_expiry_context) ? $plan_expiry_context : null;
$trial_lockout_mode = !empty($trial_lockout_mode);
$billing_lockout_reason = isset($billing_lockout_reason) ? $billing_lockout_reason : null;
$plan_past_grace_lockout = ($billing_lockout_reason === 'plan_past_grace');
$trial_expired = ($subscription_state === 'trial_ended') || ($trial_context && !empty($trial_context['is_expired']));
$current_saas_tier = isset($current_saas_tier) ? $current_saas_tier : null;
$paid_plan_active = is_array($plan_expiry_context) && (int) $plan_expiry_context['days_left'] > 0;
$paid_plan_in_grace = is_array($plan_expiry_context) && !empty($plan_expiry_context['is_in_grace']);
$trial_expiry_label = '';
if ($trial_context && !empty($trial_context['trial_expiry_date'])) {
    $trial_expiry_label = date('F j, Y', strtotime($trial_context['trial_expiry_date']));
}
?>

<div class="dashboard-page<?php echo $trial_lockout_mode ? ' dashboard-page--trial-lockout' : ''; ?>">
    <div class="dashboard-header">
        <div class="dashboard-header-text">
            <h1 class="dashboard-title">
                <?php echo $trial_lockout_mode ? 'Renew your subscription' : l('trial_manage_billing', true); ?>
            </h1>
            <?php if ($subscription_state === 'trialing'): ?>
                <p class="dashboard-subtitle"><?php echo l('trial_banner_subtitle', true); ?></p>
            <?php elseif ($trial_expired && !$plan_past_grace_lockout): ?>
                <p class="dashboard-subtitle">Your free trial has ended. Choose a plan and pay to restore full access.</p>
            <?php elseif ($plan_past_grace_lockout): ?>
                <p class="dashboard-subtitle">Your paid plan expired. Renew below to restore full access to your account.</p>
            <?php elseif ($paid_plan_in_grace): ?>
                <p class="dashboard-subtitle">Your plan has expired. You have a grace period to renew before access is paused.</p>
            <?php endif; ?>
        </div>
        <?php if ($trial_lockout_mode): ?>
            <div class="dashboard-header-actions">
                <a class="btn btn-default" href="<?php echo base_url('auth/logout'); ?>">Log out</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($trial_lockout_mode): ?>
        <div class="alert alert-danger trial-lockout-notice" role="alert">
            <strong>Account access paused.</strong>
            <?php if ($plan_past_grace_lockout && $plan_expiry_context): ?>
                Your subscription expired on <?php echo htmlspecialchars(date('F j, Y', strtotime($plan_expiry_context['expiration_date'])), ENT_QUOTES, 'UTF-8'); ?>.
                The <?php echo (int) $plan_expiry_context['grace_days']; ?>-day grace period ended on
                <?php echo htmlspecialchars(date('F j, Y', strtotime($plan_expiry_context['grace_ends_date'])), ENT_QUOTES, 'UTF-8'); ?>.
                Pay below to restore access immediately.
            <?php else: ?>
                Your trial<?php echo $trial_expiry_label ? ' ended on ' . htmlspecialchars($trial_expiry_label, ENT_QUOTES, 'UTF-8') : ' has ended'; ?>.
                Select a plan below to continue using Veurion.
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!$trial_lockout_mode && $paid_plan_in_grace && $plan_expiry_context): ?>
        <div class="alert alert-warning" role="alert">
            <strong>Grace period:</strong>
            Your plan expired on <?php echo htmlspecialchars(date('F j, Y', strtotime($plan_expiry_context['expiration_date'])), ENT_QUOTES, 'UTF-8'); ?>.
            You have <?php echo (int) $plan_expiry_context['grace_days_remaining']; ?> day<?php echo (int) $plan_expiry_context['grace_days_remaining'] === 1 ? '' : 's'; ?>
            left to renew (until <?php echo htmlspecialchars(date('F j, Y', strtotime($plan_expiry_context['grace_ends_date'])), ENT_QUOTES, 'UTF-8'); ?>).
            After that, access will be paused until payment is received.
        </div>
    <?php endif; ?>

    <?php if ($subscription_flash): ?>
        <div class="alert alert-<?php echo htmlspecialchars($subscription_flash['type'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($subscription_flash['message'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if (!$trial_lockout_mode && $paid_plan_active && empty($plan_expiry_context['is_within_warning'])): ?>
        <div class="alert alert-info">
            Your plan is active until <?php echo htmlspecialchars(date('F j, Y', strtotime($plan_expiry_context['expiration_date'])), ENT_QUOTES, 'UTF-8'); ?>
            (<?php echo (int) $plan_expiry_context['days_left']; ?> day<?php echo (int) $plan_expiry_context['days_left'] === 1 ? '' : 's'; ?> remaining).
            Paying again adds one billing period from that expiry date.
        </div>
    <?php endif; ?>

    <?php if (!$trial_lockout_mode && !$paid_plan_in_grace && $plan_expiry_context && !empty($plan_expiry_context['is_within_warning'])): ?>
        <div class="alert alert-warning">
            <?php
            $days_left = (int) $plan_expiry_context['days_left'];
            if ($days_left === 0) {
                echo 'Your current plan expires today.';
            } else {
                echo 'Your current plan expires in ' . $days_left . ' day' . ($days_left === 1 ? '' : 's') . '.';
            }
            ?>
            <br />
            Expiry date: <?php echo htmlspecialchars($plan_expiry_context['expiration_date'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if ($trial_context && !$trial_expired): ?>
        <?php $this->load->view('dashboard/trial_banner', array(
            'trial_context' => $trial_context,
            'paystack_configured' => $paystack_configured,
            'subscription_state' => $subscription_state,
        )); ?>
    <?php elseif ($trial_context || $current_plan_name): ?>
        <div class="dashboard-trial-plan-current" style="margin-bottom: 1.5rem;">
            <p class="dashboard-trial-plan-label"><?php echo l('trial_current_plan', true); ?></p>
            <p class="dashboard-trial-plan-name"><?php echo htmlspecialchars($current_plan_name, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php if ($trial_expired && $room_count > 0): ?>
                <p class="dashboard-trial-plan-hint">
                    <?php echo sprintf(l('trial_current_plan_hint', true), (int) $room_count); ?>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!($trial_context && !$trial_expired)): ?>
        <div class="dashboard-trial-plans">
            <header class="dashboard-trial-plans-header">
                <h3><?php echo $trial_lockout_mode ? 'Choose a plan to continue' : 'Choose a plan'; ?></h3>
                <p>
                    <?php if ($trial_lockout_mode): ?>
                        Payment unlocks your account immediately after confirmation.
                    <?php elseif ($paid_plan_active): ?>
                        Your subscription is active. You can extend it anytime — each payment adds one billing period from your current expiry date.
                    <?php else: ?>
                        Choose a plan and pay to start or renew your subscription.
                    <?php endif; ?>
                </p>
            </header>

            <?php if (!$paystack_configured): ?>
                <div class="alert alert-warning">
                    Platform Paystack billing is not configured. Set `PAYSTACK_SAAS_SECRET_KEY` in `.env`.
                </div>
            <?php elseif (empty($pricing_tiers)): ?>
                <div class="alert alert-warning">
                    No active pricing tiers are available yet.
                </div>
            <?php else: ?>
                <div class="dashboard-trial-plans-grid">
                    <?php foreach ($pricing_tiers as $tier):
                        $max_rooms = ($tier['max_rooms'] === null || $tier['max_rooms'] === '') ? '' : (int) $tier['max_rooms'];
                        $tier_room_range = $max_rooms === '' ? ((int) $tier['min_rooms']) . '+ rooms' : ((int) $tier['min_rooms']) . '-' . $max_rooms . ' rooms';
                        $fits_room_count = $room_count >= (int) $tier['min_rooms'] && ($max_rooms === '' || $room_count <= $max_rooms);
                        $is_subscribed_tier = $current_saas_tier && (int) $tier['id'] === (int) $current_saas_tier['id'];
                        $tier_button_label = resolve_subscription_tier_button_label(
                            $tier,
                            $current_saas_tier,
                            $subscription_state,
                            $plan_expiry_context,
                            $trial_expired
                        );
                    ?>
                        <article class="dashboard-trial-plan-card<?php echo $is_subscribed_tier ? ' dashboard-trial-plan-card--current' : ''; ?>">
                            <?php if ($is_subscribed_tier): ?>
                                <span class="dashboard-trial-plan-tag dashboard-trial-plan-tag--current">Current plan</span>
                            <?php elseif ($fits_room_count): ?>
                                <span class="dashboard-trial-plan-tag">Recommended for your property</span>
                            <?php endif; ?>
                            <h4><?php echo htmlspecialchars($tier['name'], ENT_QUOTES, 'UTF-8'); ?></h4>
                            <p class="dashboard-trial-plan-rooms"><?php echo htmlspecialchars($tier_room_range, ENT_QUOTES, 'UTF-8'); ?></p>
                            <p class="dashboard-trial-plan-price">
                                <span><?php echo saas_currency_symbol($tier['currency']); ?></span>
                                <?php echo format_saas_price_amount($tier['monthly_price']); ?>
                                <span class="dashboard-trial-plan-period">/mo</span>
                            </p>
                            <?php if (!empty($tier['features']) && is_array($tier['features'])): ?>
                                <ul class="dashboard-trial-plan-features">
                                    <?php foreach ($tier['features'] as $feature): ?>
                                        <li><?php echo htmlspecialchars($feature, ENT_QUOTES, 'UTF-8'); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            <button
                                type="button"
                                class="btn btn-primary btn-block js-paystack-subscribe"
                                data-tier-id="<?php echo (int) $tier['id']; ?>"
                                data-tier-name="<?php echo htmlspecialchars($tier['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-renewal-period="month"
                            >
                                <?php echo htmlspecialchars($tier_button_label, ENT_QUOTES, 'UTF-8'); ?>
                            </button>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
