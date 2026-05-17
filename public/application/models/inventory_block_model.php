<?php

class Inventory_block_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
    }

    public function get_blocks($company_id, $filters = array())
    {
        $this->db->select('ib.*, blg.name as linked_group_name');
        $this->db->from('inventory_block ib');
        $this->db->join('booking_linked_group blg', 'blg.id = ib.booking_group_id', 'left');
        $this->db->where('ib.company_id', (int) $company_id);

        if (!empty($filters['status'])) {
            $this->db->where('ib.status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $search = $this->db->escape_like_str($filters['search']);
            $this->db->where("(ib.name LIKE '%{$search}%' OR blg.name LIKE '%{$search}%')", null, false);
        }

        $this->db->order_by('ib.check_in_date', 'DESC');
        $query = $this->db->get();

        return $query->result_array();
    }

    public function get_block($block_id, $company_id)
    {
        $this->db->select('ib.*, blg.name as linked_group_name');
        $this->db->from('inventory_block ib');
        $this->db->join('booking_linked_group blg', 'blg.id = ib.booking_group_id', 'left');
        $this->db->where('ib.id', (int) $block_id);
        $this->db->where('ib.company_id', (int) $company_id);
        $query = $this->db->get();

        if ($query->num_rows() < 1) {
            return null;
        }

        $block = $query->row_array();
        $block['lines'] = $this->get_block_lines($block_id);

        return $block;
    }

    public function get_block_lines($block_id)
    {
        $sql = "
            SELECT
                ibl.*,
                rt.name as room_type_name,
                (ibl.quantity - ibl.picked_up) as remaining
            FROM inventory_block_line ibl
            LEFT JOIN room_type rt ON rt.id = ibl.room_type_id
            WHERE ibl.inventory_block_id = ?
            ORDER BY rt.name ASC
        ";
        $query = $this->db->query($sql, array((int) $block_id));

        return $query->result_array();
    }

    public function create_block($company_id, $data, $lines)
    {
        $now = date('Y-m-d H:i:s');
        $insert = array(
            'company_id' => (int) $company_id,
            'name' => $data['name'],
            'booking_group_id' => !empty($data['booking_group_id']) ? (int) $data['booking_group_id'] : null,
            'account_customer_id' => !empty($data['account_customer_id']) ? (int) $data['account_customer_id'] : null,
            'check_in_date' => $data['check_in_date'],
            'check_out_date' => $data['check_out_date'],
            'cutoff_date' => !empty($data['cutoff_date']) ? $data['cutoff_date'] : null,
            'release_date' => !empty($data['release_date']) ? $data['release_date'] : null,
            'status' => !empty($data['status']) ? $data['status'] : 'active',
            'notes' => isset($data['notes']) ? $data['notes'] : null,
            'created_at' => $now,
            'updated_at' => $now,
        );

        $this->db->insert('inventory_block', $insert);
        $block_id = $this->db->insert_id();

        if ($block_id && is_array($lines)) {
            foreach ($lines as $line) {
                if (empty($line['room_type_id']) || empty($line['quantity'])) {
                    continue;
                }
                $this->db->insert('inventory_block_line', array(
                    'inventory_block_id' => $block_id,
                    'room_type_id' => (int) $line['room_type_id'],
                    'quantity' => max(1, (int) $line['quantity']),
                    'picked_up' => 0,
                    'rate' => isset($line['rate']) ? $line['rate'] : null,
                ));
            }
        }

        return $block_id;
    }

    public function update_block($block_id, $company_id, $data)
    {
        $this->db->where('id', (int) $block_id);
        $this->db->where('company_id', (int) $company_id);
        $data['updated_at'] = date('Y-m-d H:i:s');
        unset($data['company_id']);

        return $this->db->update('inventory_block', $data);
    }

    public function release_block($block_id, $company_id)
    {
        return $this->update_block($block_id, $company_id, array('status' => 'released'));
    }

    public function cancel_block($block_id, $company_id)
    {
        return $this->update_block($block_id, $company_id, array('status' => 'cancelled'));
    }

    /**
     * Max held rooms per night for a room type (unpicked portion of active blocks).
     */
    public function get_max_hold_for_range($company_id, $room_type_id, $check_in_date, $check_out_date, $exclude_block_id = null)
    {
        $exclude_sql = $exclude_block_id ? ' AND ib.id != ' . (int) $exclude_block_id : '';

        $sql = "
            SELECT MAX(hold_qty) as max_hold
            FROM (
                SELECT di.date, SUM(GREATEST(0, ibl.quantity - ibl.picked_up)) as hold_qty
                FROM date_interval di
                INNER JOIN inventory_block ib ON ib.company_id = ?
                    AND ib.status = 'active'
                    AND ib.check_out_date > di.date
                    AND di.date >= ib.check_in_date
                    {$exclude_sql}
                INNER JOIN inventory_block_line ibl ON ibl.inventory_block_id = ib.id
                    AND ibl.room_type_id = ?
                WHERE di.date >= ? AND di.date < ?
                GROUP BY di.date
            ) holds
        ";

        $query = $this->db->query($sql, array(
            (int) $company_id,
            (int) $room_type_id,
            $check_in_date,
            $check_out_date,
        ));

        $row = $query->row_array();

        return isset($row['max_hold']) ? (int) $row['max_hold'] : 0;
    }

    public function get_line($line_id, $company_id)
    {
        $sql = "
            SELECT ibl.*, ib.company_id, ib.check_in_date, ib.check_out_date, ib.booking_group_id, ib.name as block_name
            FROM inventory_block_line ibl
            INNER JOIN inventory_block ib ON ib.id = ibl.inventory_block_id
            WHERE ibl.id = ? AND ib.company_id = ?
        ";
        $query = $this->db->query($sql, array((int) $line_id, (int) $company_id));

        if ($query->num_rows() < 1) {
            return null;
        }

        return $query->row_array();
    }

    public function record_pickup($line_id, $booking_id)
    {
        $this->db->insert('block_pickup', array(
            'inventory_block_line_id' => (int) $line_id,
            'booking_id' => (int) $booking_id,
            'created_at' => date('Y-m-d H:i:s'),
        ));

        $this->db->set('picked_up', 'picked_up + 1', false);
        $this->db->where('id', (int) $line_id);
        $this->db->where('picked_up < quantity', null, false);
        $this->db->update('inventory_block_line');

        return $this->db->affected_rows() > 0;
    }

    public function get_pickups_for_block($block_id)
    {
        $sql = "
            SELECT bp.*, b.booking_id, b.state, c.customer_name, brh.check_in_date, brh.check_out_date, r.room_name, rt.name as room_type_name
            FROM block_pickup bp
            INNER JOIN inventory_block_line ibl ON ibl.id = bp.inventory_block_line_id
            INNER JOIN booking b ON b.booking_id = bp.booking_id
            LEFT JOIN customer c ON c.customer_id = b.booking_customer_id
            LEFT JOIN booking_block brh ON brh.booking_id = b.booking_id
            LEFT JOIN room r ON r.room_id = brh.room_id
            LEFT JOIN room_type rt ON rt.id = ibl.room_type_id
            WHERE ibl.inventory_block_id = ?
            ORDER BY bp.created_at DESC
        ";
        $query = $this->db->query($sql, array((int) $block_id));

        return $query->result_array();
    }

    public function process_auto_releases($company_id = null)
    {
        $today = date('Y-m-d');
        $this->db->where('status', 'active');
        $this->db->where('release_date <=', $today);
        $this->db->where('release_date IS NOT NULL', null, false);

        if ($company_id) {
            $this->db->where('company_id', (int) $company_id);
        }

        $this->db->update('inventory_block', array(
            'status' => 'released',
            'updated_at' => date('Y-m-d H:i:s'),
        ));

        return $this->db->affected_rows();
    }
}
