<?php

/**
 * Corporate (city ledger) accounts are customers billed on account. Their balance is
 * the sum of posted balances (excluding future nights) of bookings they pay for.
 */
class Corporate_account_model extends CI_Model {

    function __construct()
    {
        parent::__construct();
    }

    function get_accounts($company_id, $selling_date)
    {
        $sql = "SELECT c.customer_id, c.customer_name, c.email, c.phone, c.credit_limit, c.payment_terms_days,
                       IFNULL(SUM(ob.balance), 0) AS balance,
                       IFNULL(SUM(IF(c.payment_terms_days IS NOT NULL AND DATEDIFF(?, ob.check_out_date) > c.payment_terms_days, ob.balance, 0)), 0) AS overdue,
                       COUNT(ob.booking_id) AS open_bookings
                FROM customer AS c
                LEFT JOIN (" . $this->_open_bookings_sql() . ") AS ob ON ob.booking_customer_id = c.customer_id
                WHERE c.company_id = ? AND c.is_deleted = 0 AND c.is_corporate_account = 1
                GROUP BY c.customer_id
                ORDER BY c.customer_name";
        return $this->db->query($sql, array($selling_date, (int) $company_id, (int) $company_id))->result_array();
    }

    function get_account($company_id, $customer_id)
    {
        return $this->db->where('company_id', (int) $company_id)
            ->where('customer_id', (int) $customer_id)
            ->where('is_deleted', 0)
            ->get('customer')
            ->row_array();
    }

    /**
     * Bookings billed to the account that still have a non-zero posted balance.
     */
    function get_open_bookings($company_id, $customer_id)
    {
        $sql = "SELECT ob.*, r.room_name
                FROM (" . $this->_open_bookings_sql() . ") AS ob
                LEFT JOIN room AS r ON r.room_id = ob.room_id
                WHERE ob.booking_customer_id = ?
                ORDER BY ob.check_out_date, ob.booking_id";
        return $this->db->query($sql, array((int) $company_id, (int) $customer_id))->result_array();
    }

    function get_account_balance($company_id, $customer_id)
    {
        $sql = "SELECT IFNULL(SUM(ob.balance), 0) AS balance
                FROM (" . $this->_open_bookings_sql() . ") AS ob
                WHERE ob.booking_customer_id = ?";
        $row = $this->db->query($sql, array((int) $company_id, (int) $customer_id))->row_array();
        return $row ? round((float) $row['balance'], 2) : 0;
    }

    function get_recent_payments($company_id, $customer_id, $limit = 20)
    {
        $sql = "SELECT p.payment_id, p.booking_id, p.amount, p.selling_date, p.description, pt.payment_type
                FROM payment AS p
                JOIN booking AS b ON b.booking_id = p.booking_id
                LEFT JOIN payment_type AS pt ON pt.payment_type_id = p.payment_type_id
                WHERE b.company_id = ? AND b.booking_customer_id = ? AND b.is_deleted = 0 AND p.is_deleted = 0
                ORDER BY p.selling_date DESC, p.payment_id DESC
                LIMIT " . (int) $limit;
        return $this->db->query($sql, array((int) $company_id, (int) $customer_id))->result_array();
    }

    function get_customers_for_select($company_id)
    {
        return $this->db->select('customer_id, customer_name, email, is_corporate_account')
            ->where('company_id', (int) $company_id)
            ->where('is_deleted', 0)
            ->where('customer_name !=', '')
            ->order_by('customer_name')
            ->limit(2000)
            ->get('customer')
            ->result_array();
    }

    function update_account($company_id, $customer_id, $data)
    {
        $this->db->where('company_id', (int) $company_id)->where('customer_id', (int) $customer_id);
        $this->db->update('customer', $data);
        return $this->db->affected_rows() >= 0;
    }

    function create_customer($company_id, $data)
    {
        $data['company_id'] = (int) $company_id;
        $data['is_deleted'] = 0;
        $this->db->insert('customer', $data);
        return $this->db->insert_id();
    }

    private function _open_bookings_sql()
    {
        return "SELECT b.booking_id, b.booking_customer_id, b.state, b.balance_without_forecast AS balance,
                       MIN(bb.check_in_date) AS check_in_date, MAX(bb.check_out_date) AS check_out_date,
                       MAX(bb.room_id) AS room_id
                FROM booking AS b
                LEFT JOIN booking_block AS bb ON bb.booking_id = b.booking_id
                WHERE b.company_id = ? AND b.is_deleted = 0 AND ABS(b.balance_without_forecast) > 0.005
                GROUP BY b.booking_id";
    }
}
