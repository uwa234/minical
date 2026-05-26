<?php
$trial_context = isset($trial_context) ? $trial_context : null;
$current_plan_name = isset($current_plan_name) ? $current_plan_name : '';
$subscription_state = isset($subscription_state) ? $subscription_state : '';
?>

<div class="dashboard-page">
    <div class="dashboard-header">
        <div class="dashboard-header-text">
            <h1 class="dashboard-title"><?php echo l('trial_manage_billing', true); ?></h1>
            <?php if ($subscription_state === 'trialing'): ?>
                <p class="dashboard-subtitle"><?php echo l('trial_banner_subtitle', true); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($trial_context): ?>
        <?php $this->load->view('dashboard/trial_banner', array('trial_context' => $trial_context)); ?>
    <?php else: ?>
        <div class="dashboard-trial-plan-current" style="margin-bottom: 1.5rem;">
            <p class="dashboard-trial-plan-label"><?php echo l('trial_current_plan', true); ?></p>
            <p class="dashboard-trial-plan-name"><?php echo htmlspecialchars($current_plan_name, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    <?php endif; ?>
</div>
