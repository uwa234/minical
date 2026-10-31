<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Platform_admin_model extends CI_Model {

    function table_exists()
    {
        return $this->db->table_exists('platform_admin');
    }

    function get_all($include_revoked = false)
    {
        if (!$this->table_exists()) {
            return array();
        }

        if (!$include_revoked) {
            $this->db->where('is_active', 1);
        }
        $this->db->order_by('created_at', 'DESC');

        return $this->db->get('platform_admin')->result_array();
    }

    function get_by_id($platform_admin_id)
    {
        if (!$this->table_exists()) {
            return null;
        }

        return $this->db
            ->where('platform_admin_id', (int) $platform_admin_id)
            ->limit(1)
            ->get('platform_admin')
            ->row_array();
    }

    function get_active_by_email($email)
    {
        if (!$this->table_exists() || $email === '') {
            return null;
        }

        return $this->db
            ->where('email', strtolower(trim($email)))
            ->where('is_active', 1)
            ->limit(1)
            ->get('platform_admin')
            ->row_array();
    }

    function get_active_by_user_id($user_id)
    {
        if (!$this->table_exists() || (int) $user_id < 1) {
            return null;
        }

        return $this->db
            ->where('user_id', (int) $user_id)
            ->where('is_active', 1)
            ->limit(1)
            ->get('platform_admin')
            ->row_array();
    }

    function is_active_platform_admin_email($email)
    {
        return (bool) $this->get_active_by_email($email);
    }

    function is_active_platform_admin_user($user_id, $email = null)
    {
        if ($this->get_active_by_user_id($user_id)) {
            return true;
        }
        if ($email !== null && $email !== '') {
            return (bool) $this->get_active_by_email($email);
        }

        return false;
    }

    function count_active()
    {
        if (!$this->table_exists()) {
            return 0;
        }

        $this->db->where('is_active', 1);
        return (int) $this->db->count_all_results('platform_admin');
    }

    function add_admin($email, $created_by_user_id = null, $role = 'admin')
    {
        if (!$this->table_exists()) {
            return false;
        }

        $email = strtolower(trim((string) $email));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $existing = $this->db
            ->where('email', $email)
            ->limit(1)
            ->get('platform_admin')
            ->row_array();
        if ($existing) {
            if (!empty($existing['is_active'])) {
                return (int) $existing['platform_admin_id'];
            }
            $this->db->where('platform_admin_id', (int) $existing['platform_admin_id']);
            $this->db->update('platform_admin', array(
                'is_active' => 1,
                'revoked_at' => null,
                'revoked_by_user_id' => null,
                'role' => $role,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by_user_id' => $created_by_user_id ? (int) $created_by_user_id : null,
            ));

            return (int) $existing['platform_admin_id'];
        }

        $user_id = null;
        $user = $this->db
            ->select('id')
            ->where('email', $email)
            ->limit(1)
            ->get('users')
            ->row_array();
        if ($user && isset($user['id'])) {
            $user_id = (int) $user['id'];
        }

        $this->db->insert('platform_admin', array(
            'user_id' => $user_id,
            'email' => $email,
            'role' => $role,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by_user_id' => $created_by_user_id ? (int) $created_by_user_id : null,
        ));

        return (int) $this->db->insert_id();
    }

    function revoke_admin($platform_admin_id, $revoked_by_user_id = null)
    {
        if (!$this->table_exists()) {
            return false;
        }

        $this->db->where('platform_admin_id', (int) $platform_admin_id);
        $this->db->update('platform_admin', array(
            'is_active' => 0,
            'revoked_at' => date('Y-m-d H:i:s'),
            'revoked_by_user_id' => $revoked_by_user_id ? (int) $revoked_by_user_id : null,
        ));

        return $this->db->affected_rows() > 0;
    }

    function count_active_admins()
    {
        if (!$this->table_exists()) {
            return 0;
        }

        return (int) $this->db
            ->where('is_active', 1)
            ->count_all_results('platform_admin');
    }

    function get_record_for_user($user_id, $email = null)
    {
        $row = $this->get_active_by_user_id($user_id);
        if ($row) {
            return $row;
        }
        if ($email !== null && $email !== '') {
            return $this->get_active_by_email($email);
        }

        return null;
    }

    function is_2fa_enabled($user_id, $email = null)
    {
        $row = $this->get_record_for_user($user_id, $email);
        return $row && !empty($row['totp_enabled']) && !empty($row['totp_secret']);
    }

    function save_totp_secret($platform_admin_id, $encrypted_secret, $enabled = false)
    {
        if (!$this->table_exists()) {
            return false;
        }

        $this->db->where('platform_admin_id', (int) $platform_admin_id);
        $this->db->update('platform_admin', array(
            'totp_secret' => $encrypted_secret,
            'totp_enabled' => $enabled ? 1 : 0,
        ));

        return $this->db->affected_rows() >= 0;
    }

    function enable_2fa($platform_admin_id)
    {
        if (!$this->table_exists()) {
            return false;
        }

        $this->db->where('platform_admin_id', (int) $platform_admin_id);
        $this->db->update('platform_admin', array('totp_enabled' => 1));

        return $this->db->affected_rows() > 0;
    }

    function disable_2fa($platform_admin_id)
    {
        if (!$this->table_exists()) {
            return false;
        }

        $this->db->where('platform_admin_id', (int) $platform_admin_id);
        $this->db->update('platform_admin', array(
            'totp_enabled' => 0,
            'totp_secret' => null,
        ));

        return $this->db->affected_rows() > 0;
    }

    function get_totp_secret_encrypted($platform_admin_id)
    {
        $row = $this->get_by_id($platform_admin_id);
        if (!$row || empty($row['totp_secret'])) {
            return null;
        }

        return (string) $row['totp_secret'];
    }

    function link_user_id_by_email($email, $user_id)
    {
        if (!$this->table_exists()) {
            return;
        }

        $this->db
            ->where('email', strtolower(trim((string) $email)))
            ->where('(user_id IS NULL OR user_id = 0)', null, false)
            ->update('platform_admin', array('user_id' => (int) $user_id));
    }
}
