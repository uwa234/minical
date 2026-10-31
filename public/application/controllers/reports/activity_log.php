<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Owner activity log: who deleted, refunded, voided or changed what, and when.
 */
class Activity_log extends MY_Controller {

    private $invoice_actions = array(
        1 => 'Charge added', 2 => 'Charge edited', 3 => 'Charge deleted', 4 => 'Payment added',
        5 => 'Payment refunded', 6 => 'Payment deleted', 7 => 'Payment partly refunded', 8 => 'Payment authorised',
        9 => 'Payment captured', 10 => 'Folio added', 11 => 'Folio updated', 12 => 'Folio deleted', 13 => 'Payment voided',
    );

    private $booking_fields = array(
        5 => 'Status', 6 => 'Charge type', 7 => 'Adults', 8 => 'Children', 9 => 'Rate', 10 => 'Rate plan mode',
        11 => 'Rate plan', 12 => 'Booking notes', 14 => 'Deleted', 15 => 'Check-in date', 16 => 'Check-out date',
        17 => 'Room', 18 => 'Paying customer', 19 => 'Staying guests', 20 => 'Pay period', 23 => 'Booking source',
        24 => 'Extra rate created', 25 => 'Extra rate deleted',
    );

    private $states = array('0' => 'reservation', '1' => 'checked-in', '2' => 'checked-out', '3' => 'out-of-order', '4' => 'cancelled', '5' => 'no-show', '7' => 'unconfirmed');

    function __construct()
    {
        parent::__construct();
        $this->load->model('Activity_log_model');
        $this->load->model('Company_model');
        $this->load->model('Permission_model');
        $this->load->helper('timezone');
    }

    function index()
    {
        $permissions = $this->Permission_model->_get_user_permissions($this->user_id, $this->company_id);
        if (!array_intersect(array('is_owner', 'is_admin', 'can_view_reports'), $permissions)) {
            show_404();
        }

        $time_zone = $this->Company_model->get_time_zone($this->company_id) ?: 'Africa/Lagos';
        $start = $this->_valid_date($this->input->get('start'), date('Y-m-d', strtotime('-7 days')));
        $end = $this->_valid_date($this->input->get('end'), date('Y-m-d'));
        $category = in_array($this->input->get('category'), array('risk', 'money', 'bookings', 'all'), true) ? $this->input->get('category') : 'risk';
        $user_id = (int) $this->input->get('user_id');

        $from = new DateTime($start . ' 00:00:00', new DateTimeZone($time_zone));
        $to = new DateTime($end . ' 00:00:00', new DateTimeZone($time_zone));
        $to->modify('+1 day');
        $from->setTimezone(new DateTimeZone('UTC'));
        $to->setTimezone(new DateTimeZone('UTC'));

        $limit = 1000;
        $events = $this->Activity_log_model->get_events($this->company_id, $from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s'), $user_id, $category, $limit);
        $events = $this->_describe($events, $time_zone);

        if ($this->input->get('format') === 'csv') {
            return $this->_csv($events, $start, $end);
        }

        $this->load->model('Cashier_shift_model');
        $data = array(
            'events' => $events,
            'summary' => $this->_summary($events),
            'truncated' => count($events) >= $limit,
            'users' => $this->Cashier_shift_model->get_company_users($this->company_id),
            'start' => $start,
            'end' => $end,
            'category' => $category,
            'user_id' => $user_id,
            'currency_symbol' => $this->session->userdata('currency_symbol') ?: '₦',
            'selected_menu' => 'reports',
            'main_content' => 'reports/activity_log/index',
        );
        $this->load->view('includes/bootstrapped_template', $data);
    }

    private function _describe($events, $time_zone)
    {
        $user_ids = array();
        $room_ids = array();
        $rate_plan_ids = array();
        foreach ($events as $event) {
            $user_ids[] = $event['user_id'];
            if ($event['source'] === 'booking' && (int) $event['type'] === 17) { $room_ids[] = $event['log']; }
            if ($event['source'] === 'booking' && (int) $event['type'] === 11) { $rate_plan_ids[] = $event['log']; }
        }
        $users = $this->Activity_log_model->get_user_names($user_ids);
        $rooms = $this->Activity_log_model->get_room_names($this->company_id, $room_ids);
        $rate_plans = $this->Activity_log_model->get_rate_plan_names($rate_plan_ids);
        $symbol = $this->session->userdata('currency_symbol') ?: '₦';

        foreach ($events as $i => $event) {
            $type = (int) $event['type'];
            $log = (string) $event['log'];
            $local = convert_to_local_time(new DateTime($event['date_time'], new DateTimeZone('UTC')), $time_zone);
            $events[$i]['local_time'] = $local->format('d M Y, H:i');
            $events[$i]['user_name'] = (int) $event['user_id'] === 0 ? 'System / POS' : (isset($users[(int) $event['user_id']]) ? $users[(int) $event['user_id']] : 'User #' . (int) $event['user_id']);

            if ($event['source'] === 'invoice') {
                $action = isset($this->invoice_actions[$type]) ? $this->invoice_actions[$type] : 'Invoice change';
                if (strpos($log, 'API charge reversed') === 0) { $action = 'POS charge reversed'; }
                if (strpos($log, 'API charge voided') === 0) { $action = 'POS charge voided'; }
                $details = trim((string) $event['item_description']);
                $generic = array('Charge Deleted', 'Payment Deleted', 'Charge Updated', 'Payment voided', 'Payment Captured');
                if (preg_match('/^API charge [a-z]+: (.*)$/', $log, $match)) {
                    $details = $match[1];
                } elseif ($log !== '' && !in_array($log, $generic, true) && strpos($log, $details) === false) {
                    $details = trim($details . ($details !== '' ? ' — ' : '') . $log);
                }
                $events[$i]['action'] = $action;
                $events[$i]['details'] = $details;
                $events[$i]['is_risk'] = in_array($type, array(2, 3, 5, 6, 7, 12, 13), true) || $action === 'POS charge reversed';
                continue;
            }

            $field = isset($this->booking_fields[$type]) ? $this->booking_fields[$type] : null;
            $value = $log;
            if ($type === 5) {
                $value = isset($this->states[$log]) ? $this->states[$log] : $log;
            } elseif ($type === 9) {
                $value = is_numeric($log) ? $symbol . number_format((float) $log, 2) : $log;
            } elseif ($type === 17) {
                $value = isset($rooms[(int) $log]) ? $rooms[(int) $log] : 'Not assigned';
            } elseif ($type === 11) {
                $value = isset($rate_plans[(int) $log]) ? $rate_plans[(int) $log] : $log;
            } elseif (in_array($type, array(15, 16), true)) {
                $value = $log ? date('d M Y', strtotime($log)) : $log;
            }

            if ($type === 14) {
                $events[$i]['action'] = $log === '1' ? 'Booking deleted' : 'Booking restored';
                $events[$i]['details'] = '';
            } elseif ($field) {
                $events[$i]['action'] = $field . ' changed';
                $events[$i]['details'] = $value;
            } else {
                $events[$i]['action'] = 'Booking note';
                $events[$i]['details'] = $log;
            }
            $events[$i]['is_risk'] = in_array($type, array(9, 11, 14, 18), true) || ($type === 5 && in_array($log, array('4', '5'), true));
        }
        return $events;
    }

    private function _summary($events)
    {
        $summary = array();
        foreach ($events as $event) {
            if (!$event['is_risk']) {
                continue;
            }
            $name = $event['user_name'];
            if (!isset($summary[$name])) {
                $summary[$name] = array('events' => 0, 'deleted_charges' => 0, 'deleted_payments' => 0, 'refunds' => 0, 'booking_changes' => 0);
            }
            $summary[$name]['events']++;
            $amount = abs((float) $event['amount']);
            if ($event['source'] === 'invoice') {
                $type = (int) $event['type'];
                if ($type === 3 || $event['action'] === 'POS charge reversed') {
                    $summary[$name]['deleted_charges'] += $amount;
                } elseif (in_array($type, array(6, 13), true)) {
                    $summary[$name]['deleted_payments'] += $amount;
                } elseif (in_array($type, array(5, 7), true)) {
                    $summary[$name]['refunds'] += $amount;
                }
            } else {
                $summary[$name]['booking_changes']++;
            }
        }
        uasort($summary, function ($a, $b) { return $b['events'] - $a['events']; });
        return $summary;
    }

    private function _csv($events, $start, $end)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="activity-log-' . $start . '-to-' . $end . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, array('Time', 'User', 'Booking', 'Action', 'Details', 'Amount'));
        foreach ($events as $event) {
            $row = array($event['local_time'], $event['user_name'], $event['booking_id'], $event['action'], $event['details'], $event['amount'] !== null ? $event['amount'] : '');
            // Spreadsheet formula injection guard
            foreach ($row as $k => $v) {
                if (is_string($v) && $v !== '' && in_array($v[0], array('=', '+', '-', '@'), true) && !is_numeric($v)) {
                    $row[$k] = "'" . $v;
                }
            }
            fputcsv($out, $row);
        }
        fclose($out);
    }

    private function _valid_date($value, $default)
    {
        return (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) ? $value : $default;
    }
}
