<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Platform super admin (Veurion SaaS operator), not hotel staff or whitelabel partner admin.
 */
function veurion_support_emails()
{
    $emails = array(SUPER_ADMIN);
    if (defined('PLATFORM_SUPER_ADMIN')) {
        $emails[] = PLATFORM_SUPER_ADMIN;
    }
    if (defined('LEGACY_SUPER_ADMIN')) {
        $emails[] = LEGACY_SUPER_ADMIN;
    }
    return array_map('strtolower', array_unique($emails));
}

function is_veurion_support_email($email)
{
    if ($email === null || $email === '') {
        return false;
    }
    return in_array(strtolower(trim($email)), veurion_support_emails(), true);
}

function is_platform_admin($user_id, $email = null)
{
    $CI =& get_instance();
    if (!isset($CI->Platform_admin_model)) {
        $CI->load->model('Platform_admin_model');
    }

    if ($CI->Platform_admin_model->table_exists()) {
        if ($email !== null && $email !== '') {
            if ($CI->Platform_admin_model->is_active_platform_admin_email($email)) {
                return true;
            }
        }
        if ((int) $user_id > 0 && $CI->Platform_admin_model->is_active_platform_admin_user($user_id, $email)) {
            return true;
        }
    }

    if ($email !== null && $email !== '') {
        return is_veurion_support_email($email);
    }
    if (!isset($CI->User_model)) {
        $CI->load->model('User_model');
    }
    $user = $CI->User_model->get_user_by_id($user_id);
    return $user && isset($user['email']) && is_veurion_support_email($user['email']);
}

function platform_admin_encrypt_secret($plaintext)
{
    $plaintext = (string) $plaintext;
    if ($plaintext === '') {
        return '';
    }

    $CI =& get_instance();
    $key = $CI->config->item('encryption_key');
    if (!$key) {
        return base64_encode($plaintext);
    }

    $iv = random_bytes(16);
    $cipher = openssl_encrypt($plaintext, 'AES-256-CBC', hash('sha256', $key, true), OPENSSL_RAW_DATA, $iv);
    if ($cipher === false) {
        return base64_encode($plaintext);
    }

    return base64_encode($iv . $cipher);
}

function platform_admin_decrypt_secret($ciphertext)
{
    $ciphertext = (string) $ciphertext;
    if ($ciphertext === '') {
        return '';
    }

    $raw = base64_decode($ciphertext, true);
    if ($raw === false || strlen($raw) < 17) {
        return base64_decode($ciphertext) ?: '';
    }

    $CI =& get_instance();
    $key = $CI->config->item('encryption_key');
    if (!$key) {
        return $raw;
    }

    $iv = substr($raw, 0, 16);
    $cipher = substr($raw, 16);
    $plain = openssl_decrypt($cipher, 'AES-256-CBC', hash('sha256', $key, true), OPENSSL_RAW_DATA, $iv);

    return $plain !== false ? $plain : '';
}

function platform_admin_request_ip()
{
    $CI =& get_instance();
    return $CI->input->ip_address();
}

function platform_audit_log($action, $data = array())
{
    $CI =& get_instance();
    if (!isset($CI->Platform_audit_log_model)) {
        $CI->load->model('Platform_audit_log_model');
    }
    if (!$CI->Platform_audit_log_model->table_exists()) {
        return false;
    }

    $actor_user_id = isset($data['actor_user_id']) ? (int) $data['actor_user_id'] : 0;
    $actor_email = isset($data['actor_email']) ? (string) $data['actor_email'] : '';
    if ($actor_user_id < 1 && $CI->session) {
        $actor_user_id = (int) $CI->session->userdata('user_id');
    }
    if ($actor_email === '' && $CI->session) {
        $actor_email = (string) $CI->session->userdata('email');
    }

    return $CI->Platform_audit_log_model->add_entry(array(
        'actor_user_id' => $actor_user_id > 0 ? $actor_user_id : null,
        'actor_email' => $actor_email,
        'action' => (string) $action,
        'target_type' => isset($data['target_type']) ? (string) $data['target_type'] : null,
        'target_id' => isset($data['target_id']) ? (int) $data['target_id'] : null,
        'target_label' => isset($data['target_label']) ? (string) $data['target_label'] : null,
        'details' => isset($data['details']) ? (string) $data['details'] : null,
        'ip_address' => platform_admin_request_ip(),
    ));
}

function platform_audit_action_label($action)
{
    static $labels = null;
    if ($labels === null) {
        $labels = array(
            'login' => 'Login',
            'login.2fa' => '2FA verification',
            'admin.add' => 'Admin added',
            'admin.revoke' => 'Admin revoked',
            '2fa.enable' => '2FA enabled',
            '2fa.disable' => '2FA disabled',
            'property.soft_delete' => 'Property soft-deleted',
            'property.restore' => 'Property restored',
            'property.update' => 'Property updated',
            'subscription.update' => 'Subscription updated',
            'settings.update' => 'Settings updated',
            'backup.download' => 'Database backup downloaded',
            'impersonation.start' => 'Impersonation started',
            'tenant.action' => 'Tenant action',
        );
        if (function_exists('get_instance')) {
            $CI =& get_instance();
            if (isset($CI->Platform_audit_log_model)) {
                $labels = $CI->Platform_audit_log_model->action_labels();
            } elseif (class_exists('CI_Controller', false)) {
                $CI->load->model('Platform_audit_log_model');
                $labels = $CI->Platform_audit_log_model->action_labels();
            }
        }
    }

    return isset($labels[$action]) ? $labels[$action] : ucfirst(str_replace(array('.', '_'), ' ', (string) $action));
}

function is_hosted_prod_service()
{
    $flag = getenv('IS_HOSTED_PROD_SERVICE');
    return $flag === '1' || $flag === 'true' || $flag === true;
}

/**
 * Days after paid plan expiration_date before account lockout (grace to renew).
 */
function saas_paid_plan_grace_days()
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $CI =& get_instance();
    if (!isset($CI->Platform_settings_model)) {
        $CI->load->model('Platform_settings_model');
    }
    if ($CI->db->table_exists('platform_settings')) {
        $cached = $CI->Platform_settings_model->get_paid_plan_grace_days();
        return $cached;
    }

    $cached = 7;
    return $cached;
}

/**
 * Whether the subscription state triggers post-trial account lockout.
 */
function is_trial_lockout_subscription_state($subscription_state)
{
    return trim((string) $subscription_state) === 'trial_ended';
}

/**
 * Why billing lockout applies: trial_ended, or paid plan past grace (plan_past_grace).
 *
 * @param array|null $company Row from Company_model::get_company()
 * @return string|null
 */
function company_billing_lockout_reason($company)
{
    if (!is_hosted_prod_service() || !$company || !isset($company['subscription_state'])) {
        return null;
    }

    if (is_trial_lockout_subscription_state($company['subscription_state'])) {
        return 'trial_ended';
    }

    if (trim((string) $company['subscription_state']) === 'unpaid') {
        return 'unpaid';
    }

    $plan_ctx = build_paid_plan_expiry_context($company);
    if ($plan_ctx && !empty($plan_ctx['is_past_grace'])) {
        return 'plan_past_grace';
    }

    return null;
}

/**
 * True when the signed-in company must be limited to billing pages only.
 */
function company_requires_trial_lockout($company, $user_id = null, $user_email = null)
{
    if ($user_id !== null && is_platform_admin($user_id, $user_email)) {
        return false;
    }

    return company_billing_lockout_reason($company) !== null;
}

/**
 * User-facing messages for billing lockout (trial ended or paid plan past grace).
 *
 * @param string|null $reason From company_billing_lockout_reason()
 * @param bool $billing_denied User cannot manage billing
 * @return array{short: string, owner: string, employee: string}
 */
function billing_lockout_messages($reason, $billing_denied = false)
{
    if ($billing_denied) {
        return array(
            'short' => 'Your subscription has expired. Please ask your account owner to make a payment to restore access.',
            'owner' => 'Your subscription has expired. You need to make a payment before you can access your account.',
            'employee' => 'Your subscription has expired. Please ask your account owner to make a payment to restore access.',
        );
    }

    return array(
        'short' => 'Your subscription has expired. Renew to restore access.',
        'owner' => 'Your subscription has expired. You need to make a payment before you can access your account.',
        'employee' => 'Your subscription has expired. Please ask your account owner to make a payment to restore access.',
    );
}

/**
 * Platform admin WhatsApp number for subscription support.
 */
function platform_admin_whatsapp_number()
{
    return '2348158037123';
}

/**
 * WhatsApp chat URL for platform admin subscription support.
 *
 * @param string $message Optional pre-filled message
 */
function platform_admin_whatsapp_url($message = '')
{
    $url = 'https://wa.me/' . platform_admin_whatsapp_number();
    $message = trim((string) $message);
    if ($message !== '') {
        $url .= '?text=' . rawurlencode($message);
    }

    return $url;
}

/**
 * Whether Paystack SaaS billing is configured for subscription checkout.
 */
function saas_paystack_configured()
{
    return trim((string) minical_env('PAYSTACK_SAAS_SECRET_KEY', '')) !== ''
        || trim((string) minical_env('PAYSTACK_SECRET_KEY', '')) !== '';
}

/**
 * Popup payload when a property must pay before using the app.
 *
 * @param array|null $company Row from Company_model::get_company()
 * @param int|null $user_id
 * @param string|null $user_role
 * @param array|null $permissions
 * @return array|null
 */
function build_subscription_lockout_popup_data($company, $user_id = null, $user_role = null, $permissions = null)
{
    if (!company_requires_trial_lockout($company, $user_id)) {
        return null;
    }

    $reason = company_billing_lockout_reason($company);
    $can_pay = user_can_manage_billing(
        (int) $user_id,
        isset($company['company_id']) ? (int) $company['company_id'] : 0,
        $user_role,
        $permissions
    );
    $lockout_msgs = billing_lockout_messages($reason, !$can_pay);

    $room_count = isset($company['number_of_rooms_actual']) ? (int) $company['number_of_rooms_actual'] : 1;
    if ($room_count < 1) {
        $room_count = 1;
    }

    $tier = resolve_company_saas_tier($company, $room_count);
    $tier_id = $tier && isset($tier['id']) ? (int) $tier['id'] : 0;
    $tier_name = $tier && !empty($tier['name']) ? (string) $tier['name'] : '';

    return array(
        'is_blocking' => 1,
        'message' => $can_pay ? $lockout_msgs['owner'] : $lockout_msgs['employee'],
        'show_link' => 0,
        'can_pay' => $can_pay ? 1 : 0,
        'tier_id' => $tier_id,
        'tier_name' => $tier_name,
        'paystack_configured' => saas_paystack_configured() ? 1 : 0,
        'whatsapp_url' => platform_admin_whatsapp_url('Hi, I need help renewing my subscription.'),
        'state' => isset($company['subscription_state']) ? (string) $company['subscription_state'] : $reason,
    );
}

/**
 * Whether the signed-in user may open subscription / billing pages for this property.
 */
function user_can_manage_billing($user_id, $company_id, $user_role = null, $permissions = null)
{
    $user_id = (int) $user_id;
    $company_id = (int) $company_id;
    if ($user_id < 1 || $company_id < 1) {
        return false;
    }

    if (is_platform_admin($user_id)) {
        return true;
    }

    if ($user_role === null || $permissions === null) {
        $CI =& get_instance();
        if ($user_role === null) {
            if (!isset($CI->User_model)) {
                $CI->load->model('User_model');
            }
            $user_role = $CI->User_model->get_user_role($user_id, $company_id);
        }
        if ($permissions === null) {
            if (!isset($CI->Employee_log_model)) {
                $CI->load->model('Employee_log_model');
            }
            $permissions = $CI->Employee_log_model->get_user_permission($company_id, $user_id);
        }
    }

    if (in_array($user_role, array('is_owner', 'is_admin'), true)) {
        return true;
    }

    if ($user_role === 'is_housekeeping' || (is_array($permissions) && in_array('is_housekeeping', $permissions, true))) {
        return false;
    }

    return is_array($permissions) && in_array('access_to_billing', $permissions, true);
}

/**
 * Routes reachable while a company is in post-trial lockout.
 */
function is_trial_lockout_allowed_route($controller_name, $function_name)
{
    $controller = strtolower(trim((string) $controller_name));
    $function = strtolower(trim((string) $function_name));

    $allowed = array(
        'dashboard' => array('index', 'analytics'),
        'account_settings' => array('paystack_checkout', 'paystack_callback', 'paystack_billing_health', 'subscription_receipt'),
        'auth' => array('logout', 'get_subscription_state_extended', 'check_for_employee_auto_logout', 'forbidden'),
        'menu' => array('select_hotel'),
        'properties' => array('my_properties'),
    );

    if (!isset($allowed[$controller])) {
        return false;
    }

    return in_array($function, $allowed[$controller], true);
}

/**
 * Default landing page after login when trial lockout applies.
 */
function trial_lockout_subscription_url()
{
    return base_url('dashboard/');
}

/**
 * Legacy SaaS brand names stored in DB before the Veurion rebrand.
 */
function veurion_legacy_brand_names()
{
    return array('minical', 'minical inc', 'minical inc.');
}

function veurion_is_legacy_saas_brand($name)
{
    if ($name === null || trim((string) $name) === '') {
        return false;
    }
    return in_array(strtolower(trim((string) $name)), veurion_legacy_brand_names(), true);
}

/**
 * User-facing product name (maps legacy Minical labels to Veurion).
 */
function veurion_display_brand_name($name, $fallback = 'Veurion')
{
    if ($name === null || trim((string) $name) === '') {
        return $fallback;
    }
    if (veurion_is_legacy_saas_brand($name)) {
        return 'Veurion';
    }
    return $name;
}

/**
 * Normalize whitelabel partner row for session/views after rebrand.
 */
function veurion_normalize_whitelabel_partner($partner)
{
    if (!$partner || !is_array($partner)) {
        return $partner;
    }
    if (isset($partner['name'])) {
        $partner['name'] = veurion_display_brand_name($partner['name']);
    }
    if (isset($partner['username']) && strtolower(trim($partner['username'])) === 'minical') {
        $partner['username'] = 'veurion';
    }
    return $partner;
}

/**
 * Use text mark instead of legacy Minical image assets on auth pages.
 */
function veurion_use_text_login_logo($whitelabel)
{
    if (!$whitelabel || !is_array($whitelabel)) {
        return true;
    }
    if (empty($whitelabel['logo'])) {
        return true;
    }
    return stripos((string) $whitelabel['logo'], 'minical') !== false;
}

/**
 * Directory for platform-wide logo and favicon uploads.
 */
function platform_branding_upload_dir()
{
    $dir = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'platform' . DIRECTORY_SEPARATOR;
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $index = $dir . 'index.html';
    if (!is_file($index)) {
        @file_put_contents($index, '');
    }
    return $dir;
}

function platform_branding_relative_path_is_valid($path)
{
    $path = str_replace('\\', '/', (string) $path);
    return $path !== '' && strpos($path, 'uploads/platform/') === 0 && strpos($path, '..') === false;
}

function platform_delete_branding_file($relative_path)
{
    if (!platform_branding_relative_path_is_valid($relative_path)) {
        return;
    }
    $full = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative_path);
    if (is_file($full)) {
        @unlink($full);
    }
}

function platform_website_logo_path()
{
    $CI =& get_instance();
    if (!isset($CI->Platform_settings_model)) {
        $CI->load->model('Platform_settings_model');
    }
    return (string) $CI->Platform_settings_model->get('website_logo', '');
}

function platform_website_favicon_path()
{
    $CI =& get_instance();
    if (!isset($CI->Platform_settings_model)) {
        $CI->load->model('Platform_settings_model');
    }
    return (string) $CI->Platform_settings_model->get('website_favicon', '');
}

function platform_website_logo_url()
{
    $path = platform_website_logo_path();
    if ($path === '' || !platform_branding_relative_path_is_valid($path)) {
        return '';
    }
    $full = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (!is_file($full)) {
        return '';
    }
    return base_url() . $path;
}

function platform_website_favicon_url()
{
    $path = platform_website_favicon_path();
    if ($path === '' || !platform_branding_relative_path_is_valid($path)) {
        return '';
    }
    $full = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (!is_file($full)) {
        return '';
    }
    return base_url() . $path;
}

function platform_branding_favicon_mime_type($url)
{
    if (stripos((string) $url, '.png') !== false) {
        return 'image/png';
    }
    if (stripos((string) $url, '.svg') !== false) {
        return 'image/svg+xml';
    }
    return 'image/x-icon';
}

function platform_branding_favicon_link_html()
{
    $url = platform_website_favicon_url();
    if ($url === '') {
        $url = base_url() . 'images/favicon.ico';
    }
    $type = platform_branding_favicon_mime_type($url);
    return '<link rel="shortcut icon" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
        . '" type="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '" />';
}

/**
 * Save platform website logo or favicon from $_FILES.
 *
 * @param string $asset logo|favicon
 * @return array{ok:bool,message:string}
 */
function platform_save_branding_upload($asset)
{
    $CI =& get_instance();
    if (!isset($CI->Platform_settings_model)) {
        $CI->load->model('Platform_settings_model');
    }

    $asset = ($asset === 'favicon') ? 'favicon' : 'logo';
    $input_name = ($asset === 'favicon') ? 'website_favicon' : 'website_logo';
    $setting_key = ($asset === 'favicon') ? 'website_favicon' : 'website_logo';
    $basename = ($asset === 'favicon') ? 'website_favicon' : 'website_logo';

    if (!isset($_FILES[$input_name]) || empty($_FILES[$input_name]['tmp_name']) || !is_uploaded_file($_FILES[$input_name]['tmp_name'])) {
        return array('ok' => false, 'message' => 'No file was uploaded.');
    }

    $file = $_FILES[$input_name];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return array('ok' => false, 'message' => 'Upload failed. Please try again.');
    }

    $max_bytes = ($asset === 'favicon') ? 512000 : 2097152;
    if ($file['size'] > $max_bytes) {
        $label = ($asset === 'favicon') ? '512 KB' : '2 MB';
        return array('ok' => false, 'message' => 'File is too large (max ' . $label . ').');
    }

    $parts = explode('.', $file['name']);
    $extension = strtolower((string) end($parts));
    $allowed = ($asset === 'favicon')
        ? array('ico', 'png')
        : array('png', 'jpg', 'jpeg', 'gif', 'webp', 'svg');
    if (!in_array($extension, $allowed, true)) {
        return array('ok' => false, 'message' => 'Invalid file type.');
    }

    if ($extension !== 'svg' && $extension !== 'ico') {
        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            return array('ok' => false, 'message' => 'Invalid image file.');
        }
    }

    $dir = platform_branding_upload_dir();
    $filename = $basename . '.' . $extension;
    $relative = 'uploads/platform/' . $filename;
    $full = $dir . $filename;

    $old = (string) $CI->Platform_settings_model->get($setting_key, '');
    if ($old !== '' && $old !== $relative) {
        platform_delete_branding_file($old);
    }
    foreach (glob($dir . $basename . '.*') ?: array() as $old_file) {
        if (is_file($old_file)) {
            @unlink($old_file);
        }
    }

    if (!move_uploaded_file($file['tmp_name'], $full)) {
        return array('ok' => false, 'message' => 'Could not save uploaded file.');
    }

    $CI->Platform_settings_model->set($setting_key, $relative);
    $label = ($asset === 'favicon') ? 'Favicon' : 'Website logo';
    return array('ok' => true, 'message' => $label . ' saved.');
}

/**
 * Remove platform website logo or favicon.
 *
 * @param string $asset logo|favicon
 * @return array{ok:bool,message:string}
 */
function platform_remove_branding_asset($asset)
{
    $CI =& get_instance();
    if (!isset($CI->Platform_settings_model)) {
        $CI->load->model('Platform_settings_model');
    }

    $asset = ($asset === 'favicon') ? 'favicon' : 'logo';
    $setting_key = ($asset === 'favicon') ? 'website_favicon' : 'website_logo';
    $basename = ($asset === 'favicon') ? 'website_favicon' : 'website_logo';

    $old = (string) $CI->Platform_settings_model->get($setting_key, '');
    if ($old !== '') {
        platform_delete_branding_file($old);
    }
    foreach (glob(platform_branding_upload_dir() . $basename . '.*') ?: array() as $old_file) {
        if (is_file($old_file)) {
            @unlink($old_file);
        }
    }
    $CI->Platform_settings_model->set($setting_key, '');
    $label = ($asset === 'favicon') ? 'Favicon' : 'Website logo';
    return array('ok' => true, 'message' => $label . ' removed.');
}

/**
 * Human-readable legacy subscription level (company_subscription.subscription_level).
 */
/**
 * Default SaaS billing currency (Paystack / Nigeria).
 */
function saas_default_currency()
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $CI =& get_instance();
    if (!isset($CI->Platform_settings_model)) {
        $CI->load->model('Platform_settings_model');
    }
    if ($CI->db->table_exists('platform_settings')) {
        $cached = $CI->Platform_settings_model->get_default_currency();
        return $cached;
    }

    $cached = 'NGN';
    return $cached;
}

/**
 * Currency symbol for SaaS pricing display.
 */
function saas_currency_symbol($currency_code = null)
{
    $code = strtoupper(trim((string) ($currency_code ?: saas_default_currency())));
    if ($code === 'NGN') {
        return '₦';
    }
    if ($code === 'USD') {
        return '$';
    }
    if ($code === 'EUR') {
        return '€';
    }
    if ($code === 'GBP') {
        return '£';
    }
    return $code . ' ';
}

/**
 * Format a SaaS price amount for display.
 */
function format_saas_price_amount($amount, $decimals = 0)
{
    return number_format((float) $amount, (int) $decimals);
}

/**
 * Canonical feature lists per plan (standalone — not cumulative).
 */
function saas_pricing_tier_features_by_name($tier_name)
{
    $features = array(
        'Starter' => array(
            'Front desk calendar',
            'Online booking engine',
            'Basic reporting',
            'Email support',
        ),
        'Growth' => array(
            'Channel manager connections',
            'Payment gateway integration',
            'Revenue management tools',
        ),
        'Professional' => array(
            'Multi-user permissions',
            'Advanced analytics',
            'Priority support',
        ),
        'Enterprise' => array(
            'Custom integrations',
            'Dedicated onboarding',
            'SLA support',
        ),
    );

    $name = trim((string) $tier_name);
    return isset($features[$name]) ? $features[$name] : null;
}

function subscription_level_display_name($subscription_level)
{
    $level = (string) $subscription_level;
    if ($level === (string) ELITE) {
        return 'Elite';
    }
    if ($level === (string) PREMIUM) {
        return 'Premium';
    }
    if ($level === (string) STARTER) {
        return 'Starter';
    }
    if ($level === (string) BASIC) {
        return 'Basic';
    }
    return 'Standard';
}

/**
 * Map a SaaS pricing tier row to legacy subscription_level constants used for gating.
 *
 * @param array $tier Row from saas_pricing_tier / Platform_settings_model::get_pricing_tier()
 * @return int STARTER, BASIC, PREMIUM, or ELITE
 */
function resolve_subscription_level_for_tier($tier)
{
    if (!$tier || !is_array($tier)) {
        return (int) BASIC;
    }

    $name = isset($tier['name']) ? trim((string) $tier['name']) : '';
    if (strcasecmp($name, 'Starter') === 0) {
        return (int) STARTER;
    }
    if (strcasecmp($name, 'Enterprise') === 0) {
        return (int) ELITE;
    }
    if (strcasecmp($name, 'Growth') === 0 || strcasecmp($name, 'Professional') === 0) {
        return (int) PREMIUM;
    }

    $raw = isset($tier['subscription_level']) ? (int) $tier['subscription_level'] : (int) BASIC;
    if ($raw === 2) {
        return (int) ELITE;
    }
    if ($raw === 1) {
        return (int) PREMIUM;
    }

    return (int) BASIC;
}

/**
 * Parse company_subscription.meta_data JSON into an array.
 *
 * @param array|string|null $company_or_meta
 * @return array<string, mixed>
 */
function parse_company_subscription_meta($company_or_meta)
{
    if (is_array($company_or_meta) && isset($company_or_meta['meta_data'])) {
        $company_or_meta = $company_or_meta['meta_data'];
    }

    if (!is_string($company_or_meta) || trim($company_or_meta) === '') {
        return array();
    }

    $decoded = json_decode($company_or_meta, true);
    return is_array($decoded) ? $decoded : array();
}

/**
 * Tier id that has been paid for / committed — ignores in-flight Paystack checkouts.
 *
 * @param array<string, mixed> $meta
 * @return int
 */
function saas_meta_committed_tier_id($meta)
{
    if (!is_array($meta)) {
        return 0;
    }

    $tier_id = isset($meta['tier_id']) ? (int) $meta['tier_id'] : 0;
    if ($tier_id < 1) {
        return 0;
    }

    $has_payment_proof = !empty($meta['last_reference'])
        || !empty($meta['last_paid_at'])
        || !empty($meta['tier_updated_at']);

    if ($has_payment_proof) {
        return $tier_id;
    }

    $pending_reference = isset($meta['pending_reference']) ? trim((string) $meta['pending_reference']) : '';
    if ($pending_reference === '') {
        return $tier_id;
    }

    // Unpaid checkout attempt wrote tier_id (legacy bug) — ignore until payment succeeds.
    return 0;
}

/**
 * Effective subscription level for a company (prefers assigned SaaS tier in meta_data).
 *
 * @param array $company Row from Company_model::get_company()
 * @return int
 */
function resolve_company_subscription_level($company)
{
    if (!$company || !is_array($company)) {
        return (int) BASIC;
    }

    $tier_id = saas_meta_committed_tier_id(parse_company_subscription_meta($company));
    if ($tier_id > 0) {
        $CI =& get_instance();
        if (!isset($CI->Platform_settings_model)) {
            $CI->load->model('Platform_settings_model');
        }
        $tier = $CI->Platform_settings_model->get_pricing_tier($tier_id);
        if ($tier) {
            return resolve_subscription_level_for_tier($tier);
        }
    }

    return isset($company['subscription_level']) ? (int) $company['subscription_level'] : (int) BASIC;
}

/**
 * Whether plan-based feature limits should apply for this session.
 *
 * @param array $company
 * @param int|null $subscription_level Resolved level (optional)
 * @return bool
 */
function company_plan_restrictions_apply($company, $subscription_level = null)
{
    if (!$company || empty($company['limit_feature']) || (int) $company['limit_feature'] !== 1) {
        return false;
    }

    if ($subscription_level === null) {
        $subscription_level = resolve_company_subscription_level($company);
    }

    if ((string) $subscription_level === (string) STARTER) {
        return true;
    }

    $state = isset($company['subscription_state']) ? (string) $company['subscription_state'] : '';
    return $state !== 'trialing';
}

/**
 * End-of-day timestamp for trial_expiry_date in the property timezone.
 *
 * @return int|null Unix timestamp
 */
function trial_expiry_end_timestamp($trial_expiry_date, $time_zone = 'UTC')
{
    $trial_expiry_date = trim((string) $trial_expiry_date);
    if ($trial_expiry_date === '' || $trial_expiry_date === '0000-00-00') {
        return null;
    }

    try {
        $tz = new DateTimeZone($time_zone ? $time_zone : 'UTC');
    } catch (Exception $e) {
        $tz = new DateTimeZone('UTC');
    }

    $end = DateTime::createFromFormat('Y-m-d', $trial_expiry_date, $tz);
    if (!$end) {
        return null;
    }
    $end->setTime(23, 59, 59);

    return $end->getTimestamp();
}

/**
 * Build trial + SaaS plan context for in-app subscription UI.
 *
 * @param array $company Row from Company_model::get_company()
 * @return array|null Null when not on trial
 */
function build_trial_subscription_context($company)
{
    if (!$company || !isset($company['subscription_state'])) {
        return null;
    }

    $state = (string) $company['subscription_state'];
    if ($state !== 'trialing' && $state !== 'trial_ended') {
        return null;
    }

    $CI =& get_instance();
    if (!isset($CI->Platform_settings_model)) {
        $CI->load->model('Platform_settings_model');
    }

    $trial_expiry_date = isset($company['trial_expiry_date']) ? trim($company['trial_expiry_date']) : '';
    if ($trial_expiry_date === '' || $trial_expiry_date === '0000-00-00') {
        $trial_days = $CI->Platform_settings_model->get_default_trial_days();
        $creation = isset($company['creation_date']) ? $company['creation_date'] : null;
        if ($creation) {
            $trial_expiry_date = date('Y-m-d', strtotime($creation . ' +' . (int) $trial_days . ' days'));
        }
    }

    $time_zone = isset($company['time_zone']) && $company['time_zone'] ? $company['time_zone'] : 'UTC';
    $expires_at = trial_expiry_end_timestamp($trial_expiry_date, $time_zone);

    $room_count = isset($company['number_of_rooms_actual']) ? (int) $company['number_of_rooms_actual'] : 1;
    if ($room_count < 1) {
        $room_count = 1;
    }

    $current_tier = $CI->Platform_settings_model->get_tier_for_room_count($room_count);
    $pricing_tiers = $CI->Platform_settings_model->get_pricing_tiers(true);
    $current_tier_id = $current_tier && isset($current_tier['id']) ? (int) $current_tier['id'] : 0;

    $current_plan_name = $current_tier && !empty($current_tier['name'])
        ? $current_tier['name']
        : subscription_level_display_name(isset($company['subscription_level']) ? $company['subscription_level'] : BASIC);

    $current_sort = $current_tier ? (int) $current_tier['sort_order'] : -1;
    $current_sub_level = isset($company['subscription_level']) ? (int) $company['subscription_level'] : 0;

    $upgrade_tiers = array();
    foreach ($pricing_tiers as $tier) {
        $tier_id = (int) $tier['id'];
        $tier['is_current'] = ($current_tier_id && $tier_id === $current_tier_id);
        if ($current_tier_id) {
            $tier['is_upgrade'] = $tier_id !== $current_tier_id && (int) $tier['sort_order'] > $current_sort;
        } else {
            $tier['is_upgrade'] = ((int) $tier['subscription_level'] > $current_sub_level);
        }
        $upgrade_tiers[] = $tier;
    }

    return array(
        'trial_expiry_date' => $trial_expiry_date,
        'trial_expires_at' => $expires_at,
        'trial_expires_iso' => $expires_at ? gmdate('c', $expires_at) : '',
        'default_trial_days' => $CI->Platform_settings_model->get_default_trial_days(),
        'room_count' => $room_count,
        'current_plan_name' => $current_plan_name,
        'current_tier_id' => $current_tier_id,
        'pricing_tiers' => $upgrade_tiers,
        'upgrade_url' => base_url('account_settings/subscription'),
        'is_expired' => ($state === 'trial_ended'),
    );
}

/**
 * Build paid subscription expiry context (for non-trial subscriptions).
 *
 * @param array $company Row from Company_model::get_company()
 * @return array|null
 */
function build_paid_plan_expiry_context($company)
{
    if (!$company || !isset($company['subscription_state'])) {
        return null;
    }

    $state = (string) $company['subscription_state'];
    if (!in_array($state, array('active', 'unpaid', 'canceled'), true)) {
        return null;
    }

    $expiration_date = isset($company['expiration_date']) ? trim((string) $company['expiration_date']) : '';
    if ($expiration_date === '' || $expiration_date === '0000-00-00') {
        return null;
    }

    $today = DateTime::createFromFormat('Y-m-d', date('Y-m-d'));
    $expiry = DateTime::createFromFormat('Y-m-d', $expiration_date);
    if (!$today || !$expiry) {
        return null;
    }

    $days_left = (int) $today->diff($expiry)->format('%r%a');
    $grace_days = saas_paid_plan_grace_days();

    return array(
        'expiration_date' => $expiration_date,
        'days_left' => $days_left,
        'grace_days' => $grace_days,
        'grace_ends_date' => date('Y-m-d', strtotime($expiration_date . ' +' . $grace_days . ' days')),
        'is_within_warning' => $days_left >= 0 && $days_left <= $grace_days,
        'is_in_grace' => $days_left < 0 && $days_left >= -$grace_days,
        'is_past_grace' => $days_left < -$grace_days,
        'grace_days_remaining' => $days_left < 0 ? max(0, $grace_days + $days_left) : $grace_days,
    );
}

/**
 * SaaS pricing tier assigned to a company (paid tier in meta, else room-count fit).
 *
 * @param array $company Row from Company_model::get_company()
 * @param int|null $room_count
 * @return array|null Tier row from Platform_settings_model
 */
function resolve_company_saas_tier($company, $room_count = null)
{
    if (!$company || !is_array($company)) {
        return null;
    }

    $CI =& get_instance();
    if (!isset($CI->Platform_settings_model)) {
        $CI->load->model('Platform_settings_model');
    }

    $tier_id = saas_meta_committed_tier_id(parse_company_subscription_meta($company));
    if ($tier_id > 0) {
        $tier = $CI->Platform_settings_model->get_pricing_tier($tier_id);
        if ($tier) {
            return $tier;
        }
    }

    if ($room_count === null) {
        $room_count = isset($company['number_of_rooms_actual']) ? (int) $company['number_of_rooms_actual'] : 1;
    }
    if ($room_count < 1) {
        $room_count = 1;
    }

    return $CI->Platform_settings_model->get_tier_for_room_count($room_count);
}

/**
 * Human-readable plan name for UI (SaaS tier name, not legacy subscription level).
 *
 * @param array $company Row from Company_model::get_company()
 * @param int|null $room_count
 * @return string
 */
function resolve_company_plan_display_name($company, $room_count = null)
{
    if (!$company || !is_array($company)) {
        return subscription_level_display_name((int) BASIC);
    }

    $meta = parse_company_subscription_meta($company);
    $committed_tier_id = saas_meta_committed_tier_id($meta);
    if ($committed_tier_id > 0) {
        $CI =& get_instance();
        if (!isset($CI->Platform_settings_model)) {
            $CI->load->model('Platform_settings_model');
        }
        $tier = $CI->Platform_settings_model->get_pricing_tier($committed_tier_id);
        if ($tier && !empty($tier['name'])) {
            return (string) $tier['name'];
        }
    }
    if (!empty($meta['tier_name']) && $committed_tier_id > 0) {
        return (string) $meta['tier_name'];
    }

    $tier = resolve_company_saas_tier($company, $room_count);
    if ($tier && !empty($tier['name'])) {
        return (string) $tier['name'];
    }

    return subscription_level_display_name(resolve_company_subscription_level($company));
}

/**
 * Compare two SaaS tiers for upgrade/downgrade UI (uses sort_order, then subscription_level).
 *
 * @param array $tier
 * @param array $current_tier
 * @return int -1 downgrade, 0 same, 1 upgrade
 */
function compare_saas_tier_rank($tier, $current_tier)
{
    if (!$tier || !$current_tier) {
        return 0;
    }

    $tier_sort = (int) $tier['sort_order'];
    $current_sort = (int) $current_tier['sort_order'];
    if ($tier_sort > $current_sort) {
        return 1;
    }
    if ($tier_sort < $current_sort) {
        return -1;
    }

    if ((int) $tier['id'] === (int) $current_tier['id']) {
        return 0;
    }

    $tier_level = resolve_subscription_level_for_tier($tier);
    $current_level = resolve_subscription_level_for_tier($current_tier);
    if ($tier_level > $current_level) {
        return 1;
    }
    if ($tier_level < $current_level) {
        return -1;
    }

    return 0;
}

/**
 * Label for the primary billing action on the subscription page.
 *
 * @param string $subscription_state
 * @param array|null $plan_expiry_context From build_paid_plan_expiry_context()
 * @param bool $trial_expired
 * @return string
 */
function resolve_subscription_billing_button_label($subscription_state, $plan_expiry_context = null, $trial_expired = false)
{
    $state = trim((string) $subscription_state);

    if ($trial_expired || $state === 'trial_ended') {
        return 'Subscribe now';
    }
    if ($state === 'trialing') {
        return 'Subscribe now';
    }
    if ($state === 'active' && is_array($plan_expiry_context)) {
        $days_left = (int) $plan_expiry_context['days_left'];
        if ($days_left > 0) {
            return 'Extend subscription';
        }
    }
    if ($state === 'unpaid' || $state === 'canceled') {
        return 'Renew now';
    }
    if (is_array($plan_expiry_context) && (int) $plan_expiry_context['days_left'] <= 0) {
        return 'Renew now';
    }

    return 'Pay now';
}

/**
 * Per-tier billing button label (Upgrade / Downgrade / extend / subscribe).
 *
 * @param array $tier
 * @param array|null $current_tier From resolve_company_saas_tier()
 * @param string $subscription_state
 * @param array|null $plan_expiry_context
 * @param bool $trial_expired
 * @return string
 */
function resolve_subscription_tier_button_label($tier, $current_tier, $subscription_state, $plan_expiry_context = null, $trial_expired = false)
{
    $base = resolve_subscription_billing_button_label($subscription_state, $plan_expiry_context, $trial_expired);

    if (!$tier || !$current_tier) {
        return $base;
    }

    $rank = compare_saas_tier_rank($tier, $current_tier);
    if ($rank === 0) {
        return $base;
    }
    if ($rank > 0) {
        return 'Upgrade';
    }

    return 'Downgrade';
}

/**
 * Expiry date after a successful SaaS renewal payment.
 * Stacks on the current expiration when the plan is still active.
 *
 * @param string $renewal_period "1 month", "month", "1 year", or "year"
 * @param array|null $company Row from Company_model::get_company()
 * @return string Y-m-d
 */
function compute_subscription_expiry_after_renewal($renewal_period, $company = null)
{
    $today = gmdate('Y-m-d');
    $base = $today;

    if (is_array($company)) {
        $state = isset($company['subscription_state']) ? trim((string) $company['subscription_state']) : '';
        $expiration = isset($company['expiration_date']) ? trim((string) $company['expiration_date']) : '';
        if ($state === 'active' && $expiration !== '' && $expiration !== '0000-00-00' && $expiration > $today) {
            $base = $expiration;
        }
    }

    $period = strtolower(trim((string) $renewal_period));
    if ($period === '1 year' || $period === 'year') {
        return gmdate('Y-m-d', strtotime($base . ' +1 year'));
    }

    return gmdate('Y-m-d', strtotime($base . ' +1 month'));
}

/**
 * Whether the first paid conversion date should be set on company_admin_panel_info.
 *
 * @param string|null $conversion_date
 * @return bool
 */
function should_set_subscription_conversion_date($conversion_date)
{
    $conversion_date = trim((string) $conversion_date);

    return $conversion_date === '' || $conversion_date === '0000-00-00';
}

/**
 * Label for a SaaS subscription charge in platform admin revenue reports.
 *
 * @param int $prior_payment_count Number of earlier subscription charges for the property
 * @return string
 */
function classify_saas_subscription_payment_type($prior_payment_count)
{
    return ((int) $prior_payment_count) > 0 ? 'Renewal' : 'New subscription';
}

/**
 * Human-readable label for subscription debtor list reason codes.
 *
 * @param string $reason
 * @return string
 */
function subscription_debtor_reason_label($reason)
{
    $labels = array(
        'plan_overdue' => 'Plan expired — renewal due',
        'trial_ended' => 'Trial ended — not subscribed',
        'trial_overdue' => 'Trial expired — not subscribed',
        'unpaid' => 'Marked unpaid',
        'due_soon' => 'Payment due soon',
    );

    return isset($labels[$reason]) ? $labels[$reason] : 'Payment due';
}

/**
 * Amount due for a subscription debtor row (renewal cost or tier fallback).
 *
 * @param array $row Debtor row from Admin_model::get_subscription_debtors()
 * @param array $tiers Pricing tiers from Platform_settings_model
 * @return float
 */
function resolve_subscription_debtor_amount_due($row, $tiers)
{
    $room_count = isset($row['number_of_rooms']) ? (int) $row['number_of_rooms'] : 1;
    if ($room_count < 1) {
        $room_count = 1;
    }

    $amount_due = isset($row['renewal_cost']) ? (float) $row['renewal_cost'] : 0;
    $meta = parse_company_subscription_meta($row);
    $selected_tier_id = saas_meta_committed_tier_id($meta);
    foreach ($tiers as $tier) {
        if ($selected_tier_id > 0) {
            if ((int) $tier['id'] === $selected_tier_id) {
                if ($amount_due <= 0 && isset($tier['monthly_price'])) {
                    $amount_due = (float) $tier['monthly_price'];
                }
                break;
            }
        } else {
            $min = (int) $tier['min_rooms'];
            $max = (isset($tier['max_rooms']) && $tier['max_rooms'] !== null && $tier['max_rooms'] !== '')
                ? (int) $tier['max_rooms']
                : null;
            if ($room_count >= $min && ($max === null || $room_count <= $max)) {
                if ($amount_due <= 0 && isset($tier['monthly_price'])) {
                    $amount_due = (float) $tier['monthly_price'];
                }
                break;
            }
        }
    }

    return max(0, $amount_due);
}

/**
 * Normalize renewal period input to "1 month" or "1 year".
 *
 * @param string $renewal_period
 * @return string
 */
function normalize_saas_renewal_period($renewal_period)
{
    $period = strtolower(trim((string) $renewal_period));
    if ($period === '1 year' || $period === 'year' || $period === 'annual' || $period === 'yearly') {
        return '1 year';
    }

    return '1 month';
}

/**
 * New due date after extending grace from today or a future due date.
 *
 * @param string|null $current_due_date Y-m-d
 * @param int $grace_days
 * @return string Y-m-d
 */
function compute_date_after_grace_extension($current_due_date, $grace_days)
{
    $today = gmdate('Y-m-d');
    $base = $today;
    $current = trim((string) $current_due_date);

    if ($current !== '' && $current !== '0000-00-00' && $current > $today) {
        $base = $current;
    }

    return gmdate('Y-m-d', strtotime($base . ' +' . max(1, (int) $grace_days) . ' days'));
}

/**
 * Resolve pricing tier id from subscription meta or room count.
 *
 * @param array $company
 * @param array<int, array<string, mixed>> $tiers
 * @return int
 */
function resolve_company_pricing_tier_id($company, $tiers)
{
    $selected_tier_id = saas_meta_committed_tier_id(parse_company_subscription_meta($company));
    if ($selected_tier_id > 0) {
        return $selected_tier_id;
    }

    $room_count = isset($company['number_of_rooms']) ? (int) $company['number_of_rooms'] : 0;
    if ($room_count < 1 && isset($company['number_of_rooms_actual'])) {
        $room_count = (int) $company['number_of_rooms_actual'];
    }
    if ($room_count < 1) {
        $room_count = 1;
    }

    foreach ($tiers as $tier) {
        $min = (int) $tier['min_rooms'];
        $max = (isset($tier['max_rooms']) && $tier['max_rooms'] !== null && $tier['max_rooms'] !== '')
            ? (int) $tier['max_rooms']
            : null;
        if ($room_count >= $min && ($max === null || $room_count <= $max)) {
            return isset($tier['id']) ? (int) $tier['id'] : 0;
        }
    }

    return 0;
}

/**
 * Unique reference for a platform-admin manual subscription payment.
 *
 * @param int $company_id
 * @return string
 */
function build_manual_saas_payment_reference($company_id)
{
    return 'manual_admin_' . (int) $company_id . '_' . gmdate('YmdHis') . '_' . bin2hex(random_bytes(4));
}

/**
 * Record an offline/manual SaaS subscription payment (bank transfer, etc.).
 * Mirrors Paystack finalize: charge + payment rows, active subscription, extended expiry.
 *
 * @param array $options company_id, amount, renewal_period, tier_id?, payment_date?, notes?, recorded_by?
 * @return array{success: bool, error?: string, reference?: string, expiration_date?: string, amount?: float}
 */
function record_saas_subscription_payment($options)
{
    $CI =& get_instance();
    $company_id = isset($options['company_id']) ? (int) $options['company_id'] : 0;
    $amount = isset($options['amount']) ? round((float) $options['amount'], 2) : 0;
    $renewal_period = normalize_saas_renewal_period(isset($options['renewal_period']) ? $options['renewal_period'] : '1 month');
    $tier_id = isset($options['tier_id']) ? (int) $options['tier_id'] : 0;
    $payment_date = isset($options['payment_date']) ? trim((string) $options['payment_date']) : gmdate('Y-m-d');

    if ($company_id < 1) {
        return array('success' => false, 'error' => 'Invalid company.');
    }
    if ($amount <= 0) {
        return array('success' => false, 'error' => 'Amount must be greater than zero.');
    }

    $expiry = DateTime::createFromFormat('Y-m-d', $payment_date);
    if (!$expiry || $expiry->format('Y-m-d') !== $payment_date) {
        return array('success' => false, 'error' => 'Invalid payment date. Use YYYY-MM-DD.');
    }

    if (!$CI->db->table_exists('company_payment') && !$CI->db->table_exists('company_payments')) {
        return array('success' => false, 'error' => 'Billing records table is missing.');
    }

    $CI->load->model(array(
        'Company_model',
        'Company_subscription_model',
        'Company_charge_model',
        'Company_payment_model',
        'Platform_settings_model',
    ));

    $company = $CI->Company_model->get_company($company_id);
    if (!$company || !isset($company['company_id'])) {
        return array('success' => false, 'error' => 'Property not found.');
    }

    $tiers = $CI->Platform_settings_model->get_pricing_tiers(false);
    if ($tier_id < 1) {
        $tier_id = resolve_company_pricing_tier_id($company, $tiers);
    }

    $tier = $tier_id > 0 ? $CI->Platform_settings_model->get_pricing_tier($tier_id) : null;
    if (!$tier) {
        return array('success' => false, 'error' => 'Pricing tier not found.');
    }

    $reference = build_manual_saas_payment_reference($company_id);
    if ($CI->Company_charge_model->does_transaction_exist($company_id, $reference)
        || $CI->Company_payment_model->does_transaction_exist($company_id, $reference)) {
        return array('success' => false, 'error' => 'Could not generate a unique payment reference.');
    }

    $expiry_date = compute_subscription_expiry_after_renewal($renewal_period, $company);
    $charge = array(array(
        'company_id' => $company_id,
        'description' => $reference,
        'amount' => $amount,
        'charge_type' => 'subscription',
        'date' => $payment_date,
        'is_deleted' => 0,
    ));
    $payment = array(array(
        'company_id' => $company_id,
        'description' => $reference,
        'amount' => $amount,
        'date' => $payment_date,
        'is_deleted' => 0,
    ));

    $CI->Company_charge_model->insert_company_charge($charge);
    $CI->Company_payment_model->insert_company_payment($payment);

    $current = $CI->Company_subscription_model->get_company_subscription($company_id);
    $meta = array();
    if ($current && !empty($current['meta_data'])) {
        $decoded = json_decode($current['meta_data'], true);
        if (is_array($decoded)) {
            $meta = $decoded;
        }
    }

    $meta['last_reference'] = $reference;
    $meta['last_paid_at'] = gmdate('c');
    $meta['last_gateway'] = 'manual';
    $meta['last_currency'] = isset($tier['currency']) ? strtoupper((string) $tier['currency']) : saas_default_currency();
    $meta['tier_id'] = (int) $tier['id'];
    $meta['tier_name'] = (string) $tier['name'];
    $meta['tier_updated_at'] = gmdate('c');
    if (!empty($options['notes'])) {
        $meta['last_admin_note'] = trim((string) $options['notes']);
    }
    if (!empty($options['recorded_by'])) {
        $meta['last_recorded_by'] = trim((string) $options['recorded_by']);
    }

    $CI->Company_subscription_model->insert_or_update_company_subscription($company_id, array(
        'subscription_id' => $reference,
        'subscription_state' => 'active',
        'subscription_level' => resolve_subscription_level_for_tier($tier),
        'limit_feature' => 1,
        'renewal_cost' => $amount,
        'renewal_period' => $renewal_period,
        'payment_method' => 'manual',
        'expiration_date' => $expiry_date,
        'meta_data' => json_encode($meta),
    ));
    $CI->Company_subscription_model->update_company_balance($company_id);

    $existing_conversion = isset($company['conversion_date']) ? $company['conversion_date'] : null;
    if (should_set_subscription_conversion_date($existing_conversion)) {
        $CI->Company_model->update_company_admin_panel_info($company_id, array(
            'conversion_date' => gmdate('Y-m-d'),
        ));
    }

    $receipt_url = saas_customer_subscription_receipt_url($company_id, $reference);
    $CI->Company_subscription_model->update_company_subscription($company_id, array(
        'invoice_link' => $receipt_url,
    ));

    platform_notify_payment_received($company_id, $amount, $reference, $company);

    return array(
        'success' => true,
        'reference' => $reference,
        'expiration_date' => $expiry_date,
        'amount' => $amount,
        'renewal_period' => $renewal_period,
    );
}

/**
 * Extend trial or paid plan expiry by a grace period (platform admin).
 *
 * @param int $company_id
 * @param int|null $grace_days Defaults to saas_paid_plan_grace_days()
 * @return array{success: bool, error?: string, new_expiry_date?: string, subscription_state?: string}
 */
function extend_subscription_grace_period($company_id, $grace_days = null)
{
    $CI =& get_instance();
    $company_id = (int) $company_id;
    $grace_days = $grace_days === null ? saas_paid_plan_grace_days() : max(1, (int) $grace_days);

    if ($company_id < 1) {
        return array('success' => false, 'error' => 'Invalid company.');
    }

    $CI->load->model(array('Company_model', 'Company_subscription_model', 'Admin_model'));
    $company = $CI->Company_model->get_company($company_id);
    if (!$company || !isset($company['company_id'])) {
        return array('success' => false, 'error' => 'Property not found.');
    }

    $subscription = $CI->Company_subscription_model->get_company_subscription($company_id);
    $state = $subscription && isset($subscription['subscription_state'])
        ? (string) $subscription['subscription_state']
        : '';

    $trial_states = array('trialing', 'trial_ended');
    $uses_trial_date = in_array($state, $trial_states, true)
        || ($state === '' && !empty($company['trial_expiry_date']));

    if ($uses_trial_date) {
        $current_due = isset($company['trial_expiry_date']) ? (string) $company['trial_expiry_date'] : '';
        $new_expiry = compute_date_after_grace_extension($current_due, $grace_days);

        $panel = $CI->Admin_model->get_single_company_admin_panel_info($company_id);
        if ($panel) {
            $CI->Admin_model->update_company_admin_panel_info($company_id, array(
                'trial_expiry_date' => $new_expiry,
            ));
        } else {
            $CI->Admin_model->insert_company_admin_panel_info(array(
                'company_id' => $company_id,
                'creation_date' => date('Y-m-d G:i'),
                'trial_expiry_date' => $new_expiry,
            ));
        }

        if ($state === 'trial_ended') {
            $CI->Company_subscription_model->update_company_subscription($company_id, array(
                'subscription_state' => 'trialing',
            ));
            $state = 'trialing';
        }

        return array(
            'success' => true,
            'new_expiry_date' => $new_expiry,
            'subscription_state' => $state,
            'grace_days' => $grace_days,
        );
    }

    $current_due = isset($company['expiration_date']) ? (string) $company['expiration_date'] : '';
    $new_expiry = compute_date_after_grace_extension($current_due, $grace_days);

    $CI->Company_subscription_model->update_company_subscription($company_id, array(
        'expiration_date' => $new_expiry,
    ));

    return array(
        'success' => true,
        'new_expiry_date' => $new_expiry,
        'subscription_state' => $state !== '' ? $state : 'active',
        'grace_days' => $grace_days,
    );
}

/**
 * Build subject and HTML body for a subscription payment reminder email.
 *
 * @param array $company Row from Company_model::get_company()
 * @param array $context amount_due, due_date, property_name
 * @return array{subject: string, body: string}
 */
function build_subscription_payment_reminder_email($company, $context = array())
{
    $property_name = !empty($context['property_name'])
        ? (string) $context['property_name']
        : (isset($company['name']) ? (string) $company['name'] : 'your property');
    $due_date = !empty($context['due_date']) ? (string) $context['due_date'] : '';
    $amount_due = isset($context['amount_due']) ? (float) $context['amount_due'] : 0;
    $currency_symbol = saas_currency_symbol();
    $subscription_url = function_exists('base_url') ? base_url('account_settings/subscription') : '/account_settings/subscription';

    $amount_label = $amount_due > 0
        ? $currency_symbol . format_saas_price_amount($amount_due)
        : '';

    $rendered = render_platform_email_template('payment_reminder', array(
        'property_name' => $property_name,
        'due_date' => ($due_date !== '' && $due_date !== '0000-00-00') ? $due_date : '—',
        'amount_due' => $amount_label !== '' ? $amount_label : '—',
        'subscription_url' => $subscription_url,
    ));
    if ($rendered) {
        return $rendered;
    }

    $subject = 'Payment reminder: ' . $property_name . ' subscription';
    $lines = array(
        '<p>Hello,</p>',
        '<p>This is a reminder that your Veurion subscription for <strong>' . htmlspecialchars($property_name, ENT_QUOTES, 'UTF-8') . '</strong> requires payment.</p>',
    );

    if ($due_date !== '' && $due_date !== '0000-00-00') {
        $lines[] = '<p>Due date: <strong>' . htmlspecialchars($due_date, ENT_QUOTES, 'UTF-8') . '</strong></p>';
    }
    if ($amount_due > 0) {
        $lines[] = '<p>Estimated amount: <strong>' . htmlspecialchars($currency_symbol, ENT_QUOTES, 'UTF-8')
            . htmlspecialchars(format_saas_price_amount($amount_due), ENT_QUOTES, 'UTF-8') . '</strong></p>';
    }

    $lines[] = '<p><a href="' . htmlspecialchars($subscription_url, ENT_QUOTES, 'UTF-8') . '">Open your subscription page</a> to pay online or contact support if you have already sent a bank transfer.</p>';
    $lines[] = '<p>Thank you,<br>Veurion Support</p>';

    return array(
        'subject' => $subject,
        'body' => implode("\n", $lines),
    );
}


/**
 * Compute tenant health score (0–100) from risk flags.
 *
 * @param array<string, bool> $flags
 * @return int
 */
function compute_tenant_health_score($flags)
{
    $score = 100;
    if (!empty($flags['no_bookings'])) {
        $score -= 30;
    }
    if (!empty($flags['trial_expired'])) {
        $score -= 40;
    } elseif (!empty($flags['trial_expiring_soon'])) {
        $score -= 20;
    }
    if (!empty($flags['night_audit_overdue'])) {
        $score -= 25;
    }
    if (!empty($flags['channel_sync_issue'])) {
        $score -= 20;
    }
    if (!empty($flags['inactive_login'])) {
        $score -= 10;
    }

    return max(0, min(100, $score));
}

/**
 * @param int $score
 * @return string critical|at_risk|healthy
 */
function tenant_health_risk_level($score)
{
    $score = (int) $score;
    if ($score < 50) {
        return 'critical';
    }
    if ($score < 70) {
        return 'at_risk';
    }

    return 'healthy';
}

/**
 * Human-readable labels for tenant health risk flags.
 *
 * @return array<string, string>
 */
function tenant_health_flag_labels()
{
    return array(
        'no_bookings' => 'No bookings',
        'trial_expiring_soon' => 'Trial expiring soon',
        'trial_expired' => 'Trial expired',
        'night_audit_overdue' => 'Night audit overdue',
        'channel_sync_issue' => 'Channel sync stale',
        'inactive_login' => 'No login in 30 days',
    );
}

/**
 * Parse last cron run timestamp from a minical-cron log file.
 *
 * @param string $log_path
 * @return string|null ISO-like timestamp or null
 */
function parse_cron_log_last_run($log_path)
{
    if (!is_readable($log_path)) {
        return null;
    }

    $content = @file_get_contents($log_path);
    if ($content === false || $content === '') {
        return null;
    }

    if (preg_match_all('/===== ([0-9T:+\-]+) cron /', $content, $matches) && !empty($matches[1])) {
        return end($matches[1]);
    }

    return null;
}

/**
 * Session keys used when a platform admin impersonates a tenant.
 *
 * @return array<string, string>
 */
function platform_admin_impersonation_session_keys()
{
    return array(
        'active' => 'pa_impersonating',
        'company_id' => 'pa_impersonating_company_id',
        'company_name' => 'pa_impersonating_company_name',
        'return_url' => 'pa_impersonator_return',
    );
}

/**
 * @return bool
 */
function platform_admin_is_impersonating()
{
    $CI =& get_instance();
    $keys = platform_admin_impersonation_session_keys();

    return (bool) $CI->session->userdata($keys['active']);
}

/**
 * Mark a subscription debtor as contacted (stores timestamp in subscription meta_data).
 *
 * @param int $company_id
 * @param string|null $contacted_by Admin email or identifier
 * @param string $note Optional note
 * @return array{success: bool, error?: string, contacted_at?: string}
 */
function mark_subscription_debtor_contacted($company_id, $contacted_by = null, $note = '')
{
    $CI =& get_instance();
    $company_id = (int) $company_id;

    if ($company_id < 1) {
        return array('success' => false, 'error' => 'Invalid company.');
    }

    $CI->load->model(array('Company_model', 'Company_subscription_model'));
    $company = $CI->Company_model->get_company($company_id);
    if (!$company || !isset($company['company_id'])) {
        return array('success' => false, 'error' => 'Property not found.');
    }

    $subscription = $CI->Company_subscription_model->get_company_subscription($company_id);
    $meta = array();
    if ($subscription && !empty($subscription['meta_data'])) {
        $decoded = json_decode($subscription['meta_data'], true);
        if (is_array($decoded)) {
            $meta = $decoded;
        }
    }

    $contacted_at = gmdate('c');
    $meta['debtor_last_contacted_at'] = $contacted_at;
    if ($contacted_by !== null && trim((string) $contacted_by) !== '') {
        $meta['debtor_last_contacted_by'] = trim((string) $contacted_by);
    }
    if (trim((string) $note) !== '') {
        $meta['debtor_last_contact_note'] = trim((string) $note);
    }

    $CI->Company_subscription_model->insert_or_update_company_subscription($company_id, array(
        'meta_data' => json_encode($meta),
    ));

    return array(
        'success' => true,
        'contacted_at' => $contacted_at,
    );
}

/**
 * Extract debtor last-contacted timestamp from subscription meta_data JSON.
 *
 * @param string|null $meta_data_json
 * @return string|null ISO8601 or null
 */
function subscription_debtor_last_contacted_at($meta_data_json)
{
    if ($meta_data_json === null || trim((string) $meta_data_json) === '') {
        return null;
    }
    $decoded = json_decode($meta_data_json, true);
    if (!is_array($decoded) || empty($decoded['debtor_last_contacted_at'])) {
        return null;
    }
    return (string) $decoded['debtor_last_contacted_at'];
}

/**
 * Build receipt number from payment reference.
 *
 * @param string $reference
 * @return string
 */
function build_saas_receipt_number($reference)
{
    $ref = trim((string) $reference);
    if ($ref === '') {
        return 'VEU-' . gmdate('Ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 6));
    }
    if (strlen($ref) <= 32) {
        return strtoupper($ref);
    }
    return 'VEU-' . strtoupper(substr(md5($ref), 0, 12));
}

/**
 * URL for platform admin subscription receipt page.
 *
 * @param int $company_id
 * @param string $reference
 * @return string
 */
function saas_subscription_receipt_url($company_id, $reference)
{
    $company_id = (int) $company_id;
    $reference = rawurlencode(trim((string) $reference));
    return base_url('admin/subscription_receipt/' . $company_id . '/' . $reference);
}

/**
 * Secret for signed customer receipt links (no login required).
 *
 * @return string
 */
function saas_receipt_hmac_secret()
{
    if (function_exists('minical_env')) {
        $secret = trim((string) minical_env('MIGRATION_SECRET', ''));
        if ($secret !== '') {
            return $secret;
        }
        $db_pass = trim((string) minical_env('DATABASE_PASS', ''));
        if ($db_pass !== '') {
            return $db_pass;
        }
    }
    return 'minical';
}

/**
 * HMAC token for a customer-facing subscription receipt link.
 *
 * @param int $company_id
 * @param string $reference
 * @return string
 */
function saas_subscription_receipt_token($company_id, $reference)
{
    return hash_hmac(
        'sha256',
        (int) $company_id . '|' . trim((string) $reference),
        saas_receipt_hmac_secret()
    );
}

/**
 * Customer-facing printable receipt URL (works from email without admin login).
 *
 * @param int $company_id
 * @param string $reference
 * @return string
 */
function saas_customer_subscription_receipt_url($company_id, $reference)
{
    $company_id = (int) $company_id;
    $reference = trim((string) $reference);
    $token = saas_subscription_receipt_token($company_id, $reference);

    return base_url(
        'account_settings/subscription_receipt/'
        . rawurlencode($reference)
        . '?token=' . rawurlencode($token)
    );
}

/**
 * Verify a customer receipt token.
 *
 * @param int $company_id
 * @param string $reference
 * @param string $token
 * @return bool
 */
function saas_subscription_receipt_token_valid($company_id, $reference, $token)
{
    $expected = saas_subscription_receipt_token($company_id, $reference);
    return hash_equals($expected, trim((string) $token));
}

/**
 * Load receipt row data for HTML/email generation.
 *
 * @param int $company_id
 * @param string $reference
 * @return array|null
 */
function build_saas_subscription_receipt_data($company_id, $reference)
{
    $CI =& get_instance();
    $company_id = (int) $company_id;
    $reference = trim((string) $reference);
    if ($company_id < 1 || $reference === '') {
        return null;
    }

    $CI->load->model(array(
        'Company_model',
        'Company_subscription_model',
        'Platform_settings_model',
        'Admin_model',
    ));

    $payment = $CI->Admin_model->get_saas_subscription_payment($company_id, $reference);
    if (!$payment) {
        return null;
    }

    $company = $CI->Company_model->get_company($company_id);
    if (!$company) {
        return null;
    }

    $tiers = $CI->Platform_settings_model->get_pricing_tiers(false);
    $tier_id = resolve_company_pricing_tier_id($company, $tiers);
    $tier_name = '';
    $currency = saas_default_currency();
    if ($tier_id > 0) {
        $tier = $CI->Platform_settings_model->get_pricing_tier($tier_id);
        if ($tier) {
            $tier_name = isset($tier['name']) ? (string) $tier['name'] : '';
            if (!empty($tier['currency'])) {
                $currency = strtoupper((string) $tier['currency']);
            }
        }
    }

    $meta = array();
    if (!empty($company['meta_data'])) {
        $decoded = json_decode($company['meta_data'], true);
        if (is_array($decoded)) {
            $meta = $decoded;
        }
    }
    if (!empty($meta['last_currency'])) {
        $currency = strtoupper((string) $meta['last_currency']);
    }
    if (!empty($meta['tier_name'])) {
        $tier_name = (string) $meta['tier_name'];
    }

    $subscription = $CI->Company_subscription_model->get_company_subscription($company_id);
    $notes = !empty($meta['last_admin_note']) ? (string) $meta['last_admin_note'] : '';
    $plan_expires = isset($company['expiration_date']) ? (string) $company['expiration_date'] : '';
    $renewal_period = ($subscription && isset($subscription['renewal_period']))
        ? (string) $subscription['renewal_period']
        : '';

    return array(
        'company_id' => $company_id,
        'property_name' => isset($company['name']) ? (string) $company['name'] : 'Property',
        'amount' => isset($payment['amount']) ? (float) $payment['amount'] : 0,
        'currency' => $currency,
        'paid_date' => isset($payment['paid_date']) ? (string) $payment['paid_date'] : '',
        'reference' => $reference,
        'payment_type' => isset($payment['payment_type']) ? (string) $payment['payment_type'] : 'Subscription payment',
        'renewal_period' => $renewal_period,
        'plan_expires' => $plan_expires,
        'tier_name' => $tier_name,
        'notes' => $notes,
    );
}

/**
 * Email subscription payment receipt to the property owner.
 *
 * @param int $company_id
 * @param string $reference
 * @return bool
 */
function send_saas_subscription_receipt_email($company_id, $reference)
{
    $CI =& get_instance();
    $company_id = (int) $company_id;
    $reference = trim((string) $reference);
    if ($company_id < 1 || $reference === '') {
        return false;
    }

    if (!minical_env('SMTP_USER', '')) {
        return false;
    }

    $CI->load->model('Company_model');
    $company = $CI->Company_model->get_company($company_id);
    if (!$company) {
        return false;
    }

    $recipient = !empty($company['owner_email']) ? trim((string) $company['owner_email']) : '';
    if ($recipient === '' && !empty($company['email'])) {
        $recipient = trim((string) $company['email']);
    }
    if ($recipient === '') {
        return false;
    }

    $receipt = build_saas_subscription_receipt_data($company_id, $reference);
    if (!$receipt) {
        return false;
    }

    $email_content = build_saas_subscription_receipt_email($company, $receipt);

    $CI->load->library('email');
    $CI->load->helper('email');
    $from_email = minical_smtp_from_email();
    $CI->email->from($from_email, 'Veurion');
    $CI->email->to($recipient);
    $CI->email->subject($email_content['subject']);
    $CI->email->message($email_content['body']);
    $CI->email->set_mailtype('html');

    if (!$CI->email->send()) {
        log_message('error', 'Subscription receipt email failed for company ' . $company_id . ': '
            . $CI->email->print_debugger(array('headers')));
        return false;
    }

    return true;
}

/**
 * Build subject and HTML body when admin extends subscription grace.
 *
 * @param array $company
 * @param array $context new_expiry_date, grace_days, subscription_state
 * @return array{subject: string, body: string}
 */
function build_subscription_grace_extended_email($company, $context = array())
{
    $property_name = isset($company['name']) ? (string) $company['name'] : 'your property';
    $new_expiry = !empty($context['new_expiry_date']) ? (string) $context['new_expiry_date'] : '';
    $grace_days = isset($context['grace_days']) ? (int) $context['grace_days'] : 0;
    $subscription_url = function_exists('base_url') ? base_url('account_settings/subscription') : '/account_settings/subscription';

    $subject = 'Subscription extended: ' . $property_name;
    $lines = array(
        '<p>Hello,</p>',
        '<p>Your Veurion subscription for <strong>' . htmlspecialchars($property_name, ENT_QUOTES, 'UTF-8') . '</strong> has been extended.</p>',
    );
    if ($grace_days > 0) {
        $lines[] = '<p>Extension: <strong>' . (int) $grace_days . ' day' . ($grace_days === 1 ? '' : 's') . '</strong></p>';
    }
    if ($new_expiry !== '' && $new_expiry !== '0000-00-00') {
        $lines[] = '<p>New expiry date: <strong>' . htmlspecialchars($new_expiry, ENT_QUOTES, 'UTF-8') . '</strong></p>';
    }
    $lines[] = '<p><a href="' . htmlspecialchars($subscription_url, ENT_QUOTES, 'UTF-8') . '">Open your subscription page</a> to review your plan or pay online.</p>';
    $lines[] = '<p>Thank you,<br>Veurion Support</p>';

    return array(
        'subject' => $subject,
        'body' => implode("\n", $lines),
    );
}


/**
 * Email property owner when subscription grace is extended.
 *
 * @param int $company_id
 * @param array $context new_expiry_date, grace_days, subscription_state
 * @return bool
 */
function send_subscription_grace_extended_email($company_id, $context = array())
{
    $CI =& get_instance();
    $company_id = (int) $company_id;
    if ($company_id < 1 || !minical_env('SMTP_USER', '')) {
        return false;
    }

    $CI->load->model('Company_model');
    $company = $CI->Company_model->get_company($company_id);
    if (!$company) {
        return false;
    }

    $recipient = !empty($company['owner_email']) ? trim((string) $company['owner_email']) : '';
    if ($recipient === '' && !empty($company['email'])) {
        $recipient = trim((string) $company['email']);
    }
    if ($recipient === '') {
        return false;
    }

    $email_content = build_subscription_grace_extended_email($company, $context);

    $CI->load->library('email');
    $CI->load->helper('email');
    $from_email = minical_smtp_from_email();
    $CI->email->from($from_email, 'Veurion');
    $CI->email->to($recipient);
    $CI->email->subject($email_content['subject']);
    $CI->email->message($email_content['body']);
    $CI->email->set_mailtype('html');

    if (!$CI->email->send()) {
        log_message('error', 'Grace extension email failed for company ' . $company_id . ': '
            . $CI->email->print_debugger(array('headers')));
        return false;
    }

    return true;
}

/**
 * Build HTML receipt for a SaaS subscription payment.
 *
 * @param array $receipt Keys: property_name, company_id, amount, currency, paid_date, reference,
 *                      payment_type, renewal_period, plan_expires, tier_name, notes
 * @return string HTML
 */
function build_saas_subscription_receipt_html($receipt)
{
    $property_name = isset($receipt['property_name']) ? (string) $receipt['property_name'] : 'Property';
    $company_id = isset($receipt['company_id']) ? (int) $receipt['company_id'] : 0;
    $amount = isset($receipt['amount']) ? (float) $receipt['amount'] : 0;
    $currency = isset($receipt['currency']) ? strtoupper((string) $receipt['currency']) : saas_default_currency();
    $currency_symbol = saas_currency_symbol($currency);
    $paid_date = isset($receipt['paid_date']) ? (string) $receipt['paid_date'] : gmdate('Y-m-d');
    $reference = isset($receipt['reference']) ? (string) $receipt['reference'] : '';
    $payment_type = isset($receipt['payment_type']) ? (string) $receipt['payment_type'] : 'Subscription payment';
    $renewal_period = isset($receipt['renewal_period']) ? (string) $receipt['renewal_period'] : '';
    $plan_expires = isset($receipt['plan_expires']) ? (string) $receipt['plan_expires'] : '';
    $tier_name = isset($receipt['tier_name']) ? (string) $receipt['tier_name'] : '';
    $notes = isset($receipt['notes']) ? (string) $receipt['notes'] : '';
    $receipt_number = build_saas_receipt_number($reference);
    $issued_at = gmdate('Y-m-d H:i') . ' UTC';

    $rows = array(
        array('Receipt #', htmlspecialchars($receipt_number, ENT_QUOTES, 'UTF-8')),
        array('Property', htmlspecialchars($property_name, ENT_QUOTES, 'UTF-8') . ' (ID ' . $company_id . ')'),
        array('Payment date', htmlspecialchars($paid_date, ENT_QUOTES, 'UTF-8')),
        array('Description', htmlspecialchars($payment_type, ENT_QUOTES, 'UTF-8')),
    );
    if ($tier_name !== '') {
        $rows[] = array('Plan', htmlspecialchars($tier_name, ENT_QUOTES, 'UTF-8'));
    }
    if ($renewal_period !== '') {
        $rows[] = array('Renewal period', htmlspecialchars($renewal_period, ENT_QUOTES, 'UTF-8'));
    }
    if ($plan_expires !== '' && $plan_expires !== '0000-00-00') {
        $rows[] = array('Plan valid until', htmlspecialchars($plan_expires, ENT_QUOTES, 'UTF-8'));
    }
    if ($reference !== '') {
        $rows[] = array('Reference', htmlspecialchars($reference, ENT_QUOTES, 'UTF-8'));
    }

    $table_rows = '';
    foreach ($rows as $row) {
        $table_rows .= '<tr><th style="text-align:left;padding:8px 12px;border-bottom:1px solid #e2e8f0;color:#64748b;font-weight:600;">'
            . $row[0] . '</th><td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;">' . $row[1] . '</td></tr>';
    }

    $notes_block = '';
    if ($notes !== '') {
        $notes_block = '<p style="margin:16px 0 0;font-size:14px;color:#64748b;"><strong>Notes:</strong> '
            . nl2br(htmlspecialchars($notes, ENT_QUOTES, 'UTF-8')) . '</p>';
    }

    return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Receipt ' . htmlspecialchars($receipt_number, ENT_QUOTES, 'UTF-8') . '</title>'
        . '<style>@media print{ .no-print{display:none!important;} body{margin:0;padding:24px;} }</style></head><body style="font-family:system-ui,-apple-system,sans-serif;color:#0f172a;background:#f8fafc;margin:0;padding:24px;">'
        . '<div style="max-width:640px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:32px;">'
        . '<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;">'
        . '<div><h1 style="margin:0 0 4px;font-size:24px;">Veurion</h1><p style="margin:0;color:#64748b;font-size:14px;">Subscription receipt</p></div>'
        . '<div style="text-align:right;font-size:13px;color:#64748b;">Issued ' . htmlspecialchars($issued_at, ENT_QUOTES, 'UTF-8') . '</div></div>'
        . '<table style="width:100%;border-collapse:collapse;font-size:14px;margin-bottom:24px;">' . $table_rows . '</table>'
        . '<div style="background:#f1f5f9;border-radius:8px;padding:16px 20px;">'
        . '<div style="font-size:13px;color:#64748b;margin-bottom:4px;">Amount paid</div>'
        . '<div style="font-size:28px;font-weight:700;">' . htmlspecialchars($currency_symbol, ENT_QUOTES, 'UTF-8')
        . htmlspecialchars(format_saas_price_amount($amount, 2), ENT_QUOTES, 'UTF-8')
        . ' <span style="font-size:14px;font-weight:600;color:#64748b;">' . htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') . '</span></div></div>'
        . $notes_block
        . '<p style="margin:24px 0 0;font-size:13px;color:#64748b;">Thank you for your payment. This receipt confirms your Veurion SaaS subscription payment.</p>'
        . '<p class="no-print" style="margin-top:24px;"><button onclick="window.print()" style="padding:10px 16px;background:#2563eb;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:14px;">Print receipt</button></p>'
        . '</div></body></html>';
}

/**
 * Build subject and HTML body for subscription payment receipt email.
 *
 * @param array $company
 * @param array $receipt Same keys as build_saas_subscription_receipt_html()
 * @return array{subject: string, body: string}
 */
function build_saas_subscription_receipt_email($company, $receipt)
{
    $property_name = isset($receipt['property_name']) ? (string) $receipt['property_name'] : (isset($company['name']) ? (string) $company['name'] : 'your property');
    $amount = isset($receipt['amount']) ? (float) $receipt['amount'] : 0;
    $currency = isset($receipt['currency']) ? strtoupper((string) $receipt['currency']) : saas_default_currency();
    $currency_symbol = saas_currency_symbol($currency);
    $paid_date = isset($receipt['paid_date']) ? (string) $receipt['paid_date'] : gmdate('Y-m-d');
    $reference = isset($receipt['reference']) ? (string) $receipt['reference'] : '';
    $receipt_number = build_saas_receipt_number($reference);
    $receipt_url = ($reference !== '' && isset($receipt['company_id']))
        ? saas_customer_subscription_receipt_url((int) $receipt['company_id'], $reference)
        : '';

    $subject = 'Payment receipt: ' . $property_name . ' — ' . $receipt_number;
    $lines = array(
        '<p>Hello,</p>',
        '<p>Thank you for your Veurion subscription payment for <strong>' . htmlspecialchars($property_name, ENT_QUOTES, 'UTF-8') . '</strong>.</p>',
        '<p><strong>Amount:</strong> ' . htmlspecialchars($currency_symbol, ENT_QUOTES, 'UTF-8')
            . htmlspecialchars(format_saas_price_amount($amount, 2), ENT_QUOTES, 'UTF-8')
            . ' (' . htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') . ')<br>',
        '<strong>Date:</strong> ' . htmlspecialchars($paid_date, ENT_QUOTES, 'UTF-8') . '<br>',
        '<strong>Receipt #:</strong> ' . htmlspecialchars($receipt_number, ENT_QUOTES, 'UTF-8') . '</p>',
    );
    if ($receipt_url !== '') {
        $lines[] = '<p><a href="' . htmlspecialchars($receipt_url, ENT_QUOTES, 'UTF-8') . '">View printable receipt</a></p>';
    }
    $lines[] = '<p>Thank you,<br>Veurion Support</p>';

    return array(
        'subject' => $subject,
        'body' => implode("\n", $lines),
    );
}

/**
 * Supported platform webhook event labels.
 *
 * @return array<string, string>
 */
function platform_webhook_event_labels()
{
    return array(
        'trial_signup' => 'New trial sign-up',
        'payment_received' => 'Payment received',
        'debtor_overdue' => 'Debtor overdue',
    );
}

/**
 * @param string $stored JSON or comma-separated list
 * @return array<int, string>
 */
function platform_webhook_enabled_events_from_storage($stored)
{
    $allowed = array_keys(platform_webhook_event_labels());
    if ($stored === '' || $stored === null) {
        return $allowed;
    }

    $decoded = json_decode((string) $stored, true);
    if (is_array($decoded)) {
        return array_values(array_intersect($decoded, $allowed));
    }

    $parts = array_map('trim', explode(',', (string) $stored));

    return array_values(array_intersect($parts, $allowed));
}

/**
 * @return array<int, string>
 */
function platform_webhook_enabled_events()
{
    $CI =& get_instance();
    if (!$CI->db->table_exists('platform_settings')) {
        return array_keys(platform_webhook_event_labels());
    }

    $CI->load->model('Platform_settings_model');

    return platform_webhook_enabled_events_from_storage(
        $CI->Platform_settings_model->get('webhook_enabled_events', '')
    );
}

/**
 * Replace {placeholder} tokens in platform email templates.
 *
 * @param string $text
 * @param array<string, string> $vars
 * @return string
 */
function apply_platform_email_template_vars($text, $vars)
{
    foreach ($vars as $key => $value) {
        $text = str_replace('{' . $key . '}', (string) $value, $text);
    }

    return $text;
}

/**
 * Render a platform email template from the database.
 *
 * @param string $slug
 * @param array<string, string> $vars
 * @return array{subject: string, body: string}|null
 */
function render_platform_email_template($slug, $vars = array())
{
    $CI =& get_instance();
    if (!$CI->db->table_exists('platform_email_template')) {
        return null;
    }

    $CI->load->model('Platform_communication_model');
    $template = $CI->Platform_communication_model->get_email_template($slug);
    if (!$template || empty($template['is_active'])) {
        return null;
    }

    $subject = apply_platform_email_template_vars((string) $template['subject'], $vars);
    $body = apply_platform_email_template_vars((string) $template['body_html'], $vars);

    return array(
        'subject' => $subject,
        'body' => $body,
    );
}

/**
 * @param array $lead Row from trial_registration_request
 * @return array{subject: string, body: string}
 */
function build_trial_lead_outreach_email($lead)
{
    $first_name = isset($lead['first_name']) ? trim((string) $lead['first_name']) : '';
    $property_name = isset($lead['property_name']) ? (string) $lead['property_name'] : 'your property';
    $room_count = isset($lead['number_of_rooms']) ? (int) $lead['number_of_rooms'] : 0;

    $rendered = render_platform_email_template('trial_outreach', array(
        'first_name' => $first_name !== '' ? $first_name : 'there',
        'property_name' => $property_name,
        'room_count' => (string) $room_count,
        'lead_email' => isset($lead['email']) ? (string) $lead['email'] : '',
    ));
    if ($rendered) {
        return $rendered;
    }

    return array(
        'subject' => 'Welcome to Veurion — let\'s get ' . $property_name . ' set up',
        'body' => '<p>Hi ' . htmlspecialchars($first_name !== '' ? $first_name : 'there', ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Thanks for your interest in Veurion for <strong>'
            . htmlspecialchars($property_name, ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
            . '<p>Reply to this email and we\'ll help you start your free trial.</p>'
            . '<p>Best,<br>Veurion Team</p>',
    );
}

/**
 * @param array $company Row from Company_model::get_company()
 * @return array{subject: string, body: string}
 */
function build_win_back_email($company)
{
    $property_name = isset($company['name']) ? (string) $company['name'] : 'your property';
    $subscription_url = function_exists('base_url') ? base_url('account_settings/subscription') : '/account_settings/subscription';

    $rendered = render_platform_email_template('win_back', array(
        'property_name' => $property_name,
        'subscription_url' => $subscription_url,
    ));
    if ($rendered) {
        return $rendered;
    }

    return array(
        'subject' => 'We\'d love to have ' . $property_name . ' back on Veurion',
        'body' => '<p>Hello,</p>'
            . '<p>We noticed your Veurion subscription for <strong>'
            . htmlspecialchars($property_name, ENT_QUOTES, 'UTF-8') . '</strong> is no longer active.</p>'
            . '<p><a href="' . htmlspecialchars($subscription_url, ENT_QUOTES, 'UTF-8') . '">View subscription options</a></p>'
            . '<p>Thank you,<br>Veurion Support</p>',
    );
}

/**
 * Send a platform email using CI email library.
 *
 * @return array{success: bool, error?: string}
 */
function send_platform_email($to, $subject, $body_html)
{
    $recipient = trim((string) $to);
    if ($recipient === '') {
        return array('success' => false, 'error' => 'Recipient email is required.');
    }
    if (!minical_env('SMTP_USER', '')) {
        return array('success' => false, 'error' => 'Email is not configured (SMTP_USER missing in .env).');
    }

    $CI =& get_instance();
    $CI->load->library('email');
    $CI->load->helper('email');
    $from_email = minical_smtp_from_email();

    $CI->email->clear(true);
    $CI->email->from($from_email, 'Veurion');
    $CI->email->to($recipient);
    $CI->email->subject($subject);
    $CI->email->message($body_html);
    $CI->email->set_mailtype('html');

    if (!$CI->email->send()) {
        log_message('error', 'Platform email failed to ' . $recipient . ': '
            . $CI->email->print_debugger(array('headers')));

        return array('success' => false, 'error' => 'Email could not be sent.');
    }

    return array('success' => true);
}

/**
 * Active platform-wide tenant announcement, respecting dismiss cookie.
 *
 * @return array|null
 */
function get_active_platform_announcement()
{
    $CI =& get_instance();
    if (!$CI->db->table_exists('platform_announcement')) {
        return null;
    }

    $CI->load->model('Platform_communication_model');
    $announcement = $CI->Platform_communication_model->get_active_announcement();
    if (!$announcement) {
        return null;
    }

    $cookie_key = 'pa_ann_dismiss_' . (int) $announcement['id'];
    if (!empty($announcement['dismissible']) && isset($_COOKIE[$cookie_key]) && $_COOKIE[$cookie_key] === '1') {
        return null;
    }

    return $announcement;
}

/**
 * Post a platform ops notification to Slack and/or a generic webhook.
 *
 * @param string $event trial_signup|payment_received|debtor_overdue
 * @param array $payload
 * @return bool True if at least one destination accepted the message
 */
function platform_notify_event($event, $payload = array())
{
    $event = (string) $event;
    $enabled = platform_webhook_enabled_events();
    if (!in_array($event, $enabled, true)) {
        return false;
    }

    $labels = platform_webhook_event_labels();
    $title = isset($labels[$event]) ? $labels[$event] : $event;
    $lines = array($title);
    foreach ($payload as $key => $value) {
        if ($value === null || $value === '') {
            continue;
        }
        $lines[] = ucfirst(str_replace('_', ' ', (string) $key)) . ': ' . $value;
    }
    $text = implode("\n", $lines);

    $CI =& get_instance();
    if (!$CI->db->table_exists('platform_settings')) {
        return false;
    }

    $CI->load->model('Platform_settings_model');
    $slack_url = trim((string) $CI->Platform_settings_model->get('slack_webhook_url', ''));
    $webhook_url = trim((string) $CI->Platform_settings_model->get('webhook_url', ''));

    if ($slack_url === '' && $webhook_url === '') {
        return false;
    }

    $sent = false;
    if ($slack_url !== '') {
        $sent = platform_post_webhook($slack_url, array('text' => $text)) || $sent;
    }
    if ($webhook_url !== '') {
        $sent = platform_post_webhook($webhook_url, array(
            'event' => $event,
            'title' => $title,
            'payload' => $payload,
            'text' => $text,
            'timestamp' => gmdate('c'),
        )) || $sent;
    }

    return $sent;
}

/**
 * @param string $url
 * @param array $body
 * @return bool
 */
function platform_post_webhook($url, $body)
{
    $url = trim((string) $url);
    if ($url === '' || !preg_match('#^https?://#i', $url)) {
        return false;
    }

    $json = json_encode($body);
    if ($json === false) {
        return false;
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $code >= 200 && $code < 300;
    }

    $context = stream_context_create(array(
        'http' => array(
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $json,
            'timeout' => 10,
            'ignore_errors' => true,
        ),
    ));
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return false;
    }

    if (isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) {
        $code = (int) $m[1];

        return $code >= 200 && $code < 300;
    }

    return true;
}

/**
 * @param int $company_id
 * @param float $amount
 * @param string $reference
 * @param array|null $company
 */
function platform_notify_payment_received($company_id, $amount, $reference, $company = null)
{
    $CI =& get_instance();
    if (!$company) {
        $CI->load->model('Company_model');
        $company = $CI->Company_model->get_company((int) $company_id);
    }

    platform_notify_event('payment_received', array(
        'property' => $company && isset($company['name']) ? (string) $company['name'] : ('Company #' . (int) $company_id),
        'company_id' => (string) (int) $company_id,
        'amount' => saas_currency_symbol() . format_saas_price_amount($amount),
        'reference' => (string) $reference,
    ));
}

/**
 * @param array $lead
 */
function platform_notify_trial_signup($lead)
{
    platform_notify_event('trial_signup', array(
        'property' => isset($lead['property_name']) ? (string) $lead['property_name'] : '',
        'contact' => trim((isset($lead['first_name']) ? $lead['first_name'] : '') . ' ' . (isset($lead['last_name']) ? $lead['last_name'] : '')),
        'email' => isset($lead['email']) ? (string) $lead['email'] : '',
        'phone' => isset($lead['phone']) ? (string) $lead['phone'] : '',
        'rooms' => isset($lead['number_of_rooms']) ? (string) (int) $lead['number_of_rooms'] : '',
    ));
}

/**
 * @param array $company
 * @param array $context
 */
function platform_notify_debtor_overdue($company, $context = array())
{
    platform_notify_event('debtor_overdue', array(
        'property' => isset($company['name']) ? (string) $company['name'] : '',
        'company_id' => isset($company['company_id']) ? (string) (int) $company['company_id'] : '',
        'due_date' => isset($context['due_date']) ? (string) $context['due_date'] : '',
        'amount_due' => isset($context['amount_due']) ? (string) $context['amount_due'] : '',
        'owner_email' => isset($company['owner_email']) ? (string) $company['owner_email'] : '',
    ));
}

/**
 * Suggested filename for a platform database backup download.
 *
 * @return string
 */
function platform_admin_database_backup_filename()
{
    $db_name = trim((string) getenv('DATABASE_NAME'));
    if ($db_name === '') {
        $db_name = 'database';
    }
    $safe_name = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $db_name);
    $extension = function_exists('gzopen') ? '.sql.gz' : '.sql';

    return $safe_name . '-backup-' . gmdate('Y-m-d-His') . $extension;
}

/**
 * Escape a value for use in a SQL INSERT statement.
 *
 * @param CI_DB_mysqli_driver|null $db
 * @param mixed $value
 * @return string
 */
function platform_admin_sql_literal($db, $value)
{
    if ($value === null) {
        return 'NULL';
    }
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    if (is_int($value)) {
        return (string) $value;
    }
    if (is_float($value)) {
        return rtrim(rtrim(sprintf('%.17F', $value), '0'), '.');
    }
    if (is_string($value) && preg_match('/^-?\d+$/', $value)) {
        return $value;
    }

    return "'" . $db->escape_str((string) $value) . "'";
}

/**
 * Quote a SQL identifier (table/column) for MySQL.
 *
 * @param string $identifier
 * @return string
 */
function platform_admin_sql_identifier($identifier)
{
    return '`' . str_replace('`', '``', (string) $identifier) . '`';
}

/**
 * Stream a gzip-compressed SQL dump of the active database to the client.
 */
function platform_admin_stream_database_backup()
{
    if (function_exists('set_time_limit')) {
        @set_time_limit(0);
    }

    $CI =& get_instance();
    if (!isset($CI->db)) {
        $CI->load->database();
    }
    $db = $CI->db;

    $filename = platform_admin_database_backup_filename();
    $use_gzip = function_exists('gzopen');

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    if ($use_gzip) {
        header('Content-Type: application/gzip');
    } else {
        header('Content-Type: application/sql; charset=utf-8');
    }
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-zA-Z0-9._-]+/', '-', $filename) . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    $write = null;
    $gz = null;
    if ($use_gzip) {
        $gz = @gzopen('php://output', 'wb9');
        if ($gz) {
            $write = function ($chunk) use ($gz) {
                gzwrite($gz, $chunk);
            };
        }
    }
    if ($write === null) {
        $write = function ($chunk) {
            echo $chunk;
        };
    }

    $db_name = trim((string) getenv('DATABASE_NAME'));
    $write("-- Veurion platform database backup\n");
    $write('-- Generated: ' . gmdate('Y-m-d H:i:s') . " UTC\n");
    if ($db_name !== '') {
        $write('-- Database: ' . $db_name . "\n");
    }
    $write("\nSET NAMES utf8;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

    $tables = $db->query('SHOW TABLES');
    if (!$tables) {
        if ($gz) {
            gzclose($gz);
        }
        show_error('Could not read database tables.', 500);
    }

    foreach ($tables->result_array() as $table_row) {
        $table = array_values($table_row);
        if (empty($table[0])) {
            continue;
        }
        $table_name = (string) $table[0];
        $quoted_table = platform_admin_sql_identifier($table_name);

        $create_result = $db->query('SHOW CREATE TABLE ' . $quoted_table);
        if (!$create_result) {
            continue;
        }
        $create_row = $create_result->row_array();
        $create_sql = '';
        if (isset($create_row['Create Table'])) {
            $create_sql = $create_row['Create Table'];
        } elseif (isset($create_row['Create View'])) {
            $create_sql = $create_row['Create View'];
        }
        if ($create_sql === '') {
            continue;
        }

        $write("\n-- Table structure for " . $table_name . "\n\n");
        $write('DROP TABLE IF EXISTS ' . $quoted_table . ";\n");
        $write($create_sql . ";\n\n");

        $offset = 0;
        $batch_size = 500;
        do {
            $data_result = $db->query('SELECT * FROM ' . $quoted_table . ' LIMIT ' . (int) $batch_size . ' OFFSET ' . (int) $offset);
            if (!$data_result) {
                break;
            }
            $rows = $data_result->result_array();
            if (empty($rows)) {
                break;
            }

            $columns = array_keys($rows[0]);
            $column_list = implode(', ', array_map('platform_admin_sql_identifier', $columns));

            foreach ($rows as $row) {
                $values = array();
                foreach ($columns as $column) {
                    $values[] = platform_admin_sql_literal($db, array_key_exists($column, $row) ? $row[$column] : null);
                }
                $write('INSERT INTO ' . $quoted_table . ' (' . $column_list . ') VALUES (' . implode(', ', $values) . ");\n");
            }

            $offset += $batch_size;
        } while (count($rows) === $batch_size);

        $write("\n");
    }

    $write("SET FOREIGN_KEY_CHECKS=1;\n");

    if ($gz) {
        gzclose($gz);
    }
    exit;
}

