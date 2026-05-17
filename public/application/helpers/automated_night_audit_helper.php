<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @return DateTime property-local "now"
 */
function automated_night_audit_local_now($time_zone)
{
    if (!$time_zone) {
        $time_zone = 'America/New_York';
    }

    return convert_to_local_time(new DateTime('now', new DateTimeZone('UTC')), $time_zone);
}

/**
 * True when local time is at or after today's scheduled run time, within $window_minutes.
 */
function automated_night_audit_is_run_window(DateTime $local_now, $run_time_hms, $window_minutes = 90)
{
    $run_time_hms = $run_time_hms ?: '04:00:00';
    $scheduled = DateTime::createFromFormat(
        'Y-m-d H:i:s',
        $local_now->format('Y-m-d') . ' ' . $run_time_hms,
        $local_now->getTimezone()
    );

    if (!$scheduled) {
        return false;
    }

    $window_end = clone $scheduled;
    $window_end->modify('+' . (int) $window_minutes . ' minutes');

    return $local_now >= $scheduled && $local_now <= $window_end;
}

/**
 * True when the property has not rolled the selling date for the current calendar day yet.
 */
function automated_night_audit_needs_roll(DateTime $local_now, $selling_date, $has_log_for_selling_date)
{
    if ($has_log_for_selling_date) {
        return false;
    }

    $calendar_date = $local_now->format('Y-m-d');

    return $selling_date < $calendar_date || $selling_date === $calendar_date;
}

/**
 * True when night audit was expected but selling date is still behind the calendar.
 */
function automated_night_audit_is_overdue(DateTime $local_now, $run_time_hms, $selling_date, $has_log_for_selling_date, $grace_hours = 3)
{
    if ($has_log_for_selling_date) {
        return false;
    }

    $calendar_date = $local_now->format('Y-m-d');

    if ($selling_date >= $calendar_date) {
        return false;
    }

    $run_time_hms = $run_time_hms ?: '04:00:00';
    $deadline = DateTime::createFromFormat(
        'Y-m-d H:i:s',
        $calendar_date . ' ' . $run_time_hms,
        $local_now->getTimezone()
    );

    if (!$deadline) {
        return false;
    }

    $deadline->modify('+' . (int) $grace_hours . ' hours');

    return $local_now > $deadline;
}
