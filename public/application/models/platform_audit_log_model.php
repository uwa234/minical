<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Platform_audit_log_model extends CI_Model {

    function table_exists()
    {
        return $this->db->table_exists('platform_audit_log');
    }

    function add_entry($data)
    {
        if (!$this->table_exists()) {
            return false;
        }

        $row = array(
            'created_at' => date('Y-m-d H:i:s'),
            'actor_user_id' => isset($data['actor_user_id']) ? (int) $data['actor_user_id'] : null,
            'actor_email' => isset($data['actor_email']) ? (string) $data['actor_email'] : null,
            'action' => isset($data['action']) ? (string) $data['action'] : 'unknown',
            'target_type' => isset($data['target_type']) ? (string) $data['target_type'] : null,
            'target_id' => isset($data['target_id']) ? (int) $data['target_id'] : null,
            'target_label' => isset($data['target_label']) ? (string) $data['target_label'] : null,
            'details' => isset($data['details']) ? (string) $data['details'] : null,
            'ip_address' => isset($data['ip_address']) ? (string) $data['ip_address'] : null,
        );

        return $this->db->insert('platform_audit_log', $row);
    }

    function get_entries($filters = array(), $limit = 100, $offset = 0)
    {
        if (!$this->table_exists()) {
            return array();
        }

        if (!empty($filters['action'])) {
            $this->db->where('action', (string) $filters['action']);
        }
        if (!empty($filters['actor_email'])) {
            $this->db->like('actor_email', (string) $filters['actor_email']);
        }
        if (!empty($filters['target_type'])) {
            $this->db->where('target_type', (string) $filters['target_type']);
        }
        if (!empty($filters['search'])) {
            $search = (string) $filters['search'];
            $this->db->group_start();
            $this->db->like('details', $search);
            $this->db->or_like('target_label', $search);
            $this->db->or_like('actor_email', $search);
            $this->db->group_end();
        }

        $this->db->order_by('created_at', 'DESC');
        $this->db->limit((int) $limit, (int) $offset);

        return $this->db->get('platform_audit_log')->result_array();
    }

    function count_entries($filters = array())
    {
        if (!$this->table_exists()) {
            return 0;
        }

        if (!empty($filters['action'])) {
            $this->db->where('action', (string) $filters['action']);
        }
        if (!empty($filters['actor_email'])) {
            $this->db->like('actor_email', (string) $filters['actor_email']);
        }
        if (!empty($filters['target_type'])) {
            $this->db->where('target_type', (string) $filters['target_type']);
        }
        if (!empty($filters['search'])) {
            $search = (string) $filters['search'];
            $this->db->group_start();
            $this->db->like('details', $search);
            $this->db->or_like('target_label', $search);
            $this->db->or_like('actor_email', $search);
            $this->db->group_end();
        }

        return (int) $this->db->count_all_results('platform_audit_log');
    }

    function action_labels()
    {
        return array(
            'login' => 'Login',
            'login.2fa' => '2FA verification',
            'admin.add' => 'Admin added',
            'admin.revoke' => 'Admin revoked',
            '2fa.enable' => '2FA enabled',
            '2fa.disable' => '2FA disabled',
            'property.soft_delete' => 'Property soft-deleted',
            'property.restore' => 'Property restored',
            'property.update' => 'Property updated',
            'subscription.update' => 'Subscription updated',
            'settings.update' => 'Settings updated',
            'impersonation.start' => 'Impersonation started',
            'tenant.action' => 'Tenant action',
        );
    }
}
