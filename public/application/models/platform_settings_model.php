<?php

class Platform_settings_model extends CI_Model {

    function __construct()
    {
        parent::__construct();
    }

    function get($key, $default = null)
    {
        if (!$this->db->table_exists('platform_settings')) {
            return $default;
        }

        $row = $this->db->get_where('platform_settings', array('setting_key' => $key))->row_array();
        if ($row && array_key_exists('setting_value', $row)) {
            return $row['setting_value'];
        }
        return $default;
    }

    function set($key, $value)
    {
        if (!$this->db->table_exists('platform_settings')) {
            return false;
        }

        $existing = $this->db->get_where('platform_settings', array('setting_key' => $key))->row_array();
        if ($existing) {
            $this->db->where('setting_key', $key);
            $this->db->update('platform_settings', array('setting_value' => (string) $value));
        } else {
            $this->db->insert('platform_settings', array(
                'setting_key' => $key,
                'setting_value' => (string) $value,
            ));
        }
        return true;
    }

    function get_default_trial_days()
    {
        $days = (int) $this->get('default_trial_days', 14);
        return $days > 0 ? $days : 14;
    }

    function get_pricing_tiers($active_only = true)
    {
        if (!$this->db->table_exists('saas_pricing_tier')) {
            return array();
        }

        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        $this->db->order_by('sort_order', 'ASC');
        $this->db->order_by('min_rooms', 'ASC');
        $query = $this->db->get('saas_pricing_tier');
        if ($query->num_rows() < 1) {
            return array();
        }

        $rows = $query->result_array();
        foreach ($rows as &$row) {
            $row['features'] = array();
            if (!empty($row['features_json'])) {
                $decoded = json_decode($row['features_json'], true);
                if (is_array($decoded)) {
                    $row['features'] = $decoded;
                }
            }
        }
        return $rows;
    }

    function get_tier_for_room_count($room_count)
    {
        $room_count = (int) $room_count;
        $tiers = $this->get_pricing_tiers(true);
        foreach ($tiers as $tier) {
            $min = (int) $tier['min_rooms'];
            $max = isset($tier['max_rooms']) && $tier['max_rooms'] !== null && $tier['max_rooms'] !== ''
                ? (int) $tier['max_rooms']
                : null;
            if ($room_count >= $min && ($max === null || $room_count <= $max)) {
                return $tier;
            }
        }
        return !empty($tiers) ? end($tiers) : null;
    }

    function get_pricing_tier($id)
    {
        if (!$this->db->table_exists('saas_pricing_tier')) {
            return null;
        }
        $query = $this->db->get_where('saas_pricing_tier', array('id' => (int) $id));
        if ($query->num_rows() < 1) {
            return null;
        }
        $row = $query->row_array();
        $row['features'] = array();
        if (!empty($row['features_json'])) {
            $decoded = json_decode($row['features_json'], true);
            if (is_array($decoded)) {
                $row['features'] = $decoded;
            }
        }
        return $row;
    }

    function save_pricing_tier($data, $id = null)
    {
        if (!$this->db->table_exists('saas_pricing_tier')) {
            return false;
        }

        $features = isset($data['features']) ? $data['features'] : array();
        if (isset($data['features_text'])) {
            $lines = preg_split('/\r\n|\r|\n/', trim($data['features_text']));
            $features = array_values(array_filter(array_map('trim', $lines)));
        }

        $row = array(
            'name' => $data['name'],
            'min_rooms' => (int) $data['min_rooms'],
            'max_rooms' => ($data['max_rooms'] === '' || $data['max_rooms'] === null) ? null : (int) $data['max_rooms'],
            'monthly_price' => (float) $data['monthly_price'],
            'currency' => isset($data['currency']) ? $data['currency'] : 'USD',
            'subscription_level' => (int) $data['subscription_level'],
            'features_json' => json_encode($features),
            'sort_order' => isset($data['sort_order']) ? (int) $data['sort_order'] : 0,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        );

        if ($id) {
            $this->db->where('id', (int) $id);
            $this->db->update('saas_pricing_tier', $row);
            return (int) $id;
        }

        $this->db->insert('saas_pricing_tier', $row);
        return (int) $this->db->insert_id();
    }

    function delete_pricing_tier($id)
    {
        if (!$this->db->table_exists('saas_pricing_tier')) {
            return false;
        }
        $this->db->where('id', (int) $id);
        $this->db->delete('saas_pricing_tier');
        return true;
    }
}
