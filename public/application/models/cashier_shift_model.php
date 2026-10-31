<?php

class Cashier_shift_model extends CI_Model {

    function __construct()
    {
        parent::__construct();
    }

    function get_open_shift($company_id, $user_id)
    {
        return $this->db->where('company_id', (int) $company_id)
            ->where('user_id', (int) $user_id)
            ->where('status', 'open')
            ->order_by('opened_at', 'DESC')
            ->limit(1)
            ->get('cashier_shift')
            ->row_array();
    }

    function open_shift($company_id, $user_id, $opening_float)
    {
        $this->db->insert('cashier_shift', array(
            'company_id' => (int) $company_id,
            'user_id' => (int) $user_id,
            'status' => 'open',
            'opened_at' => gmdate('Y-m-d H:i:s'),
            'opening_float' => round((float) $opening_float, 2),
        ));
        return $this->db->insert_id();
    }

    function close_shift($shift_id, $data)
    {
        $this->db->where('id', (int) $shift_id)->where('status', 'open');
        $this->db->update('cashier_shift', $data);
        return $this->db->affected_rows() > 0;
    }

    function get_shift($company_id, $shift_id)
    {
        return $this->db->select('cs.*, up.first_name, up.last_name, u.email')
            ->from('cashier_shift as cs')
            ->join('users as u', 'u.id = cs.user_id', 'left')
            ->join('user_profiles as up', 'up.user_id = cs.user_id', 'left')
            ->where('cs.company_id', (int) $company_id)
            ->where('cs.id', (int) $shift_id)
            ->get()
            ->row_array();
    }

    /**
     * @param array $filters start (UTC datetime), end (UTC datetime), user_id
     */
    function get_shifts($company_id, $filters = array(), $limit = 200)
    {
        $this->db->select('cs.*, up.first_name, up.last_name, u.email')
            ->from('cashier_shift as cs')
            ->join('users as u', 'u.id = cs.user_id', 'left')
            ->join('user_profiles as up', 'up.user_id = cs.user_id', 'left')
            ->where('cs.company_id', (int) $company_id);
        if (!empty($filters['start'])) {
            $this->db->where('cs.opened_at >=', $filters['start']);
        }
        if (!empty($filters['end'])) {
            $this->db->where('cs.opened_at <', $filters['end']);
        }
        if (!empty($filters['user_id'])) {
            $this->db->where('cs.user_id', (int) $filters['user_id']);
        }
        return $this->db->order_by('cs.opened_at', 'DESC')->limit((int) $limit)->get()->result_array();
    }

    /**
     * Payments the cashier recorded between two UTC datetimes, totalled per payment type.
     */
    function get_payment_totals($company_id, $user_id, $from_utc, $to_utc)
    {
        $sql = "SELECT pt.payment_type_id, pt.payment_type, SUM(p.amount) AS total, COUNT(*) AS payment_count
                FROM payment AS p
                JOIN payment_type AS pt ON pt.payment_type_id = p.payment_type_id
                WHERE pt.company_id = ?
                  AND p.user_id = ?
                  AND p.is_deleted = 0
                  AND p.date_time >= ?
                  AND p.date_time <= ?
                GROUP BY pt.payment_type_id, pt.payment_type
                ORDER BY pt.payment_type";
        return $this->db->query($sql, array((int) $company_id, (int) $user_id, $from_utc, $to_utc))->result_array();
    }

    function get_payment_types($company_id)
    {
        return $this->db->select('payment_type_id, payment_type')
            ->where('company_id', (int) $company_id)
            ->where('is_deleted', 0)
            ->order_by('payment_type')
            ->get('payment_type')
            ->result_array();
    }

    function get_company_users($company_id)
    {
        return $this->db->select('up.user_id, up.first_name, up.last_name, u.email')
            ->from('user_permissions as perm')
            ->join('users as u', 'u.id = perm.user_id')
            ->join('user_profiles as up', 'up.user_id = perm.user_id', 'left')
            ->where('perm.company_id', (int) $company_id)
            ->group_by('perm.user_id')
            ->order_by('up.first_name')
            ->get()
            ->result_array();
    }
}
