<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * RFC 6238 TOTP (Google Authenticator compatible).
 */
class Totp {

    const DEFAULT_PERIOD = 30;
    const DEFAULT_DIGITS = 6;
    const DEFAULT_ALGORITHM = 'sha1';

    public static function generate_secret($length = 16)
    {
        $bytes = random_bytes($length);
        return self::base32_encode($bytes);
    }

    public static function verify($secret, $code, $window = 1, $period = self::DEFAULT_PERIOD, $digits = self::DEFAULT_DIGITS)
    {
        $secret = self::normalize_secret($secret);
        $code = trim((string) $code);
        if ($secret === '' || !preg_match('/^\d{' . (int) $digits . '}$/', $code)) {
            return false;
        }

        $timestamp = time();
        for ($i = -((int) $window); $i <= (int) $window; $i++) {
            $counter = (int) floor(($timestamp + ($i * $period)) / $period);
            if (hash_equals(self::generate_code($secret, $counter, $digits), $code)) {
                return true;
            }
        }

        return false;
    }

    public static function generate_code($secret, $counter = null, $digits = self::DEFAULT_DIGITS)
    {
        $secret = self::normalize_secret($secret);
        if ($secret === '') {
            return '';
        }
        if ($counter === null) {
            $counter = (int) floor(time() / self::DEFAULT_PERIOD);
        }

        $binary_secret = self::base32_decode($secret);
        $counter_bytes = pack('N*', 0, $counter);
        $hash = hash_hmac(self::DEFAULT_ALGORITHM, $counter_bytes, $binary_secret, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $truncated = (
            ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF)
        );
        $otp = $truncated % (10 ** (int) $digits);

        return str_pad((string) $otp, (int) $digits, '0', STR_PAD_LEFT);
    }

    public static function provisioning_uri($secret, $account_name, $issuer = 'Veurion Platform Admin')
    {
        $secret = self::normalize_secret($secret);
        $label = rawurlencode($issuer . ':' . $account_name);
        $params = http_build_query(array(
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => strtoupper(self::DEFAULT_ALGORITHM),
            'digits' => self::DEFAULT_DIGITS,
            'period' => self::DEFAULT_PERIOD,
        ));

        return 'otpauth://totp/' . $label . '?' . $params;
    }

    private static function normalize_secret($secret)
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $secret));
    }

    private static function base32_encode($data)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';
        $chunks = str_split($binary, 5);
        foreach ($chunks as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $encoded .= $alphabet[bindec($chunk)];
        }

        return $encoded;
    }

    private static function base32_decode($data)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $data = self::normalize_secret($data);
        $binary = '';

        foreach (str_split($data) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) {
                continue;
            }
            $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($binary, 8) as $byte) {
            if (strlen($byte) === 8) {
                $bytes .= chr(bindec($byte));
            }
        }

        return $bytes;
    }
}
