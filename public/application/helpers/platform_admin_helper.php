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
    if ($email !== null && $email !== '') {
        return is_veurion_support_email($email);
    }
    $CI =& get_instance();
    if (!isset($CI->User_model)) {
        $CI->load->model('User_model');
    }
    $user = $CI->User_model->get_user_by_id($user_id);
    return $user && isset($user['email']) && is_veurion_support_email($user['email']);
}

function is_hosted_prod_service()
{
    $flag = getenv('IS_HOSTED_PROD_SERVICE');
    return $flag === '1' || $flag === 'true' || $flag === true;
}

/**
 * Whether the subscription state triggers post-trial account lockout.
 */
function is_trial_lockout_subscription_state($subscription_state)
{
    return trim((string) $subscription_state) === 'trial_ended';
}

/**
 * True when the signed-in company must be limited to billing pages only.
 */
function company_requires_trial_lockout($company, $user_id = null, $user_email = null)
{
    if (!is_hosted_prod_service()) {
        return false;
    }
    if (!$company || !isset($company['subscription_state'])) {
        return false;
    }
    if (!is_trial_lockout_subscription_state($company['subscription_state'])) {
        return false;
    }
    if ($user_id !== null && is_platform_admin($user_id, $user_email)) {
        return false;
    }
    return true;
}

/**
 * Routes reachable while a company is in post-trial lockout.
 */
function is_trial_lockout_allowed_route($controller_name, $function_name)
{
    $controller = strtolower(trim((string) $controller_name));
    $function = strtolower(trim((string) $function_name));

    $allowed = array(
        'account_settings' => array('subscription', 'paystack_checkout', 'paystack_callback'),
        'auth' => array('logout', 'get_subscription_state_extended', 'check_for_employee_auto_logout'),
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
    return base_url('account_settings/subscription');
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
 * Human-readable legacy subscription level (company_subscription.subscription_level).
 */
/**
 * Default SaaS billing currency (Paystack / Nigeria).
 */
function saas_default_currency()
{
    return 'NGN';
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

    if (!empty($company['meta_data'])) {
        $meta = json_decode($company['meta_data'], true);
        if (is_array($meta) && !empty($meta['tier_id'])) {
            $CI =& get_instance();
            if (!isset($CI->Platform_settings_model)) {
                $CI->load->model('Platform_settings_model');
            }
            $tier = $CI->Platform_settings_model->get_pricing_tier((int) $meta['tier_id']);
            if ($tier) {
                return resolve_subscription_level_for_tier($tier);
            }
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
    return array(
        'expiration_date' => $expiration_date,
        'days_left' => $days_left,
        'is_within_warning' => $days_left >= 0 && $days_left <= 7,
    );
}
