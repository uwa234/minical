<?php
$platform_feature_groups = isset($platform_feature_groups) ? $platform_feature_groups : marketing_platform_feature_groups();
$saas_highlights = isset($saas_highlights) ? $saas_highlights : marketing_saas_highlights();
$platform_extensions = isset($platform_extensions) ? $platform_extensions : array();
$extensions_contact_email = getenv('SEO_ORG_EMAIL') ?: getenv('MAIL_REPLY_TO');
?>
<?php $this->load->view('includes/marketing_head', marketing_layout_view_vars(get_defined_vars())); ?>

<main id="main">
    <section class="mc-hero">
        <div class="mc-hero-bg" aria-hidden="true"></div>
        <div class="mc-container mc-hero-grid">
            <div class="mc-hero-copy">
                <p class="mc-eyebrow">Cloud PMS for modern hotels</p>
                <h1><?php echo htmlspecialchars($hero_title, ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="mc-hero-lead"><?php echo htmlspecialchars($hero_subtitle, ENT_QUOTES, 'UTF-8'); ?></p>
                <div class="mc-hero-cta">
                    <a class="mc-btn mc-btn-lg mc-btn-solid" href="<?php echo base_url('auth/register'); ?>">
                        Start <?php echo (int) $default_trial_days; ?>-day free trial
                    </a>
                    <a class="mc-btn mc-btn-lg mc-btn-outline-light" href="<?php echo base_url('auth/login'); ?>">Hotel staff login</a>
                </div>
                <ul class="mc-hero-trust">
                    <li>No credit card required</li>
                    <li>Full feature access during trial</li>
                    <li>Setup in minutes</li>
                </ul>
            </div>
            <div class="mc-hero-visual" aria-hidden="true">
                <div class="mc-dashboard">
                    <div class="mc-dashboard-bar">
                        <span></span><span></span><span></span>
                    </div>
                    <div class="mc-dashboard-body">
                        <div class="mc-dash-sidebar"></div>
                        <div class="mc-dash-main">
                            <div class="mc-dash-stat-row">
                                <div class="mc-dash-stat"></div>
                                <div class="mc-dash-stat"></div>
                                <div class="mc-dash-stat highlight"></div>
                            </div>
                            <div class="mc-dash-calendar"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mc-stats">
        <div class="mc-container mc-stats-grid">
            <div class="mc-stat"><strong>One</strong><span>calendar for every room</span></div>
            <div class="mc-stat"><strong>24/7</strong><span>direct bookings online</span></div>
            <div class="mc-stat"><strong>All</strong><span>channels in sync</span></div>
            <div class="mc-stat"><strong><?php echo (int) $default_trial_days; ?> days</strong><span>free to explore</span></div>
        </div>
    </section>

    <section id="features" class="mc-section">
        <div class="mc-container">
            <header class="mc-section-head">
                <p class="mc-eyebrow mc-eyebrow-dark">Platform</p>
                <h2>Complete hotel management software</h2>
                <p class="mc-section-sub">From front desk and night audit to distribution, payments, revenue, and reporting — everything your team needs in one connected Veurion workspace.</p>
            </header>

            <?php foreach ($platform_feature_groups as $group) { ?>
                <div class="mc-feature-group" id="features-<?php echo htmlspecialchars($group['id'], ENT_QUOTES, 'UTF-8'); ?>">
                    <header class="mc-feature-group-head">
                        <h3><?php echo htmlspecialchars($group['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p><?php echo htmlspecialchars($group['summary'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </header>
                    <div class="mc-features mc-features--compact">
                        <?php foreach ($group['features'] as $feature) { ?>
                            <article class="mc-feature-card">
                                <div class="mc-feature-icon" aria-hidden="true"><?php echo $feature['icon']; ?></div>
                                <h4><?php echo htmlspecialchars($feature['title'], ENT_QUOTES, 'UTF-8'); ?></h4>
                                <p><?php echo htmlspecialchars($feature['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </article>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </section>

    <section id="saas" class="mc-section mc-saas-section">
        <div class="mc-container">
            <header class="mc-section-head">
                <p class="mc-eyebrow mc-eyebrow-dark">SaaS</p>
                <h2>Hosted on Veurion — subscribe and scale</h2>
                <p class="mc-section-sub">Veurion is offered as a secure cloud service with room-based plans, online billing, and a full trial so you can evaluate every capability before you commit.</p>
            </header>
            <div class="mc-saas-grid">
                <?php foreach ($saas_highlights as $highlight) { ?>
                    <article class="mc-saas-card">
                        <div class="mc-feature-icon" aria-hidden="true"><?php echo $highlight['icon']; ?></div>
                        <h3><?php echo htmlspecialchars($highlight['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p><?php echo htmlspecialchars($highlight['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </article>
                <?php } ?>
            </div>
            <div class="mc-saas-plans-note">
                <p><strong>Subscription tiers</strong> — Starter, Growth, Professional, and Enterprise plans include front desk tools, direct booking, channel connectivity, payment integrations, revenue management, analytics, and support matched to your property size. See <a href="#pricing">pricing</a> for room-based monthly rates.</p>
            </div>
        </div>
    </section>

    <section id="extensions" class="mc-section mc-extensions-section">
        <div class="mc-container">
            <header class="mc-section-head">
                <p class="mc-eyebrow mc-eyebrow-dark">Extensions</p>
                <h2>Integrations that extend your PMS</h2>
                <p class="mc-section-sub">Activate powerful add-ons inside Veurion — from OTA connectivity and direct bookings to smart locks and WhatsApp guest journeys.</p>
            </header>
            <?php if (!empty($platform_extensions)) { ?>
                <div class="mc-extensions-grid">
                    <?php foreach ($platform_extensions as $extension) { ?>
                        <article class="mc-extension-card">
                            <div class="mc-extension-card-head">
                                <h3><?php echo htmlspecialchars($extension['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <?php if (!empty($extension['categories'])) { ?>
                                    <ul class="mc-extension-tags">
                                        <?php foreach ($extension['categories'] as $tag) { ?>
                                            <li><?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></li>
                                        <?php } ?>
                                    </ul>
                                <?php } ?>
                            </div>
                            <p><?php echo htmlspecialchars($extension['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </article>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <p class="mc-section-sub">Extensions are available from the in-app marketplace after you create your property.</p>
            <?php } ?>
            <p class="mc-extensions-footnote">Need something specific? Custom integrations can be added for your property at an additional charge — <?php if ($extensions_contact_email) { ?><a href="mailto:<?php echo htmlspecialchars($extensions_contact_email, ENT_QUOTES, 'UTF-8'); ?>">contact us</a><?php } else { ?>get in touch via your account manager<?php } ?> to discuss your requirements.</p>
        </div>
    </section>

    <section id="benefits" class="mc-section mc-benefits">
        <div class="mc-container mc-benefits-grid">
            <div class="mc-benefits-copy">
                <p class="mc-eyebrow mc-eyebrow-dark">Why Veurion</p>
                <h2>Built for hoteliers who want time back</h2>
                <p>Stop juggling tools that don't talk to each other. Veurion gives your team one place to work — and your guests a smoother stay.</p>
            </div>
            <ul class="mc-benefit-list">
                <li>
                    <span class="mc-benefit-num">01</span>
                    <div>
                        <strong>Save hours every week</strong>
                        <p>Automate night audit, distribution updates, and repetitive front-desk tasks.</p>
                    </div>
                </li>
                <li>
                    <span class="mc-benefit-num">02</span>
                    <div>
                        <strong>Fewer costly mistakes</strong>
                        <p>One source of truth for reservations, folios, and availability across every channel.</p>
                    </div>
                </li>
                <li>
                    <span class="mc-benefit-num">03</span>
                    <div>
                        <strong>More direct revenue</strong>
                        <p>Own the guest relationship with your booking engine and reduce OTA commission pressure.</p>
                    </div>
                </li>
                <li>
                    <span class="mc-benefit-num">04</span>
                    <div>
                        <strong>Scale without chaos</strong>
                        <p>From boutique inns to multi-property portfolios — add rooms, staff, and integrations as you grow.</p>
                    </div>
                </li>
            </ul>
        </div>
    </section>

    <section id="pricing" class="mc-section mc-pricing-section">
        <div class="mc-container">
            <header class="mc-section-head">
                <p class="mc-eyebrow mc-eyebrow-dark">Pricing</p>
                <h2>Plans that grow with your property</h2>
                <p class="mc-section-sub">Transparent monthly pricing based on room count. No hidden setup fees.</p>
            </header>

            <div class="mc-pricing-calc">
                <label for="mc-room-slider">How many rooms do you have?</label>
                <div class="mc-slider-wrap">
                    <input type="range" id="mc-room-slider" min="1" max="150" value="25" aria-valuemin="1" aria-valuemax="150" />
                    <output class="mc-room-count" id="mc-room-count" for="mc-room-slider">25 rooms</output>
                </div>
            </div>

            <div class="mc-pricing-tiers">
                <?php
                $max_slider = 150;
                $tier_index = 0;
                foreach ($pricing_tiers as $tier) {
                    $tier_index++;
                    $max_rooms = ($tier['max_rooms'] === null || $tier['max_rooms'] === '') ? '' : (int) $tier['max_rooms'];
                    if ($max_rooms && $max_rooms > $max_slider) {
                        $max_slider = $max_rooms;
                    }
                    $room_label = (int) $tier['min_rooms'] . ' – ' . ($max_rooms === '' ? 'Unlimited' : $max_rooms) . ' rooms';
                    $is_popular = ($tier_index === 2);
                    ?>
                    <article class="mc-tier-card<?php echo $is_popular ? ' mc-tier-popular' : ''; ?>"
                             data-tier="<?php echo (int) $tier['id']; ?>"
                             data-min="<?php echo (int) $tier['min_rooms']; ?>"
                             data-max="<?php echo $max_rooms === '' ? '' : $max_rooms; ?>">
                        <?php if ($is_popular) { ?><span class="mc-tier-badge">Most popular</span><?php } ?>
                        <h3><?php echo htmlspecialchars($tier['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p class="mc-tier-rooms"><?php echo htmlspecialchars($room_label, ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="mc-tier-price">
                            <span class="mc-tier-currency"><?php echo saas_currency_symbol($tier['currency']); ?></span>
                            <?php echo format_saas_price_amount($tier['monthly_price']); ?>
                            <span class="mc-tier-period">/month</span>
                        </p>
                        <ul class="mc-tier-features">
                            <?php foreach ($tier['features'] as $feature) { ?>
                                <li><?php echo htmlspecialchars($feature, ENT_QUOTES, 'UTF-8'); ?></li>
                            <?php } ?>
                        </ul>
                        <a class="mc-btn mc-btn-block<?php echo $is_popular ? ' mc-btn-solid' : ' mc-btn-outline'; ?>"
                           href="<?php echo base_url('auth/register'); ?>">Get started</a>
                    </article>
                <?php } ?>
            </div>
        </div>
    </section>

    <section class="mc-cta-band">
        <div class="mc-container mc-cta-inner">
            <h2>Ready to modernize your hotel operations?</h2>
            <p>Join properties using Veurion for reservations, night audit, channel sync, payments, and guest-facing integrations.</p>
            <a class="mc-btn mc-btn-lg mc-btn-solid mc-btn-light" href="<?php echo base_url('auth/register'); ?>">
                Start your <?php echo (int) $default_trial_days; ?>-day trial
            </a>
        </div>
    </section>
</main>

<script>window.mcMaxRooms = <?php echo (int) max(isset($max_slider) ? $max_slider : 150, 50); ?>;</script>
<?php $this->load->view('includes/marketing_footer', marketing_layout_view_vars(get_defined_vars())); ?>
