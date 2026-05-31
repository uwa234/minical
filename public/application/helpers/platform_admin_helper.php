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
 * Days after paid plan expiration_date before account lockout (grace to renew).
 */
function saas_paid_plan_grace_days()
{
    return 7;
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
    if ($reason === 'plan_past_grace') {
        return array(
            'short' => 'Your subscription expired and the grace period has ended. Renew to restore access.',
            'owner' => 'Your subscription expired and the grace period has ended. Renew your plan below to restore access.',
            'employee' => 'Your subscription expired and the grace period has ended. Please ask your account owner to renew the subscription.',
        );
    }

    return array(
        'short' => 'Your trial has ended. Choose a plan to restore access.',
        'owner' => 'Your trial has ended. Choose a plan below to restore access.',
        'employee' => 'Your trial has ended. Please ask your account owner to renew the subscription.',
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
        'account_settings' => array('subscription', 'paystack_checkout', 'paystack_callback', 'paystack_billing_health'),
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

    if (!empty($company['meta_data'])) {
        $meta = json_decode($company['meta_data'], true);
        if (is_array($meta) && !empty($meta['tier_id'])) {
            $tier = $CI->Platform_settings_model->get_pricing_tier((int) $meta['tier_id']);
            if ($tier) {
                return $tier;
            }
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

    if (!empty($company['meta_data'])) {
        $meta = json_decode($company['meta_data'], true);
        if (is_array($meta)) {
            if (!empty($meta['tier_id'])) {
                $CI =& get_instance();
                if (!isset($CI->Platform_settings_model)) {
                    $CI->load->model('Platform_settings_model');
                }
                $tier = $CI->Platform_settings_model->get_pricing_tier((int) $meta['tier_id']);
                if ($tier && !empty($tier['name'])) {
                    return (string) $tier['name'];
                }
            }
            if (!empty($meta['tier_name'])) {
                return (string) $meta['tier_name'];
            }
        }
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
    return ((int) $prior_payment_count) > 0 ? 'Extension' : 'New subscription';
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
    $meta = array();
    if (!empty($company['meta_data'])) {
        $decoded = json_decode($company['meta_data'], true);
        if (is_array($decoded)) {
            $meta = $decoded;
        }
    }

    $selected_tier_id = isset($meta['tier_id']) ? (int) $meta['tier_id'] : 0;
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
