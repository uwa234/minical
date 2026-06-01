<?php

class Platform_communication_model extends CI_Model {

    function __construct()
    {
        parent::__construct();
    }

    function email_templates_exist()
    {
        return $this->db->table_exists('platform_email_template');
    }

    function get_email_templates()
    {
        if (!$this->email_templates_exist()) {
            return array();
        }

        $this->db->order_by('slug', 'ASC');
        $query = $this->db->get('platform_email_template');

        return $query->num_rows() > 0 ? $query->result_array() : array();
    }

    function get_email_template($slug)
    {
        if (!$this->email_templates_exist()) {
            return null;
        }

        $query = $this->db->get_where('platform_email_template', array('slug' => (string) $slug), 1);

        return $query->num_rows() > 0 ? $query->row_array() : null;
    }

    function save_email_template($slug, $data)
    {
        if (!$this->email_templates_exist()) {
            return false;
        }

        $slug = (string) $slug;
        $row = array(
            'subject' => isset($data['subject']) ? (string) $data['subject'] : '',
            'body_html' => isset($data['body_html']) ? (string) $data['body_html'] : '',
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'updated_at' => gmdate('Y-m-d H:i:s'),
        );

        $this->db->where('slug', $slug);
        $this->db->update('platform_email_template', $row);

        return $this->db->affected_rows() >= 0;
    }

    function announcements_exist()
    {
        return $this->db->table_exists('platform_announcement');
    }

    function get_announcements()
    {
        if (!$this->announcements_exist()) {
            return array();
        }

        $this->db->order_by('created_at', 'DESC');
        $query = $this->db->get('platform_announcement');

        return $query->num_rows() > 0 ? $query->result_array() : array();
    }

    function get_announcement($id)
    {
        if (!$this->announcements_exist()) {
            return null;
        }

        $query = $this->db->get_where('platform_announcement', array('id' => (int) $id), 1);

        return $query->num_rows() > 0 ? $query->row_array() : null;
    }

    function get_active_announcement()
    {
        if (!$this->announcements_exist()) {
            return null;
        }

        $this->_apply_active_announcement_filters();
        $this->db->order_by('id', 'DESC');
        $query = $this->db->get('platform_announcement', 1);

        return $query->num_rows() > 0 ? $query->row_array() : null;
    }

    function save_announcement($data, $id = null)
    {
        if (!$this->announcements_exist()) {
            return false;
        }

        $starts_at = !empty($data['starts_at']) ? $this->_normalize_datetime((string) $data['starts_at']) : null;
        $ends_at = !empty($data['ends_at']) ? $this->_normalize_datetime((string) $data['ends_at']) : null;

        $row = array(
            'title' => isset($data['title']) ? trim((string) $data['title']) : '',
            'body' => isset($data['body']) ? trim((string) $data['body']) : '',
            'severity' => isset($data['severity']) && $data['severity'] === 'warning' ? 'warning' : 'info',
            'starts_at' => $starts_at,
            'ends_at' => $ends_at,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'dismissible' => !empty($data['dismissible']) ? 1 : 0,
            'updated_at' => gmdate('Y-m-d H:i:s'),
        );

        if ($row['title'] === '' || $row['body'] === '') {
            return false;
        }

        if ($id) {
            $this->db->where('id', (int) $id);
            $this->db->update('platform_announcement', $row);

            return $this->db->affected_rows() >= 0 ? (int) $id : false;
        }

        $row['created_at'] = gmdate('Y-m-d H:i:s');
        if ($this->db->insert('platform_announcement', $row)) {
            return (int) $this->db->insert_id();
        }

        return false;
    }

    function delete_announcement($id)
    {
        if (!$this->announcements_exist()) {
            return false;
        }

        $this->db->where('id', (int) $id);
        $this->db->delete('platform_announcement');

        return $this->db->affected_rows() > 0;
    }

    function count_active_announcements()
    {
        if (!$this->announcements_exist()) {
            return 0;
        }

        $this->_apply_active_announcement_filters();

        return (int) $this->db->count_all_results('platform_announcement');
    }

    function get_webhook_settings()
    {
        if (!$this->db->table_exists('platform_settings')) {
            return array(
                'webhook_url' => '',
                'slack_webhook_url' => '',
                'webhook_enabled_events' => array_keys(platform_webhook_event_labels()),
            );
        }

        $this->load->model('Platform_settings_model');
        $events = platform_webhook_enabled_events_from_storage(
            $this->Platform_settings_model->get('webhook_enabled_events', '')
        );

        return array(
            'webhook_url' => (string) $this->Platform_settings_model->get('webhook_url', ''),
            'slack_webhook_url' => (string) $this->Platform_settings_model->get('slack_webhook_url', ''),
            'webhook_enabled_events' => $events,
        );
    }

    function save_webhook_settings($data)
    {
        if (!$this->db->table_exists('platform_settings')) {
            return false;
        }

        $this->load->model('Platform_settings_model');
        $allowed = array_keys(platform_webhook_event_labels());
        $events = isset($data['webhook_enabled_events']) && is_array($data['webhook_enabled_events'])
            ? array_values(array_intersect($data['webhook_enabled_events'], $allowed))
            : array();

        return $this->Platform_settings_model->set('webhook_url', trim((string) $data['webhook_url']))
            && $this->Platform_settings_model->set('slack_webhook_url', trim((string) $data['slack_webhook_url']))
            && $this->Platform_settings_model->set('webhook_enabled_events', json_encode($events));
    }

    function _normalize_datetime($value)
    {
        $value = trim(str_replace('T', ' ', (string) $value));
        if ($value === '') {
            return null;
        }
        if (strlen($value) === 16) {
            $value .= ':00';
        }

        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $value);
        if (!$dt) {
            return null;
        }

        return $dt->format('Y-m-d H:i:s');
    }

    function _apply_active_announcement_filters($now = null)
    {
        $now = $now ? $now : gmdate('Y-m-d H:i:s');
        $this->db->where('is_active', 1);
        $this->db->where(
            '(starts_at IS NULL OR starts_at <= ' . $this->db->escape($now) . ')',
            null,
            false
        );
        $this->db->where(
            '(ends_at IS NULL OR ends_at >= ' . $this->db->escape($now) . ')',
            null,
            false
        );
    }
}
