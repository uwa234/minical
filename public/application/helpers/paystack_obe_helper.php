<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

define('OBE_PAYMENT_MODE_BOTH', 'both');
define('OBE_PAYMENT_MODE_PAYSTACK_ONLY', 'paystack_only');
define('OBE_PAYMENT_MODE_PAY_AT_HOTEL_ONLY', 'pay_at_hotel_only');

/**
 * Whether Paystack online payment UI should appear on the booking engine.
 */
function obe_paystack_checkout_enabled($gateway_settings, $credentials_filled)
{
    if (!$credentials_filled) {
        return false;
    }

    return isset($gateway_settings['selected_payment_gateway'])
        && $gateway_settings['selected_payment_gateway'] === 'paystack';
}

/**
 * Allowed guest payment methods for the booking engine (paystack / pay_at_hotel).
 *
 * @return array<string>|null null when Paystack checkout is not active
 */
function obe_allowed_payment_methods($gateway_settings, $credentials_filled)
{
    if (!obe_paystack_checkout_enabled($gateway_settings, $credentials_filled)) {
        return null;
    }

    $mode = OBE_PAYMENT_MODE_BOTH;
    if (isset($gateway_settings['obe_online_payment_mode']) && $gateway_settings['obe_online_payment_mode']) {
        $mode = $gateway_settings['obe_online_payment_mode'];
    }

    switch ($mode) {
        case OBE_PAYMENT_MODE_PAYSTACK_ONLY:
            return array('paystack');
        case OBE_PAYMENT_MODE_PAY_AT_HOTEL_ONLY:
            return array('pay_at_hotel');
        case OBE_PAYMENT_MODE_BOTH:
        default:
            return array('paystack', 'pay_at_hotel');
    }
}

function obe_default_payment_method(array $allowed_methods)
{
    return count($allowed_methods) > 0 ? $allowed_methods[0] : 'pay_at_hotel';
}

function obe_payment_method_is_allowed($method, $gateway_settings, $credentials_filled)
{
    $allowed = obe_allowed_payment_methods($gateway_settings, $credentials_filled);

    if ($allowed === null) {
        return false;
    }

    return in_array($method, $allowed, true);
}
