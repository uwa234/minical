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

$email = '';
$password = '';
if (auto_fill_credentials() && current_url() == 'https://demo.veurion.com/auth/login') {
    $email = 'demo@veurion.com';
    $password = '12345';
}

$error_messages = array();
if (!empty($errors['incorrect_email'])) {
    $error_messages[] = $errors['incorrect_email'];
}
if (!empty($errors['password'])) {
    $error_messages[] = $errors['password'];
}

function auth_login_brand_markup($brand_name, $has_logo, $logo_url) {
    if ($has_logo) {
        return '<img src="' . htmlspecialchars($logo_url, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($brand_name, ENT_QUOTES, 'UTF-8') . '">';
    }
    return '<span class="auth-login-hero-mark" aria-hidden="true"></span><span class="veurion-login-brand">' . htmlspecialchars($brand_name, ENT_QUOTES, 'UTF-8') . '</span>';
}
?>

<div class="auth-login">
    <aside class="auth-login-hero">
        <a class="auth-login-hero-brand" href="<?php echo base_url(); ?>">
            <?php echo auth_login_brand_markup($brand_name, $has_logo, $logo_url); ?>
        </a>

        <div class="auth-login-hero-copy">
            <p class="auth-login-hero-eyebrow">Property management</p>
            <h1>Run your hotel from one beautiful workspace</h1>
            <p>Reservations, housekeeping, reporting, and guest communication — everything your team needs, in one place.</p>
            <ul class="auth-login-hero-features">
                <li>Real-time availability calendar</li>
                <li>Direct bookings &amp; channel sync</li>
                <li>Night audit &amp; financial reports</li>
            </ul>
        </div>

        <div class="auth-login-visual" aria-hidden="true">
            <div class="auth-login-visual-card">
                <div class="auth-login-visual-bar"><span></span><span></span><span></span></div>
                <div class="auth-login-visual-grid">
                    <div></div><div></div><div class="highlight"></div>
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
                <h2>Welcome back</h2>
                <p>Sign in to your <?php echo htmlspecialchars($brand_name, ENT_QUOTES, 'UTF-8'); ?> account</p>
            </header>

            <?php if (!empty($error_messages)) { ?>
                <div class="auth-login-errors" role="alert">
                    <?php echo implode('<br>', array_map('htmlspecialchars', $error_messages)); ?>
                </div>
            <?php } ?>

            <form action="<?php echo base_url(); ?>auth/login" method="post" accept-charset="utf-8" novalidate>
                <div class="auth-login-field">
                    <label for="Email">Email address</label>
                    <input
                        type="email"
                        name="login"
                        id="Email"
                        placeholder="you@hotel.com"
                        maxlength="80"
                        value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                        required
                        autocomplete="email"
                    >
                </div>

                <div class="auth-login-field">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        value="<?php echo htmlspecialchars($password, ENT_QUOTES, 'UTF-8'); ?>"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <button type="submit" name="submit" value="1" class="auth-login-submit" id="log-in-button">
                    Sign in
                </button>
            </form>

            <div class="auth-login-links">
                <?php echo anchor('/auth/forgot_password/', 'Forgot your password?'); ?>
            </div>

            <?php
            $show_signup = isset($whitelabel_detail['domain'])
                && $whitelabel_detail['domain'] != 'app.thelobbyboy.com'
                && show_registration_link();
            if ($show_signup) {
                echo anchor('/auth/register', "Create an account — start your free trial", 'class="auth-login-signup"');
            }
            ?>

            <p class="auth-login-legal">
                By signing in, you agree to our
                <?php if (!empty($whitelabel_detail['terms_of_service'])) { ?>
                    <a href="<?php echo htmlspecialchars($whitelabel_detail['terms_of_service'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Terms of Use</a>
                <?php } else { ?>
                    <a href="<?php echo base_url(); ?>auth/show_terms_of_service" target="_blank" rel="noopener">Terms of Use</a>
                <?php } ?>
                and
                <?php if (!empty($whitelabel_detail['privacy_policy'])) { ?>
                    <a href="<?php echo htmlspecialchars($whitelabel_detail['privacy_policy'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Privacy Policy</a>
                <?php } else { ?>
                    <a href="<?php echo base_url(); ?>auth/show_privacy_policy" target="_blank" rel="noopener">Privacy Policy</a>
                <?php } ?>.
            </p>
        </div>
    </main>
</div>

<!-- Google Code for Arrived sign-up page Conversion Page -->
<script type="text/javascript">
/* <![CDATA[ */
var google_conversion_id = 980305060;
var google_conversion_language = "en";
var google_conversion_format = "3";
var google_conversion_color = "ffffff";
var google_conversion_label = "1HKsCPzqpQoQpIm50wM";
var google_remarketing_only = false;
/* ]]> */
</script>
<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js"></script>
<noscript>
<div style="display:inline;">
<img height="1" width="1" style="border-style:none;" alt="" src="//www.googleadservices.com/pagead/conversion/980305060/?label=1HKsCPzqpQoQpIm50wM&amp;guid=ON&amp;script=0"/>
</div>
</noscript>
