<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Cashier shifts: open with a float, close by counting each payment type, review variances.
 */
class Cashier_shift extends MY_Controller {

    function __construct()
    {
        parent::__construct();
        $this->load->model('Cashier_shift_model');
        $this->load->model('Company_model');
        $this->load->model('Permission_model');
        $this->load->helper('timezone');
        $this->load->helper('veurion_ui');
    }

    function index()
    {
        $data = $this->_base_data();
        $shift = $this->Cashier_shift_model->get_open_shift($this->company_id, $this->user_id);
        $data['shift'] = $shift;
        if ($shift) {
            $data['rows'] = $this->_expected_rows($shift, gmdate('Y-m-d H:i:s'));
            $data['opened_at_local'] = $this->_local_time($shift['opened_at']);
        }
        $data['main_content'] = 'reports/cashier_shift/index';
        $this->load->view('includes/bootstrapped_template', $data);
    }

    function open()
    {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('reports/cashier_shift');
        }
        if (!$this->Cashier_shift_model->get_open_shift($this->company_id, $this->user_id)) {
            $float = max(0, (float) $this->input->post('opening_float'));
            $this->Cashier_shift_model->open_shift($this->company_id, $this->user_id, $float);
        }
        redirect('reports/cashier_shift');
    }

    function close()
    {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('reports/cashier_shift');
        }
        $shift = $this->Cashier_shift_model->get_open_shift($this->company_id, $this->user_id);
        if (!$shift) {
            redirect('reports/cashier_shift');
        }

        $closed_at = gmdate('Y-m-d H:i:s');
        $rows = $this->_expected_rows($shift, $closed_at);
        $counted_input = $this->input->post('counted');
        $counted_input = is_array($counted_input) ? $counted_input : array();

        $counted = array();
        $expected_total = 0;
        $counted_total = 0;
        foreach ($rows as $row) {
            $key = (string) $row['payment_type_id'];
            $value = isset($counted_input[$key]) && $counted_input[$key] !== '' ? round((float) $counted_input[$key], 2) : 0;
            $counted[$key] = $value;
            $expected_total += $row['expected'];
            $counted_total += $value;
        }

        $this->Cashier_shift_model->close_shift($shift['id'], array(
            'status' => 'closed',
            'closed_at' => $closed_at,
            'closed_by' => $this->user_id,
            'expected_json' => json_encode($rows),
            'counted_json' => json_encode($counted),
            'expected_total' => round($expected_total, 2),
            'counted_total' => round($counted_total, 2),
            'variance' => round($counted_total - $expected_total, 2),
            'notes' => trim((string) $this->input->post('notes', TRUE)),
        ));

        redirect('reports/cashier_shift/view/' . (int) $shift['id']);
    }

    function view($shift_id = null)
    {
        $shift = $this->Cashier_shift_model->get_shift($this->company_id, $shift_id);
        if (!$shift || ((int) $shift['user_id'] !== (int) $this->user_id && !$this->_is_manager())) {
            show_404();
        }

        $data = $this->_base_data();
        $data['shift'] = $shift;
        if ($shift['status'] === 'closed') {
            $rows = json_decode($shift['expected_json'], true);
            $counted = json_decode($shift['counted_json'], true);
            $rows = is_array($rows) ? $rows : array();
            foreach ($rows as $i => $row) {
                $value = isset($counted[(string) $row['payment_type_id']]) ? (float) $counted[(string) $row['payment_type_id']] : 0;
                $rows[$i]['counted'] = $value;
                $rows[$i]['variance'] = round($value - $row['expected'], 2);
            }
        } else {
            $rows = $this->_expected_rows($shift, gmdate('Y-m-d H:i:s'));
        }
        $data['rows'] = $rows;
        $data['opened_at_local'] = $this->_local_time($shift['opened_at']);
        $data['closed_at_local'] = $shift['closed_at'] ? $this->_local_time($shift['closed_at']) : '';
        $data['main_content'] = 'reports/cashier_shift/view';
        $this->load->view('includes/bootstrapped_template', $data);
    }

    function history()
    {
        if (!$this->_is_manager()) {
            show_404();
        }

        $time_zone = $this->_time_zone();
        $start = $this->input->get('start') ?: date('Y-m-d', strtotime('-7 days'));
        $end = $this->input->get('end') ?: date('Y-m-d');
        $user_id = (int) $this->input->get('user_id');

        $filters = array('user_id' => $user_id);
        foreach (array('start' => $start, 'end' => $end) as $key => $value) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $value = date('Y-m-d');
            }
            $local = new DateTime($value . ' 00:00:00', new DateTimeZone($time_zone));
            if ($key === 'end') {
                $local->modify('+1 day');
            }
            $local->setTimezone(new DateTimeZone('UTC'));
            $filters[$key] = $local->format('Y-m-d H:i:s');
            if ($key === 'start') { $start = $value; } else { $end = $value; }
        }

        $shifts = $this->Cashier_shift_model->get_shifts($this->company_id, $filters);
        foreach ($shifts as $i => $shift) {
            $shifts[$i]['opened_at_local'] = $this->_local_time($shift['opened_at']);
            $shifts[$i]['closed_at_local'] = $shift['closed_at'] ? $this->_local_time($shift['closed_at']) : '';
        }

        $data = $this->_base_data();
        $data['shifts'] = $shifts;
        $data['users'] = $this->Cashier_shift_model->get_company_users($this->company_id);
        $data['start'] = $start;
        $data['end'] = $end;
        $data['user_id'] = $user_id;
        $data['main_content'] = 'reports/cashier_shift/history';
        $this->load->view('includes/bootstrapped_template', $data);
    }

    /**
     * One row per payment type: payments the cashier took during the shift, plus
     * the opening float on cash types, is what should be in hand at close.
     */
    private function _expected_rows($shift, $to_utc)
    {
        $totals = array();
        foreach ($this->Cashier_shift_model->get_payment_totals($this->company_id, $shift['user_id'], $shift['opened_at'], $to_utc) as $total) {
            $totals[(string) $total['payment_type_id']] = $total;
        }

        $rows = array();
        foreach ($this->Cashier_shift_model->get_payment_types($this->company_id) as $type) {
            $rows[(string) $type['payment_type_id']] = $type;
        }
        foreach ($totals as $id => $total) {
            if (!isset($rows[$id])) {
                $rows[$id] = array('payment_type_id' => $total['payment_type_id'], 'payment_type' => $total['payment_type']);
            }
        }

        $float_assigned = false;
        $result = array();
        foreach ($rows as $id => $row) {
            $payments = isset($totals[$id]) ? round((float) $totals[$id]['total'], 2) : 0;
            $is_cash = stripos($row['payment_type'], 'cash') !== false;
            $float = ($is_cash && !$float_assigned) ? (float) $shift['opening_float'] : 0;
            if ($float > 0) {
                $float_assigned = true;
            }
            $result[] = array(
                'payment_type_id' => (int) $row['payment_type_id'],
                'payment_type' => $row['payment_type'],
                'payment_count' => isset($totals[$id]) ? (int) $totals[$id]['payment_count'] : 0,
                'payments' => $payments,
                'opening_float' => round($float, 2),
                'expected' => round($payments + $float, 2),
            );
        }
        return $result;
    }

    private function _is_manager()
    {
        $permissions = $this->Permission_model->_get_user_permissions($this->user_id, $this->company_id);
        return count(array_intersect(array('is_owner', 'is_admin', 'can_view_reports'), $permissions)) > 0;
    }

    private function _time_zone()
    {
        $time_zone = $this->Company_model->get_time_zone($this->company_id);
        return $time_zone ? $time_zone : 'Africa/Lagos';
    }

    private function _local_time($utc)
    {
        $time = convert_to_local_time(new DateTime($utc, new DateTimeZone('UTC')), $this->_time_zone());
        return $time->format('d M Y, H:i');
    }

    private function _base_data()
    {
        return array_merge(vx_page_assets('reports'), array(
            'currency_symbol' => $this->session->userdata('currency_symbol') ? $this->session->userdata('currency_symbol') : '₦',
            'is_manager' => $this->_is_manager(),
        ));
    }
}
