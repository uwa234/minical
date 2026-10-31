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
            $id = (int) $this->db->insert_id();
            $row['trial_registration_request_id'] = $id;
            if (function_exists('platform_notify_trial_signup')) {
                platform_notify_trial_signup($row);
            }
            return $id;
        }

        return false;
    }

    function get_by_id($id)
    {
        if (!$this->table_exists()) {
            return null;
        }

        $this->db->where('trial_registration_request_id', (int) $id);
        $query = $this->db->get('trial_registration_request', 1);

        if ($query->num_rows() < 1) {
            return null;
        }

        return $query->row_array();
    }

    function get_all($options = array())
    {
        if (!$this->table_exists()) {
            return array();
        }

        $search = isset($options['search']) ? trim($options['search']) : '';
        if ($search !== '') {
            $term = '%' . $this->db->escape_like_str($search) . '%';
            $this->db->where(
                '(property_name LIKE ' . $this->db->escape($term)
                . ' OR email LIKE ' . $this->db->escape($term)
                . ' OR phone LIKE ' . $this->db->escape($term)
                . ' OR first_name LIKE ' . $this->db->escape($term)
                . ' OR last_name LIKE ' . $this->db->escape($term) . ')',
                null,
                false
            );
        }

        if (!empty($options['statuses']) && is_array($options['statuses'])) {
            $this->db->where_in('status', $options['statuses']);
        }

        if (!empty($options['unlinked_only'])) {
            if ($this->db->field_exists('company_id', 'trial_registration_request')) {
                $this->db->where('(company_id IS NULL OR company_id = 0)', null, false);
            } else {
                $this->db->where('status !=', 'converted');
            }
        }

        $this->db->order_by('created_at', 'DESC');

        if (!empty($options['limit'])) {
            $this->db->limit((int) $options['limit']);
        }

        $query = $this->db->get('trial_registration_request');

        if ($query->num_rows() < 1) {
            return array();
        }

        return $query->result_array();
    }

    function link_to_company($id, $company_id)
    {
        if (!$this->table_exists()) {
            return false;
        }

        $lead = $this->get_by_id($id);
        if (!$lead) {
            return false;
        }

        if (!empty($lead['company_id'])) {
            return (int) $lead['company_id'] === (int) $company_id;
        }

        $this->db->where('trial_registration_request_id', (int) $id);
        $this->db->update('trial_registration_request', array(
            'company_id' => (int) $company_id,
            'status' => 'converted',
        ));

        return $this->db->affected_rows() > 0;
    }

    function count_new()
    {
        if (!$this->table_exists()) {
            return 0;
        }

        $this->db->where('status', 'new');
        return (int) $this->db->count_all_results('trial_registration_request');
    }

    function has_recent_by_email($email, $within_seconds = 86400)
    {
        if (!$this->table_exists() || $email === '') {
            return false;
        }

        $this->db->where('email', strtolower(trim($email)));
        $this->db->where('created_at >=', date('Y-m-d H:i:s', time() - (int) $within_seconds));
        return $this->db->count_all_results('trial_registration_request') > 0;
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

    function delete($id)
    {
        if (!$this->table_exists()) {
            return false;
        }

        $id = (int) $id;
        if ($id < 1) {
            return false;
        }

        $this->db->where('trial_registration_request_id', $id);
        $this->db->delete('trial_registration_request');
        return $this->db->affected_rows() > 0;
    }
}
