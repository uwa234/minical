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
    if (!$company || !isset($company['subscription_state']) || $company['subscription_state'] !== 'trialing') {
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
    );
}
