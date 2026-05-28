<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_trial_registration_requests extends CI_Migration {

    public function up()
    {
        if ($this->db->table_exists('trial_registration_request')) {
            return;
        }

        $this->dbforge->add_field(array(
            'trial_registration_request_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ),
            'property_name' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ),
            'number_of_rooms' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
                'default' => 0,
            ),
            'email' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ),
            'phone' => array(
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ),
            'first_name' => array(
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ),
            'last_name' => array(
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ),
            'status' => array(
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => false,
                'default' => 'new',
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => false,
            ),
        ));

        $this->dbforge->add_key('trial_registration_request_id', true);
        $this->dbforge->add_key('email');
        $this->dbforge->add_key('created_at');
        $this->dbforge->create_table('trial_registration_request', true);
    }

    public function down()
    {
        if ($this->db->table_exists('trial_registration_request')) {
            $this->dbforge->drop_table('trial_registration_request', true);
        }
    }
}
