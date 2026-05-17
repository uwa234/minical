<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Paystack REST API client for transaction initialize and verify.
 */
class Paystack
{
    const API_BASE = 'https://api.paystack.co';

    /** @var CI_Controller */
    private $ci;

    /** @var string */
    private $secret_key;

    /** @var string|null */
    private $last_error;

    public function __construct($params = array())
    {
        $this->ci =& get_instance();
        $this->ci->load->helper('curl');

        if (!empty($params['secret_key'])) {
            $this->secret_key = $params['secret_key'];
        }
    }

    public function setSecretKey($secret_key)
    {
        $this->secret_key = $secret_key;
    }

    public function getLastError()
    {
        return $this->last_error;
    }

    /**
     * @param string $email
     * @param int $amount_minor smallest currency unit (kobo, cents, etc.)
     * @param string $reference unique transaction reference
     * @param string $callback_url
     * @param array $metadata
     * @return array|null
     */
    public function initializeTransaction($email, $amount_minor, $reference, $callback_url, $metadata = array())
    {
        $payload = array(
            'email' => $email,
            'amount' => (int) $amount_minor,
            'reference' => $reference,
            'callback_url' => $callback_url,
            'currency' => isset($metadata['currency']) ? $metadata['currency'] : null,
            'metadata' => $metadata,
        );

        if (empty($payload['currency'])) {
            unset($payload['currency']);
        }

        return $this->request('POST', '/transaction/initialize', $payload);
    }

    /**
     * @param string $reference
     * @return array|null
     */
    public function verifyTransaction($reference)
    {
        return $this->request('GET', '/transaction/verify/' . rawurlencode($reference));
    }

    /**
     * Convert major currency amount to Paystack minor units.
     */
    public static function toMinorUnits($amount_major)
    {
        return (int) round((float) $amount_major * 100);
    }

    /**
     * Build a reference for online booking engine payments.
     */
    public static function buildObeReference($company_id, $booking_id)
    {
        return 'obe_' . (int) $company_id . '_' . (int) $booking_id . '_' . bin2hex(random_bytes(8));
    }

    /**
     * Parse company_id and booking_id from OBE reference.
     *
     * @return array{company_id:int,booking_id:int}|null
     */
    public static function parseObeReference($reference)
    {
        if (!preg_match('/^obe_(\d+)_(\d+)_/', $reference, $matches)) {
            return null;
        }

        return array(
            'company_id' => (int) $matches[1],
            'booking_id' => (int) $matches[2],
        );
    }

    private function request($method, $path, $body = null)
    {
        $this->last_error = null;

        if (empty($this->secret_key)) {
            $this->last_error = 'Paystack secret key is not configured.';

            return null;
        }

        $url = self::API_BASE . $path;
        $ch = curl_init($url);
        $headers = array(
            'Authorization: Bearer ' . $this->secret_key,
            'Content-Type: application/json',
        );

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        apply_curl_ssl_options($ch);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            $this->last_error = $curl_error;

            return null;
        }

        $decoded = json_decode($response, true);

        if (!is_array($decoded)) {
            $this->last_error = 'Invalid response from Paystack.';

            return null;
        }

        if (empty($decoded['status']) || $http_code >= 400) {
            $this->last_error = isset($decoded['message']) ? $decoded['message'] : 'Paystack request failed.';

            return null;
        }

        return isset($decoded['data']) ? $decoded['data'] : $decoded;
    }
}
