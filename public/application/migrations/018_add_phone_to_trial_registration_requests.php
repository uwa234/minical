<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_phone_to_trial_registration_requests extends CI_Migration {

    public function up()
    {
        if (!$this->db->table_exists('trial_registration_request')) {
            return;
        }

        if ($this->db->field_exists('phone', 'trial_registration_request')) {
            return;
        }

        $this->dbforge->add_column('trial_registration_request', array(
            'phone' => array(
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'default' => '',
                'after' => 'email',
            ),
        ));
    }

    public function down()
    {
        if ($this->db->table_exists('trial_registration_request') && $this->db->field_exists('phone', 'trial_registration_request')) {
            $this->dbforge->drop_column('trial_registration_request', 'phone');
        }
    }
}
