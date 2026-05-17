<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../public/application/helpers/ical_helper.php';

class IcalHelperTest extends TestCase
{
    public function test_parse_single_date_event()
    {
        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nBEGIN:VEVENT\r\n"
            . "UID:test-1@booking.com\r\n"
            . "SUMMARY:Reserved\r\n"
            . "DTSTART;VALUE=DATE:20250601\r\n"
            . "DTEND;VALUE=DATE:20250603\r\n"
            . "END:VEVENT\r\nEND:VCALENDAR\r\n";

        $events = ical_parse_events($ics);

        $this->assertCount(1, $events);
        $this->assertSame('2025-06-01', $events[0]['check_in_date']);
        $this->assertSame('2025-06-03', $events[0]['check_out_date']);
        $this->assertSame('test-1@booking.com', $events[0]['uid']);
    }

    public function test_build_calendar_contains_vevent()
    {
        $ics = ical_build_calendar(array(
            array(
                'check_in_date' => '2025-06-01',
                'check_out_date' => '2025-06-02',
                'summary' => 'Not available',
                'uid' => 'test-uid',
            ),
        ));

        $this->assertStringContainsString('BEGIN:VCALENDAR', $ics);
        $this->assertStringContainsString('BEGIN:VEVENT', $ics);
        $this->assertStringContainsString('DTSTART;VALUE=DATE:20250601', $ics);
    }
}
