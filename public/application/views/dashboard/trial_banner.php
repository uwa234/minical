<?php
$ctx = isset($trial_context) ? $trial_context : null;
if (!$ctx) {
    return;
}
$upgrade_url = isset($ctx['upgrade_url']) ? $ctx['upgrade_url'] : base_url('account_settings/subscription');
$tiers = isset($ctx['pricing_tiers']) ? $ctx['pricing_tiers'] : array();
$expiry_label = '';
if (!empty($ctx['trial_expiry_date'])) {
    $expiry_label = sprintf(l('trial_expires_on', true), date('F j, Y', strtotime($ctx['trial_expiry_date'])));
}
?>
<section class="dashboard-trial" aria-labelledby="dashboard-trial-heading">
    <div class="dashboard-trial-main">
        <div class="dashboard-trial-intro">
            <span class="dashboard-trial-badge"><?php echo l('trial_banner_badge', true); ?></span>
            <h2 id="dashboard-trial-heading" class="dashboard-trial-title"><?php echo l('trial_banner_title', true); ?></h2>
            <p class="dashboard-trial-subtitle"><?php echo l('trial_banner_subtitle', true); ?></p>
            <?php if ($expiry_label): ?>
                <p class="dashboard-trial-expiry-date"><?php echo htmlspecialchars($expiry_label, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
        </div>

        <div class="dashboard-trial-countdown-wrap">
            <p class="dashboard-trial-countdown-label"><?php echo l('trial_countdown_label', true); ?></p>
            <div class="dashboard-trial-countdown" id="dashboard-trial-countdown" aria-live="polite">
                <div class="dashboard-trial-countdown-unit">
                    <span class="dashboard-trial-countdown-value" id="dashboard-trial-days">—</span>
                    <span class="dashboard-trial-countdown-unit-label"><?php echo l('trial_countdown_days', true); ?></span>
                </div>
                <span class="dashboard-trial-countdown-sep" aria-hidden="true">:</span>
                <div class="dashboard-trial-countdown-unit">
                    <span class="dashboard-trial-countdown-value" id="dashboard-trial-hours">—</span>
                    <span class="dashboard-trial-countdown-unit-label"><?php echo l('trial_countdown_hours', true); ?></span>
                </div>
                <span class="dashboard-trial-countdown-sep" aria-hidden="true">:</span>
                <div class="dashboard-trial-countdown-unit">
                    <span class="dashboard-trial-countdown-value" id="dashboard-trial-minutes">—</span>
                    <span class="dashboard-trial-countdown-unit-label"><?php echo l('trial_countdown_minutes', true); ?></span>
                </div>
            </div>
            <a class="btn btn-primary dashboard-trial-cta" href="<?php echo htmlspecialchars($upgrade_url, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo l('trial_manage_billing', true); ?>
            </a>
        </div>
    </div>

    <div class="dashboard-trial-plan-current">
        <p class="dashboard-trial-plan-label"><?php echo l('trial_current_plan', true); ?></p>
        <p class="dashboard-trial-plan-name"><?php echo htmlspecialchars($ctx['current_plan_name'], ENT_QUOTES, 'UTF-8'); ?></p>
        <p class="dashboard-trial-plan-hint">
            <?php echo sprintf(l('trial_current_plan_hint', true), (int) $ctx['room_count']); ?>
        </p>
    </div>

    <?php if (!empty($tiers)): ?>
        <div class="dashboard-trial-plans">
            <header class="dashboard-trial-plans-header">
                <h3><?php echo l('trial_upgrade_heading', true); ?></h3>
                <p><?php echo l('trial_upgrade_subheading', true); ?></p>
            </header>
            <div class="dashboard-trial-plans-grid">
                <?php foreach ($tiers as $tier):
                    $max_rooms = ($tier['max_rooms'] === null || $tier['max_rooms'] === '') ? '' : (int) $tier['max_rooms'];
                    $room_label = (int) $tier['min_rooms'] . ' – ' . ($max_rooms === '' ? '∞' : $max_rooms) . ' rooms';
                    $is_current = !empty($tier['is_current']);
                    $is_upgrade = !empty($tier['is_upgrade']);
                    $card_class = 'dashboard-trial-plan-card';
                    if ($is_current) {
                        $card_class .= ' dashboard-trial-plan-card--current';
                    } elseif ($is_upgrade) {
                        $card_class .= ' dashboard-trial-plan-card--upgrade';
                    }
                ?>
                    <article class="<?php echo $card_class; ?>">
                        <?php if ($is_current): ?>
                            <span class="dashboard-trial-plan-tag dashboard-trial-plan-tag--current"><?php echo l('trial_tier_current', true); ?></span>
                        <?php elseif ($is_upgrade): ?>
                            <span class="dashboard-trial-plan-tag dashboard-trial-plan-tag--upgrade"><?php echo l('trial_tier_upgrade', true); ?></span>
                        <?php endif; ?>
                        <h4><?php echo htmlspecialchars($tier['name'], ENT_QUOTES, 'UTF-8'); ?></h4>
                        <p class="dashboard-trial-plan-rooms"><?php echo htmlspecialchars($room_label, ENT_QUOTES, 'UTF-8'); ?></p>
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
                        <?php if ($is_current): ?>
                            <span class="btn btn-default btn-block dashboard-trial-plan-btn" disabled><?php echo l('trial_tier_current', true); ?></span>
                        <?php else: ?>
                            <a class="btn btn-primary btn-block dashboard-trial-plan-btn" href="<?php echo htmlspecialchars($upgrade_url, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo $is_upgrade ? l('trial_choose_plan', true) : l('trial_tier_contact', true); ?>
                            </a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <script type="application/json" id="dashboard-trial-data"><?php
        echo json_encode(array(
            'expiresAt' => isset($ctx['trial_expires_at']) ? (int) $ctx['trial_expires_at'] : 0,
            'expiredLabel' => l('trial_countdown_expired', true),
        ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    ?></script>
</section>
