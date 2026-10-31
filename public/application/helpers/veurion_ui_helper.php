<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Small view helpers for Veurion UI pages.
 */

if (!function_exists('vx_e')) {
    function vx_e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('vx_symbol')) {
    /** Currency symbol for display; ISO codes stored as the "symbol" become the real sign. */
    function vx_symbol($symbol)
    {
        $map = array('NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', 'GHS' => 'GH₵', 'KES' => 'KSh ', 'ZAR' => 'R');
        $symbol = trim((string) $symbol);
        return isset($map[strtoupper($symbol)]) ? $map[strtoupper($symbol)] : $symbol;
    }
}

if (!function_exists('vx_money')) {
    function vx_money($value, $symbol = '₦')
    {
        $value = (float) $value;
        return ($value < 0 ? '−' : '') . vx_e(vx_symbol($symbol)) . number_format(abs($value), 2);
    }
}

if (!function_exists('vx_signed_money')) {
    /** Money with an explicit + for positive values, e.g. variances. */
    function vx_signed_money($value, $symbol = '₦')
    {
        return ((float) $value > 0 ? '+' : '') . vx_money($value, $symbol);
    }
}

if (!function_exists('vx_tone')) {
    /** success | danger | warning for a variance (0 = balanced, < 0 = short, > 0 = over). */
    function vx_tone($value)
    {
        $value = round((float) $value, 2);
        return $value == 0 ? 'success' : ($value < 0 ? 'danger' : 'warning');
    }
}

if (!function_exists('vx_initials')) {
    function vx_initials($name)
    {
        $parts = preg_split('/[\s@._-]+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= function_exists('mb_substr') ? mb_strtoupper(mb_substr($part, 0, 1)) : strtoupper(substr($part, 0, 1));
        }
        return $initials !== '' ? $initials : '?';
    }
}

if (!function_exists('vx_user')) {
    function vx_user($name, $is_system = false)
    {
        return '<span class="vx-user"><span class="vx-avatar' . ($is_system ? ' is-system' : '') . '">'
            . ($is_system ? '<i class="fa fa-cog"></i>' : vx_e(vx_initials($name)))
            . '</span><span>' . vx_e($name) . '</span></span>';
    }
}

if (!function_exists('vx_page_assets')) {
    /** Template vars every Veurion UI page needs: sidebar on and the theme stylesheet. */
    function vx_page_assets($selected_menu)
    {
        return array(
            'menu_on' => true,
            'selected_menu' => $selected_menu,
            'css_files' => array(base_url() . auto_version('css/veurion/veurion-ui.css')),
        );
    }
}
