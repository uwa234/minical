<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Minimal iCalendar (ICS) parse and generate helpers for channel availability sync.
 */
function ical_unfold_lines($ics)
{
    $ics = str_replace(array("\r\n", "\r"), "\n", $ics);
    $lines = explode("\n", $ics);
    $out = array();

    foreach ($lines as $line) {
        if ($line === '') {
            continue;
        }
        if (!empty($out) && (isset($line[0]) && ($line[0] === ' ' || $line[0] === "\t"))) {
            $out[count($out) - 1] .= substr($line, 1);
        } else {
            $out[] = $line;
        }
    }

    return $out;
}

function ical_parse_datetime_value($value, $params = '')
{
    $value = trim($value);
    $is_date_only = (stripos($params, 'VALUE=DATE') !== false) || (strlen($value) === 8);

    if ($is_date_only) {
        $dt = DateTime::createFromFormat('Ymd', substr(preg_replace('/[^0-9]/', '', $value), 0, 8));

        return $dt ? $dt->format('Y-m-d') : null;
    }

    $digits = preg_replace('/[^0-9]/', '', $value);
    if (strlen($digits) >= 15) {
        $dt = DateTime::createFromFormat('YmdHis', substr($digits, 0, 14));

        return $dt ? $dt->format('Y-m-d') : null;
    }

    if (strlen($digits) >= 8) {
        $dt = DateTime::createFromFormat('Ymd', substr($digits, 0, 8));

        return $dt ? $dt->format('Y-m-d') : null;
    }

    return null;
}

/**
 * @return array[] List of events: uid, summary, check_in_date, check_out_date (checkout exclusive)
 */
function ical_parse_events($ics)
{
    $events = array();
    $lines = ical_unfold_lines($ics);
    $in_event = false;
    $current = array();

    foreach ($lines as $line) {
        if ($line === 'BEGIN:VEVENT') {
            $in_event = true;
            $current = array();
            continue;
        }
        if ($line === 'END:VEVENT') {
            $in_event = false;
            if (!empty($current['check_in_date']) && !empty($current['check_out_date'])) {
                if ($current['check_out_date'] <= $current['check_in_date']) {
                    $end = DateTime::createFromFormat('Y-m-d', $current['check_in_date']);
                    if ($end) {
                        $end->modify('+1 day');
                        $current['check_out_date'] = $end->format('Y-m-d');
                    }
                }
                $events[] = $current;
            }
            continue;
        }
        if (!$in_event) {
            continue;
        }

        $colon = strpos($line, ':');
        if ($colon === false) {
            continue;
        }
        $key_part = substr($line, 0, $colon);
        $value = substr($line, $colon + 1);
        $semi = strpos($key_part, ';');
        $key = strtoupper($semi !== false ? substr($key_part, 0, $semi) : $key_part);
        $params = $semi !== false ? substr($key_part, $semi) : '';

        switch ($key) {
            case 'UID':
                $current['uid'] = trim($value);
                break;
            case 'SUMMARY':
                $current['summary'] = trim(str_replace('\\,', ',', $value));
                break;
            case 'DTSTART':
                $current['check_in_date'] = ical_parse_datetime_value($value, $params);
                break;
            case 'DTEND':
                $current['check_out_date'] = ical_parse_datetime_value($value, $params);
                break;
        }
    }

    return $events;
}

/**
 * @param array[] $busy_periods Each: check_in_date, check_out_date, summary (optional), uid (optional)
 */
function ical_build_calendar($busy_periods, $calendar_name = 'miniCal availability')
{
    $lines = array(
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//miniCal//Channels//EN',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
        'X-WR-CALNAME:' . ical_escape_text($calendar_name),
    );

    $stamp = gmdate('Ymd\THis\Z');
    foreach ($busy_periods as $period) {
        $uid = isset($period['uid']) ? $period['uid'] : ('minical-' . md5(json_encode($period)));
        $start = str_replace('-', '', $period['check_in_date']);
        $end = str_replace('-', '', $period['check_out_date']);
        $summary = isset($period['summary']) ? $period['summary'] : 'Not available';

        $lines[] = 'BEGIN:VEVENT';
        $lines[] = 'UID:' . $uid;
        $lines[] = 'DTSTAMP:' . $stamp;
        $lines[] = 'DTSTART;VALUE=DATE:' . $start;
        $lines[] = 'DTEND;VALUE=DATE:' . $end;
        $lines[] = 'SUMMARY:' . ical_escape_text($summary);
        $lines[] = 'TRANSP:OPAQUE';
        $lines[] = 'END:VEVENT';
    }

    $lines[] = 'END:VCALENDAR';

    return implode("\r\n", $lines) . "\r\n";
}

function ical_escape_text($text)
{
    $text = str_replace('\\', '\\\\', $text);
    $text = str_replace(',', '\\,', $text);
    $text = str_replace(';', '\\;', $text);
    $text = str_replace("\n", '\\n', $text);

    return $text;
}

function ical_fetch_url($url)
{
    $url = trim($url);
    if ($url === '' || !preg_match('#^https?://#i', $url)) {
        return array('success' => false, 'body' => '', 'error' => 'Invalid calendar URL.');
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'miniCal-Channel-Sync/1.0');
    apply_curl_ssl_options($ch);

    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno) {
        return array('success' => false, 'body' => '', 'error' => $error);
    }
    if ($code >= 400) {
        return array('success' => false, 'body' => '', 'error' => 'Calendar URL returned HTTP ' . $code);
    }

    return array('success' => true, 'body' => $body, 'error' => null);
}
