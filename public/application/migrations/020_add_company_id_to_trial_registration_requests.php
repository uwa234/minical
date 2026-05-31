<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_company_id_to_trial_registration_requests extends CI_Migration {

    public function up()
    {
        if (!$this->db->table_exists('trial_registration_request')) {
            return;
        }

        if ($this->db->field_exists('company_id', 'trial_registration_request')) {
            return;
        }

        $this->dbforge->add_column('trial_registration_request', array(
            'company_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'default' => null,
                'after' => 'status',
            ),
        ));
    }

    public function down()
    {
        if ($this->db->table_exists('trial_registration_request')
            && $this->db->field_exists('company_id', 'trial_registration_request')) {
            $this->dbforge->drop_column('trial_registration_request', 'company_id');
        }
    }
}
