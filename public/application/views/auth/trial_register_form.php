<?php
$brand_name = ($whitelabel_detail && isset($whitelabel_detail['name']))
    ? veurion_display_brand_name($whitelabel_detail['name'])
    : ($this->config->item('branding_name') ?: 'Veurion');

$has_logo = isset($whitelabel_detail) && $whitelabel_detail
    && !veurion_use_text_login_logo($whitelabel_detail)
    && !empty($whitelabel_detail['logo']);

$logo_url = '';
if ($has_logo) {
    $logo_url = $this->image_url . $whitelabel_detail['logo'];
    if (strpos($whitelabel_detail['logo'], '.') !== false) {
        $logo_url = base_url() . 'images/' . $whitelabel_detail['logo'];
    }
}

$trial_days = isset($default_trial_days) ? (int) $default_trial_days : 14;

$error_messages = array();
if (!empty($errors) && is_array($errors)) {
    foreach ($errors as $msg) {
        if ($msg) {
            $error_messages[] = $msg;
        }
    }
}

function auth_trial_brand_markup($brand_name, $has_logo, $logo_url) {
    if ($has_logo) {
        return '<img src="' . htmlspecialchars($logo_url, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($brand_name, ENT_QUOTES, 'UTF-8') . '">';
    }
    return '<span class="auth-login-hero-mark" aria-hidden="true"></span><span class="veurion-login-brand">' . htmlspecialchars($brand_name, ENT_QUOTES, 'UTF-8') . '</span>';
}
?>

<div class="auth-login">
    <aside class="auth-login-hero">
        <a class="auth-login-hero-brand" href="<?php echo base_url(); ?>">
            <?php echo auth_trial_brand_markup($brand_name, $has_logo, $logo_url); ?>
        </a>

        <div class="auth-login-hero-copy">
            <p class="auth-login-hero-eyebrow">Free trial</p>
            <h1>Start managing your property in minutes</h1>
            <p>Tell us about your hotel and we will set up your <?php echo (int) $trial_days; ?>-day trial workspace.</p>
            <ul class="auth-login-hero-features">
                <li>No credit card required</li>
                <li>Full feature access during trial</li>
                <li>Our team will reach out to help you onboard</li>
            </ul>
        </div>

        <div class="auth-login-visual" aria-hidden="true">
            <div class="auth-login-visual-card">
                <div class="auth-login-visual-bar"><span></span><span></span><span></span></div>
                <div class="auth-login-visual-grid">
                    <div></div><div class="highlight"></div><div></div>
                </div>
            </div>
        </div>

        <p class="auth-login-hero-footer">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($brand_name, ENT_QUOTES, 'UTF-8'); ?></p>
    </aside>

    <main class="auth-login-main">
        <div class="auth-login-card auth-login-card-wide">
            <div class="auth-login-mobile-brand">
                <a href="<?php echo base_url(); ?>">
                    <?php
                    if ($has_logo) {
                        echo '<img src="' . htmlspecialchars($logo_url, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($brand_name, ENT_QUOTES, 'UTF-8') . '">';
                    } else {
                        echo '<span class="veurion-login-brand">' . htmlspecialchars($brand_name, ENT_QUOTES, 'UTF-8') . '</span>';
                    }
                    ?>
                </a>
            </div>

            <header class="auth-login-card-header">
                <h2>Start your free trial</h2>
                <p>Share your property details and we will get you started.</p>
            </header>

            <?php if (!empty($error_messages)) { ?>
                <div class="auth-login-errors" role="alert">
                    <?php echo implode('<br>', array_map('htmlspecialchars', $error_messages)); ?>
                </div>
            <?php } ?>

            <form action="<?php echo base_url('auth/register'); ?>" method="post" accept-charset="utf-8" novalidate>
                <div class="auth-login-field">
                    <label for="property_name">Property name</label>
                    <input
                        type="text"
                        name="property_name"
                        id="property_name"
                        placeholder="e.g. Sunset Boutique Hotel"
                        maxlength="255"
                        value="<?php echo htmlspecialchars(set_value('property_name'), ENT_QUOTES, 'UTF-8'); ?>"
                        required
                        autocomplete="organization"
                    >
                </div>

                <div class="auth-login-field">
                    <label for="number_of_rooms">Number of rooms</label>
                    <input
                        type="number"
                        name="number_of_rooms"
                        id="number_of_rooms"
                        placeholder="e.g. 24"
                        min="1"
                        max="9999"
                        value="<?php echo htmlspecialchars(set_value('number_of_rooms'), ENT_QUOTES, 'UTF-8'); ?>"
                        required
                        inputmode="numeric"
                    >
                </div>

                <div class="auth-login-field-row">
                    <div class="auth-login-field">
                        <label for="first_name">First name</label>
                        <input
                            type="text"
                            name="first_name"
                            id="first_name"
                            placeholder="Jane"
                            maxlength="100"
                            value="<?php echo htmlspecialchars(set_value('first_name'), ENT_QUOTES, 'UTF-8'); ?>"
                            required
                            autocomplete="given-name"
                        >
                    </div>
                    <div class="auth-login-field">
                        <label for="last_name">Last name</label>
                        <input
                            type="text"
                            name="last_name"
                            id="last_name"
                            placeholder="Doe"
                            maxlength="100"
                            value="<?php echo htmlspecialchars(set_value('last_name'), ENT_QUOTES, 'UTF-8'); ?>"
                            required
                            autocomplete="family-name"
                        >
                    </div>
                </div>

                <div class="auth-login-field">
                    <label for="email">Work email</label>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        placeholder="you@hotel.com"
                        maxlength="255"
                        value="<?php echo htmlspecialchars(set_value('email'), ENT_QUOTES, 'UTF-8'); ?>"
                        required
                        autocomplete="email"
                    >
                </div>

                <div class="auth-login-field">
                    <label for="phone">Phone number</label>
                    <input
                        type="tel"
                        name="phone"
                        id="phone"
                        placeholder="+234 801 234 5678"
                        maxlength="50"
                        value="<?php echo htmlspecialchars(set_value('phone'), ENT_QUOTES, 'UTF-8'); ?>"
                        required
                        autocomplete="tel"
                    >
                </div>

                <button type="submit" name="trial_register_submit" value="1" class="auth-login-submit">
                    Request free trial
                </button>
            </form>

            <div class="auth-login-links">
                <?php echo anchor('/auth/login', 'Already have an account? Sign in'); ?>
            </div>

            <p class="auth-login-legal">
                By submitting this form, you agree to our
                <a href="<?php echo base_url(); ?>auth/show_terms_of_service" target="_blank" rel="noopener">Terms of Use</a>
                and
                <a href="<?php echo base_url(); ?>auth/show_privacy_policy" target="_blank" rel="noopener">Privacy Policy</a>.
            </p>
        </div>
    </main>
</div>
