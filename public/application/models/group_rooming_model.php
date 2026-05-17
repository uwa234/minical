<?php

class Group_rooming_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
    }

    public function get_rooming_list($booking_group_id, $company_id)
    {
        $entries = $this->get_entries($booking_group_id);
        $booked = $this->get_linked_bookings_rooming($booking_group_id, $company_id);

        $by_booking = array();
        foreach ($booked as $row) {
            $by_booking[$row['booking_id']] = $row;
        }

        foreach ($entries as $index => $entry) {
            if (!empty($entry['booking_id']) && isset($by_booking[$entry['booking_id']])) {
                $entries[$index] = array_merge($entry, $by_booking[$entry['booking_id']]);
                unset($by_booking[$entry['booking_id']]);
            }
        }

        foreach ($by_booking as $booking_id => $row) {
            $entries[] = array_merge(array(
                'id' => null,
                'booking_group_id' => $booking_group_id,
                'booking_id' => $booking_id,
                'guest_name' => null,
                'guest_customer_id' => null,
                'room_type_id' => $row['room_type_id'],
                'sort_order' => 9999,
                'notes' => null,
                'is_implicit' => true,
            ), $row);
        }

        usort($entries, function ($a, $b) {
            $ao = isset($a['sort_order']) ? (int) $a['sort_order'] : 0;
            $bo = isset($b['sort_order']) ? (int) $b['sort_order'] : 0;
            if ($ao === $bo) {
                return strcmp(isset($a['room_name']) ? $a['room_name'] : '', isset($b['room_name']) ? $b['room_name'] : '');
            }
            return $ao - $bo;
        });

        return $entries;
    }

    public function get_entries($booking_group_id)
    {
        $this->db->where('booking_group_id', (int) $booking_group_id);
        $this->db->order_by('sort_order', 'ASC');
        $this->db->order_by('id', 'ASC');
        $query = $this->db->get('group_rooming_entry');

        return $query->result_array();
    }

    public function get_linked_bookings_rooming($booking_group_id, $company_id)
    {
        $sql = "
            SELECT
                b.booking_id,
                b.state,
                c.customer_name,
                c.customer_id as guest_customer_id,
                brh.check_in_date,
                brh.check_out_date,
                brh.room_id,
                brh.room_type_id,
                r.room_name,
                rt.name as room_type_name,
                IF(b.state = 4, 1, 0) as room_cancelled
            FROM booking_x_booking_linked_group bxblg
            INNER JOIN booking b ON b.booking_id = bxblg.booking_id
            LEFT JOIN customer c ON c.customer_id = b.booking_customer_id
            LEFT JOIN booking_block brh ON brh.booking_id = b.booking_id
            LEFT JOIN room r ON r.room_id = brh.room_id
            LEFT JOIN room_type rt ON rt.id = brh.room_type_id
            WHERE bxblg.booking_group_id = ?
                AND b.company_id = ?
                AND b.is_deleted = 0
            GROUP BY b.booking_id
            ORDER BY r.room_name ASC
        ";
        $query = $this->db->query($sql, array((int) $booking_group_id, (int) $company_id));

        return $query->result_array();
    }

    public function save_entry($booking_group_id, $data)
    {
        $now = date('Y-m-d H:i:s');
        $row = array(
            'booking_group_id' => (int) $booking_group_id,
            'booking_id' => !empty($data['booking_id']) ? (int) $data['booking_id'] : null,
            'room_type_id' => !empty($data['room_type_id']) ? (int) $data['room_type_id'] : null,
            'guest_name' => isset($data['guest_name']) ? $data['guest_name'] : null,
            'guest_customer_id' => !empty($data['guest_customer_id']) ? (int) $data['guest_customer_id'] : null,
            'sort_order' => isset($data['sort_order']) ? (int) $data['sort_order'] : 0,
            'notes' => isset($data['notes']) ? $data['notes'] : null,
            'updated_at' => $now,
        );

        if (!empty($data['id'])) {
            $this->db->where('id', (int) $data['id']);
            $this->db->where('booking_group_id', (int) $booking_group_id);
            $this->db->update('group_rooming_entry', $row);

            return (int) $data['id'];
        }

        $row['created_at'] = $now;
        $this->db->insert('group_rooming_entry', $row);

        return $this->db->insert_id();
    }

    public function add_unassigned_slots($booking_group_id, $room_type_id, $count)
    {
        $ids = array();
        $max_sort = 0;
        $this->db->select_max('sort_order');
        $this->db->where('booking_group_id', (int) $booking_group_id);
        $q = $this->db->get('group_rooming_entry');
        $row = $q->row_array();
        if ($row && isset($row['sort_order'])) {
            $max_sort = (int) $row['sort_order'];
        }

        for ($i = 0; $i < (int) $count; $i++) {
            $ids[] = $this->save_entry($booking_group_id, array(
                'room_type_id' => $room_type_id,
                'sort_order' => $max_sort + $i + 1,
            ));
        }

        return $ids;
    }

    public function delete_entry($entry_id, $booking_group_id)
    {
        $this->db->where('id', (int) $entry_id);
        $this->db->where('booking_group_id', (int) $booking_group_id);

        return $this->db->delete('group_rooming_entry');
    }

    public function assign_guest_to_booking($booking_id, $guest_name, $guest_customer_id = null)
    {
        if ($guest_customer_id) {
            $this->db->where('booking_id', (int) $booking_id);
            $this->db->update('booking', array('booking_customer_id' => (int) $guest_customer_id));

            return true;
        }

        if (!$guest_name) {
            return false;
        }

        $booking = $this->db->select('company_id')->where('booking_id', (int) $booking_id)->get('booking')->row_array();
        if (!$booking) {
            return false;
        }

        $this->load->model('Customer_model');
        $customer_id = $this->Customer_model->create_customer((object) array(
            'customer_name' => $guest_name,
            'company_id' => $booking['company_id'],
        ));

        if ($customer_id) {
            $this->db->where('booking_id', (int) $booking_id);
            $this->db->update('booking', array('booking_customer_id' => $customer_id));

            return $customer_id;
        }

        return false;
    }
}
