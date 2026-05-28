<?php

class Trial_registration_request_model extends CI_Model {

    function __construct()
    {
        parent::__construct();
    }

    function table_exists()
    {
        return $this->db->table_exists('trial_registration_request');
    }

    function create($data)
    {
        if (!$this->table_exists()) {
            return false;
        }

        $row = array(
            'property_name' => $data['property_name'],
            'number_of_rooms' => (int) $data['number_of_rooms'],
            'email' => $data['email'],
            'phone' => isset($data['phone']) ? $data['phone'] : '',
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'status' => 'new',
            'created_at' => date('Y-m-d H:i:s'),
        );

        if ($this->db->insert('trial_registration_request', $row)) {
            return (int) $this->db->insert_id();
        }

        return false;
    }

    function get_all($options = array())
    {
        if (!$this->table_exists()) {
            return array();
        }

        $search = isset($options['search']) ? trim($options['search']) : '';
        if ($search !== '') {
            $this->db->group_start();
            $this->db->like('property_name', $search);
            $this->db->or_like('email', $search);
            $this->db->or_like('phone', $search);
            $this->db->or_like('first_name', $search);
            $this->db->or_like('last_name', $search);
            $this->db->group_end();
        }

        $this->db->order_by('created_at', 'DESC');
        $query = $this->db->get('trial_registration_request');

        if ($query->num_rows() < 1) {
            return array();
        }

        return $query->result_array();
    }

    function count_new()
    {
        if (!$this->table_exists()) {
            return 0;
        }

        $this->db->where('status', 'new');
        return (int) $this->db->count_all_results('trial_registration_request');
    }

    function update_status($id, $status)
    {
        if (!$this->table_exists()) {
            return false;
        }

        $allowed = array('new', 'contacted', 'converted', 'declined');
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $this->db->where('trial_registration_request_id', (int) $id);
        $this->db->update('trial_registration_request', array('status' => $status));
        return $this->db->affected_rows() > 0;
    }
}
