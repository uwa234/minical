<?php

use PHPUnit\Framework\TestCase;

class AutomatedNightAuditTest extends TestCase
{
    protected function setUp(): void
    {
        require_once APPPATH . 'helpers/timezone_helper.php';
        require_once APPPATH . 'helpers/automated_night_audit_helper.php';
    }

    public function testRunWindowOpensAtScheduledTime()
    {
        $tz = new DateTimeZone('America/New_York');
        $local = new DateTime('2026-05-17 04:30:00', $tz);

        $this->assertTrue(automated_night_audit_is_run_window($local, '04:00:00', 90));
    }

    public function testRunWindowClosedBeforeScheduledTime()
    {
        $tz = new DateTimeZone('America/New_York');
        $local = new DateTime('2026-05-17 03:30:00', $tz);

        $this->assertFalse(automated_night_audit_is_run_window($local, '04:00:00', 90));
    }

    public function testRunWindowClosedAfterWindowEnds()
    {
        $tz = new DateTimeZone('America/New_York');
        $local = new DateTime('2026-05-17 06:00:00', $tz);

        $this->assertFalse(automated_night_audit_is_run_window($local, '04:00:00', 90));
    }

    public function testNeedsRollWhenSellingDateBehindCalendar()
    {
        $tz = new DateTimeZone('UTC');
        $local = new DateTime('2026-05-18 05:00:00', $tz);

        $this->assertTrue(automated_night_audit_needs_roll($local, '2026-05-17', false));
    }

    public function testNeedsRollSkippedWhenAlreadyLogged()
    {
        $tz = new DateTimeZone('UTC');
        $local = new DateTime('2026-05-18 05:00:00', $tz);

        $this->assertFalse(automated_night_audit_needs_roll($local, '2026-05-17', true));
    }

    public function testOverdueWhenBehindCalendarPastGrace()
    {
        $tz = new DateTimeZone('UTC');
        $local = new DateTime('2026-05-18 10:00:00', $tz);

        $this->assertTrue(
            automated_night_audit_is_overdue($local, '04:00:00', '2026-05-16', false, 3)
        );
    }
}
