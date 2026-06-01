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
        $this->assertSame('Database backup downloaded', platform_audit_action_label('backup.download'));
        $this->assertSame('Unknown action', platform_audit_action_label('unknown.action'));
    }

    public function testPlatformAdminSqlLiteral()
    {
        $this->assertSame('NULL', platform_admin_sql_literal(null, null));
        $this->assertSame('1', platform_admin_sql_literal(null, true));
        $this->assertSame('42', platform_admin_sql_literal(null, 42));
    }

    public function testPlatformAdminDatabaseBackupFilename()
    {
        $previous = getenv('DATABASE_NAME');
        putenv('DATABASE_NAME=minical_test');
        $filename = platform_admin_database_backup_filename();
        if ($previous !== false) {
            putenv('DATABASE_NAME=' . $previous);
        } else {
            putenv('DATABASE_NAME');
        }

        $this->assertStringStartsWith('minical-test-backup-', $filename);
        $this->assertTrue(
            substr($filename, -7) === '.sql.gz' || substr($filename, -4) === '.sql',
            'Backup filename should end with .sql.gz or .sql'
        );
    }

    public function testVeurionSupportEmailsIncludesLegacy()
    {
        $emails = veurion_support_emails();

        $this->assertContains('support@veurion.com', $emails);
        $this->assertNotEmpty($emails);
    }
}
