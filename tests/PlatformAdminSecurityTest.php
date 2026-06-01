<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../public/application/libraries/Totp.php';
require_once __DIR__ . '/../public/application/helpers/platform_admin_helper.php';

class PlatformAdminSecurityTest extends TestCase
{
    public function testTotpGenerateAndVerify()
    {
        $secret = Totp::generate_secret();
        $this->assertNotSame('', $secret);

        $code = Totp::generate_code($secret);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertTrue(Totp::verify($secret, $code));
        $this->assertFalse(Totp::verify($secret, '000000'));
    }

    public function testTotpProvisioningUri()
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $uri = Totp::provisioning_uri($secret, 'admin@example.com');

        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('admin%40example.com', $uri);
        $this->assertStringContainsString('secret=JBSWY3DPEHPK3PXP', $uri);
    }

    public function testPlatformAuditActionLabel()
    {
        $this->assertSame('Property restored', platform_audit_action_label('property.restore'));
        $this->assertSame('Unknown action', platform_audit_action_label('unknown.action'));
    }

    public function testVeurionSupportEmailsIncludesLegacy()
    {
        $emails = veurion_support_emails();

        $this->assertContains('support@veurion.com', $emails);
        $this->assertNotEmpty($emails);
    }
}
