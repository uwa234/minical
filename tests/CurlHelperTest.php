<?php

use PHPUnit\Framework\TestCase;

class CurlHelperTest extends TestCase
{
    protected function setUp(): void
    {
        require_once APPPATH . 'helpers/curl_helper.php';
    }

    public function testCronAuthHeaderIsEmptyWithoutSecret()
    {
        putenv('CRON_AUTH_SECRET');
        $this->assertSame(array(), cron_auth_curl_headers());
    }

    public function testCronAuthHeaderIncludesSecret()
    {
        putenv('CRON_AUTH_SECRET=test-secret');
        $headers = cron_auth_curl_headers();

        $this->assertContains('X-Cron-Auth: test-secret', $headers);
        putenv('CRON_AUTH_SECRET');
    }
}
