<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../public/application/helpers/platform_admin_helper.php';

class PlatformAdminTenantHealthTest extends TestCase
{
    public function testHealthyTenantScore()
    {
        $score = compute_tenant_health_score(array());
        $this->assertSame(100, $score);
        $this->assertSame('healthy', tenant_health_risk_level($score));
    }

    public function testCriticalWhenMultipleFlags()
    {
        $score = compute_tenant_health_score(array(
            'no_bookings' => true,
            'trial_expired' => true,
            'night_audit_overdue' => true,
        ));
        $this->assertSame(5, $score);
        $this->assertSame('critical', tenant_health_risk_level($score));
    }

    public function testAtRiskThreshold()
    {
        $score = compute_tenant_health_score(array(
            'no_bookings' => true,
            'trial_expiring_soon' => true,
        ));
        $this->assertSame(50, $score);
        $this->assertSame('at_risk', tenant_health_risk_level($score));
    }

    public function testParseCronLogLastRun()
    {
        $log = sys_get_temp_dir() . '/tenant-health-cron-test.log';
        file_put_contents($log, "===== 2026-05-29T11:20:43+00:00 cron run_automated_night_audit =====\n");
        file_put_contents($log, "===== 2026-05-31T10:00:01+00:00 cron expire_platform_trials =====\n", FILE_APPEND);

        $this->assertSame('2026-05-31T10:00:01+00:00', parse_cron_log_last_run($log));
        unlink($log);
    }
}
