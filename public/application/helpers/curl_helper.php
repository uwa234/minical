<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Apply TLS verification settings to a cURL handle.
 *
 * Verification is enabled by default. Set CURL_SSL_VERIFY=0 in .env only for
 * local development when calling HTTPS endpoints with self-signed certificates.
 */
function apply_curl_ssl_options($ch)
{
    $verify = getenv('CURL_SSL_VERIFY');

    if ($verify === '0' || $verify === 'false') {
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        return;
    }

    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
}

/**
 * Optional cron auth header when CRON_AUTH_SECRET is configured.
 */
function cron_auth_curl_headers()
{
    $secret = getenv('CRON_AUTH_SECRET');

    if (!$secret) {
        return array();
    }

    return array('X-Cron-Auth: ' . $secret);
}
