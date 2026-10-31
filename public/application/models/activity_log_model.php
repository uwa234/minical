<?php

/**
 * Owner activity log: invoice_log (charges, payments, refunds, voids, folios) and
 * booking_log (status, rate, room, date and customer changes) for one property.
 */
class Activity_log_model extends CI_Model {

    // invoice_log.action_id values (config/constants.php)
    const RISK_INVOICE_ACTIONS = '2,3,5,6,7,12,13';
    const CHARGE_ACTIONS = '1,2,3';
    const PAYMENT_ACTIONS = '4,5,6,7,8,9,13';

    function __construct()
    {
        parent::__construct();
    }

    /**
     * @param string $category risk | money | bookings | all
     */
    function get_events($company_id, $from_utc, $to_utc, $user_id = 0, $category = 'risk', $limit = 1000)
    {
        $parts = array();
        $bindings = array();

        if ($category !== 'bookings') {
            $where = '';
            if ($category === 'risk') {
                $where = " AND (il.action_id IN (" . self::RISK_INVOICE_ACTIONS . ") OR (il.action_id = 1 AND il.log LIKE 'API charge reversed%'))";
            }
            if ($user_id) {
                $where .= ' AND il.user_id = ' . (int) $user_id;
            }
            $parts[] = "SELECT 'invoice' AS source, il.date_time, il.user_id, il.booking_id, il.action_id AS type,
                               il.log, il.new_amount AS amount, COALESCE(ch.description, p.description) AS item_description
                        FROM invoice_log AS il
                        JOIN booking AS b ON b.booking_id = il.booking_id
                        LEFT JOIN charge AS ch ON il.action_id IN (" . self::CHARGE_ACTIONS . ") AND ch.charge_id = il.charge_or_payment_id
                        LEFT JOIN payment AS p ON il.action_id IN (" . self::PAYMENT_ACTIONS . ") AND p.payment_id = il.charge_or_payment_id
                        WHERE b.company_id = ? AND il.date_time >= ? AND il.date_time < ?" . $where;
            array_push($bindings, (int) $company_id, $from_utc, $to_utc);
        }

        if ($category !== 'money') {
            $where = '';
            if ($category === 'risk') {
                $where = " AND (bl.log_type IN (9, 11, 14, 18) OR (bl.log_type = 5 AND bl.log IN ('4', '5')))";
            } else {
                $where = ' AND bl.log_type >= 1 AND bl.log_type NOT IN (2, 13)';
            }
            if ($user_id) {
                $where .= ' AND bl.user_id = ' . (int) $user_id;
            }
            $parts[] = "SELECT 'booking' AS source, bl.date_time, bl.user_id, bl.booking_id, bl.log_type AS type,
                               bl.log, NULL AS amount, NULL AS item_description
                        FROM booking_log AS bl
                        JOIN booking AS b ON b.booking_id = bl.booking_id
                        WHERE b.company_id = ? AND bl.date_time >= ? AND bl.date_time < ?" . $where;
            array_push($bindings, (int) $company_id, $from_utc, $to_utc);
        }

        $sql = "SELECT * FROM (" . implode(' UNION ALL ', $parts) . ") AS events
                ORDER BY date_time DESC
                LIMIT " . (int) $limit;
        return $this->db->query($sql, $bindings)->result_array();
    }

    function get_user_names($user_ids)
    {
        $user_ids = array_values(array_filter(array_map('intval', array_unique($user_ids))));
        if (!$user_ids) {
            return array();
        }
        $rows = $this->db->select('u.id, u.email, up.first_name, up.last_name')
            ->from('users AS u')
            ->join('user_profiles AS up', 'up.user_id = u.id', 'left')
            ->where_in('u.id', $user_ids)
            ->get()
            ->result_array();
        $names = array();
        foreach ($rows as $row) {
            $name = trim($row['first_name'] . ' ' . $row['last_name']);
            $names[(int) $row['id']] = $name !== '' ? $name : $row['email'];
        }
        return $names;
    }

    function get_room_names($company_id, $room_ids)
    {
        $room_ids = array_values(array_filter(array_map('intval', array_unique($room_ids))));
        if (!$room_ids) {
            return array();
        }
        $rows = $this->db->select('room_id, room_name')->where('company_id', (int) $company_id)->where_in('room_id', $room_ids)->get('room')->result_array();
        $names = array();
        foreach ($rows as $row) {
            $names[(int) $row['room_id']] = $row['room_name'];
        }
        return $names;
    }

    function get_rate_plan_names($rate_plan_ids)
    {
        $rate_plan_ids = array_values(array_filter(array_map('intval', array_unique($rate_plan_ids))));
        if (!$rate_plan_ids) {
            return array();
        }
        $rows = $this->db->select('rate_plan_id, rate_plan_name')->where_in('rate_plan_id', $rate_plan_ids)->get('rate_plan')->result_array();
        $names = array();
        foreach ($rows as $row) {
            $names[(int) $row['rate_plan_id']] = $row['rate_plan_name'];
        }
        return $names;
    }
}
