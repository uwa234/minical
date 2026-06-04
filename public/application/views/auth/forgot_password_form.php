<?php
$whitelabel_detail = isset($whitelabel_detail) ? $whitelabel_detail : veurion_normalize_whitelabel_partner($this->session->userdata('white_label_information'));

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
if (!$has_logo) {
    $platform_logo_url = platform_website_logo_url();
    if ($platform_logo_url !== '') {
        $has_logo = true;
        $logo_url = $platform_logo_url;
    }
}

$use_username = $this->config->item('use_username', 'tank_auth');
$login_label = $use_username ? 'Email or login' : 'Email address';

$error_messages = array();
if (form_error('login')) {
    $error_messages[] = strip_tags(form_error('login'));
}
if (!empty($errors['login'])) {
    $error_messages[] = $errors['login'];
}

function auth_forgot_brand_markup($brand_name, $has_logo, $logo_url) {
    if ($has_logo) {
        return '<img src="' . htmlspecialchars($logo_url, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($brand_name, ENT_QUOTES, 'UTF-8') . '">';
    }
    return '<span class="auth-login-hero-mark" aria-hidden="true"></span><span class="veurion-login-brand">' . htmlspecialchars($brand_name, ENT_QUOTES, 'UTF-8') . '</span>';
}
?>

<div class="auth-login">
    <aside class="auth-login-hero">
        <a class="auth-login-hero-brand" href="<?php echo base_url(); ?>">
            <?php echo auth_forgot_brand_markup($brand_name, $has_logo, $logo_url); ?>
        </a>

        <div class="auth-login-hero-copy">
            <p class="auth-login-hero-eyebrow">Account security</p>
            <h1>We'll help you get back in</h1>
            <p>Enter the email linked to your <?php echo htmlspecialchars($brand_name, ENT_QUOTES, 'UTF-8'); ?> account and we'll send you a secure link to choose a new password.</p>
            <ul class="auth-login-hero-features">
                <li>Reset link delivered to your inbox</li>
                <li>Link expires after a limited time</li>
                <li>Your existing data stays safe</li>
            </ul>
        </div>

        <div class="auth-login-visual" aria-hidden="true">
            <div class="auth-login-visual-card">
                <div class="auth-login-visual-bar"><span></span><span></span><span></span></div>
                <div class="auth-login-visual-grid">
                    <div class="highlight"></div><div></div><div></div>
                </div>
            </div>
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
                <h2>Reset your password</h2>
                <p>We'll email you instructions to set a new password</p>
            </header>

            <?php if (!empty($error_messages)) { ?>
                <div class="auth-login-errors" role="alert">
                    <?php echo implode('<br>', array_map('htmlspecialchars', $error_messages)); ?>
                </div>
            <?php } ?>

            <form action="<?php echo base_url('auth/forgot_password'); ?>" method="post" accept-charset="utf-8" novalidate>
                <div class="auth-login-field">
                    <label for="login"><?php echo htmlspecialchars($login_label, ENT_QUOTES, 'UTF-8'); ?></label>
                    <input
                        type="email"
                        name="login"
                        id="login"
                        placeholder="you@hotel.com"
                        maxlength="80"
                        value="<?php echo htmlspecialchars(set_value('login'), ENT_QUOTES, 'UTF-8'); ?>"
                        required
                        autocomplete="email"
                    >
                </div>

                <button type="submit" name="submit" value="1" class="auth-login-submit">
                    Send reset link
                </button>
            </form>

            <div class="auth-login-links">
                <?php echo anchor('/auth/login/', '&larr; Back to sign in'); ?>
            </div>

            <p class="auth-login-legal">
                Need help?
                <a href="mailto:support@veurion.com">Contact support</a>
            </p>
        </div>
    </main>
</div>
