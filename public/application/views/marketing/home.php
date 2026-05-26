<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Veurion — Hotel property management software</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <?php foreach ($css_files as $css) { ?>
        <link rel="stylesheet" href="<?php echo $css; ?>">
    <?php } ?>
</head>
<body class="mc-marketing">
<a class="mc-skip" href="#main">Skip to content</a>

<header class="mc-nav" id="mc-nav">
    <div class="mc-nav-inner">
        <a class="mc-logo" href="<?php echo base_url(); ?>">
            <span class="mc-logo-mark" aria-hidden="true"></span>
            Veurion
        </a>
        <button type="button" class="mc-nav-toggle" id="mc-nav-toggle" aria-label="Open menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <nav class="mc-nav-links" id="mc-nav-links">
            <a href="#features">Features</a>
            <a href="#benefits">Benefits</a>
            <a href="#pricing">Pricing</a>
            <a href="<?php echo base_url('auth/login'); ?>" class="mc-nav-ghost">Sign in</a>
            <a href="<?php echo base_url('auth/register'); ?>" class="mc-btn mc-btn-sm mc-btn-solid">Start free trial</a>
        </nav>
    </div>
</header>

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
                    <li>Full feature access</li>
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
                <h2>Everything your hotel needs to run smoothly</h2>
                <p class="mc-section-sub">From the front desk to distribution and revenue — one connected system instead of scattered spreadsheets.</p>
            </header>
            <div class="mc-features">
                <article class="mc-feature-card mc-feature-large">
                    <div class="mc-feature-icon" aria-hidden="true">&#128197;</div>
                    <h3>Front desk calendar</h3>
                    <p>Manage arrivals, departures, and room assignments from one intuitive calendar built for busy front desks.</p>
                </article>
                <article class="mc-feature-card">
                    <div class="mc-feature-icon" aria-hidden="true">&#128225;</div>
                    <h3>Channel manager</h3>
                    <p>Sync availability and rates with OTAs. Fewer overbookings, less manual updating.</p>
                </article>
                <article class="mc-feature-card">
                    <div class="mc-feature-icon" aria-hidden="true">&#127760;</div>
                    <h3>Booking engine</h3>
                    <p>Accept direct reservations on your site and keep more revenue in-house.</p>
                </article>
                <article class="mc-feature-card">
                    <div class="mc-feature-icon" aria-hidden="true">&#128179;</div>
                    <h3>Payments &amp; invoicing</h3>
                    <p>Deposits, card tokenization, and guest balances with integrated gateways.</p>
                </article>
                <article class="mc-feature-card">
                    <div class="mc-feature-icon" aria-hidden="true">&#128200;</div>
                    <h3>Revenue management</h3>
                    <p>Forecast demand and optimize rates with built-in revenue tools.</p>
                </article>
                <article class="mc-feature-card">
                    <div class="mc-feature-icon" aria-hidden="true">&#128202;</div>
                    <h3>Reporting</h3>
                    <p>Occupancy, revenue, and ops reports — run your property with confidence.</p>
                </article>
            </div>
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
                        <p>Automate repetitive front-desk and distribution tasks.</p>
                    </div>
                </li>
                <li>
                    <span class="mc-benefit-num">02</span>
                    <div>
                        <strong>Fewer costly mistakes</strong>
                        <p>One source of truth for reservations across every channel.</p>
                    </div>
                </li>
                <li>
                    <span class="mc-benefit-num">03</span>
                    <div>
                        <strong>More direct revenue</strong>
                        <p>Own the guest relationship and reduce OTA commission pressure.</p>
                    </div>
                </li>
                <li>
                    <span class="mc-benefit-num">04</span>
                    <div>
                        <strong>Scale without chaos</strong>
                        <p>From boutique inns to multi-property portfolios.</p>
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
                            <span class="mc-tier-currency"><?php echo htmlspecialchars($tier['currency'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php echo number_format((float) $tier['monthly_price'], 0); ?>
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
            <p>Join properties using Veurion to simplify reservations, payments, and distribution.</p>
            <a class="mc-btn mc-btn-lg mc-btn-solid mc-btn-light" href="<?php echo base_url('auth/register'); ?>">
                Start your <?php echo (int) $default_trial_days; ?>-day trial
            </a>
        </div>
    </section>
</main>

<footer class="mc-footer">
    <div class="mc-container mc-footer-inner">
        <div class="mc-footer-brand">
            <span class="mc-logo-mark" aria-hidden="true"></span>
            <span>Veurion</span>
        </div>
        <nav class="mc-footer-links">
            <a href="<?php echo base_url('auth/show_terms_of_service'); ?>">Terms</a>
            <a href="<?php echo base_url('auth/show_privacy_policy'); ?>">Privacy</a>
            <a href="<?php echo base_url('auth/login'); ?>">Hotel login</a>
            <a href="<?php echo base_url('admin'); ?>">Platform admin</a>
        </nav>
        <p class="mc-footer-copy">&copy; <?php echo date('Y'); ?> Veurion. All rights reserved.</p>
    </div>
</footer>

<?php foreach ($js_files as $js) { ?>
    <script src="<?php echo $js; ?>"></script>
<?php } ?>
<script>window.mcMaxRooms = <?php echo (int) max(isset($max_slider) ? $max_slider : 150, 50); ?>;</script>
</body>
</html>
