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

function auth_trial_success_brand_markup($brand_name, $has_logo, $logo_url) {
    if ($has_logo) {
        return '<img src="' . htmlspecialchars($logo_url, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($brand_name, ENT_QUOTES, 'UTF-8') . '">';
    }
    return '<span class="auth-login-hero-mark" aria-hidden="true"></span><span class="veurion-login-brand">' . htmlspecialchars($brand_name, ENT_QUOTES, 'UTF-8') . '</span>';
}
?>

<div class="auth-login">
    <aside class="auth-login-hero">
        <a class="auth-login-hero-brand" href="<?php echo base_url(); ?>">
            <?php echo auth_trial_success_brand_markup($brand_name, $has_logo, $logo_url); ?>
        </a>

        <div class="auth-login-hero-copy">
            <p class="auth-login-hero-eyebrow">Thank you</p>
            <h1>We received your request</h1>
            <p>Our team will review your details and follow up at <strong><?php echo htmlspecialchars($submitted_email, ENT_QUOTES, 'UTF-8'); ?></strong> shortly.</p>
        </div>

        <p class="auth-login-hero-footer">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($brand_name, ENT_QUOTES, 'UTF-8'); ?></p>
    </aside>

    <main class="auth-login-main">
        <div class="auth-login-card">
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
                <h2>You are all set</h2>
                <p>Check your inbox for updates from <?php echo htmlspecialchars($brand_name, ENT_QUOTES, 'UTF-8'); ?>.</p>
            </header>

            <p class="auth-trial-success-note">
                In the meantime, you can return to the homepage or sign in if your account is already active.
            </p>

            <a href="<?php echo base_url(); ?>" class="auth-login-submit auth-login-submit-link">Back to homepage</a>

            <div class="auth-login-links">
                <?php echo anchor('/auth/login', 'Sign in to your account'); ?>
            </div>
        </div>
    </main>
</div>
