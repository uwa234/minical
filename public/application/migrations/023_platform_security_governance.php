<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Platform_security_governance extends CI_Migration {

    public function up()
    {
        if (!$this->db->table_exists('platform_admin')) {
            $this->dbforge->add_field(array(
                'platform_admin_id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ),
                'user_id' => array(
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'null' => true,
                ),
                'email' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => false,
                ),
                'role' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'null' => false,
                    'default' => 'admin',
                ),
                'is_active' => array(
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 1,
                ),
                'totp_secret' => array(
                    'type' => 'TEXT',
                    'null' => true,
                ),
                'totp_enabled' => array(
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 0,
                ),
                'created_at' => array(
                    'type' => 'DATETIME',
                    'null' => false,
                ),
                'created_by_user_id' => array(
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'null' => true,
                ),
                'revoked_at' => array(
                    'type' => 'DATETIME',
                    'null' => true,
                ),
                'revoked_by_user_id' => array(
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'null' => true,
                ),
            ));
            $this->dbforge->add_key('platform_admin_id', true);
            $this->dbforge->add_key('email');
            $this->dbforge->add_key('user_id');
            $this->dbforge->create_table('platform_admin', true);
        }

        if (!$this->db->table_exists('platform_audit_log')) {
            $this->dbforge->add_field(array(
                'platform_audit_log_id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ),
                'created_at' => array(
                    'type' => 'DATETIME',
                    'null' => false,
                ),
                'actor_user_id' => array(
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'null' => true,
                ),
                'actor_email' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ),
                'action' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => false,
                ),
                'target_type' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'null' => true,
                ),
                'target_id' => array(
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'null' => true,
                ),
                'target_label' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ),
                'details' => array(
                    'type' => 'TEXT',
                    'null' => true,
                ),
                'ip_address' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 45,
                    'null' => true,
                ),
            ));
            $this->dbforge->add_key('platform_audit_log_id', true);
            $this->dbforge->add_key('created_at');
            $this->dbforge->add_key('action');
            $this->dbforge->add_key('actor_user_id');
            $this->dbforge->create_table('platform_audit_log', true);
        }

        $this->_seed_legacy_platform_admins();
    }

    public function down()
    {
        if ($this->db->table_exists('platform_audit_log')) {
            $this->dbforge->drop_table('platform_audit_log', true);
        }
        if ($this->db->table_exists('platform_admin')) {
            $this->dbforge->drop_table('platform_admin', true);
        }
    }

    private function _seed_legacy_platform_admins()
    {
        $emails = array(SUPER_ADMIN);
        if (defined('PLATFORM_SUPER_ADMIN')) {
            $emails[] = PLATFORM_SUPER_ADMIN;
        }
        if (defined('LEGACY_SUPER_ADMIN')) {
            $emails[] = LEGACY_SUPER_ADMIN;
        }
        $emails = array_unique(array_map('strtolower', array_filter($emails)));
        $now = date('Y-m-d H:i:s');

        foreach ($emails as $email) {
            $email = strtolower($email);
            $exists = $this->db
                ->where('email', $email)
                ->limit(1)
                ->get('platform_admin')
                ->row_array();
            if ($exists) {
                continue;
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
                'role' => 'super_admin',
                'is_active' => 1,
                'created_at' => $now,
            ));
        }
    }
}
