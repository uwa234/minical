<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Ttlock_integration extends CI_Migration {

    public function up()
    {
        if (!$this->db->table_exists('ttlock_x_company')) {
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'auto_increment' => TRUE,
                ),
                'company_id' => array(
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'null' => FALSE,
                ),
                'client_id' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 128,
                    'null' => TRUE,
                ),
                'client_secret' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => TRUE,
                ),
                'username' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 128,
                    'null' => TRUE,
                ),
                'password' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => TRUE,
                ),
                'access_token' => array(
                    'type' => 'TEXT',
                    'null' => TRUE,
                ),
                'token_expires_at' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => TRUE,
                ),
                'demo_mode' => array(
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ),
                'is_active' => array(
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ),
                'created_at' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,
                ),
                'updated_at' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,
                ),
            ));
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->add_key('company_id');
            $this->dbforge->create_table('ttlock_x_company', TRUE);
        }

        if (!$this->db->table_exists('ttlock_x_room')) {
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'auto_increment' => TRUE,
                ),
                'company_id' => array(
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'null' => FALSE,
                ),
                'room_id' => array(
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'null' => FALSE,
                ),
                'lock_id' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => FALSE,
                ),
                'lock_alias' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 128,
                    'null' => TRUE,
                ),
            ));
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->add_key(array('company_id', 'room_id'));
            $this->dbforge->create_table('ttlock_x_room', TRUE);
        }

        if (!$this->db->table_exists('ttlock_x_booking')) {
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'auto_increment' => TRUE,
                ),
                'company_id' => array(
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'null' => FALSE,
                ),
                'booking_id' => array(
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'null' => FALSE,
                ),
                'room_id' => array(
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'null' => TRUE,
                ),
                'lock_id' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => TRUE,
                ),
                'keyboard_pwd_id' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => TRUE,
                ),
                'passcode' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'null' => TRUE,
                ),
                'valid_from' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,
                ),
                'valid_to' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,
                ),
                'status' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => 'pending',
                ),
                'last_error' => array(
                    'type' => 'TEXT',
                    'null' => TRUE,
                ),
                'created_at' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,
                ),
                'updated_at' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,
                ),
            ));
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->add_key(array('company_id', 'booking_id'));
            $this->dbforge->create_table('ttlock_x_booking', TRUE);
        }

        $extension_name = 'ttlock-integration';
        $exists = $this->db
            ->where('extension_name', $extension_name)
            ->where('vendor_id', 0)
            ->count_all_results('extensions_x_vendor');

        if ($exists === 0) {
            $this->db->insert('extensions_x_vendor', array(
                'extension_name' => $extension_name,
                'vendor_id' => 0,
                'is_installed' => 1,
            ));
        }
    }

    public function down()
    {
        $this->dbforge->drop_table('ttlock_x_booking', TRUE);
        $this->dbforge->drop_table('ttlock_x_room', TRUE);
        $this->dbforge->drop_table('ttlock_x_company', TRUE);

        $this->db
            ->where('extension_name', 'ttlock-integration')
            ->delete('extensions_x_vendor');
    }
}
