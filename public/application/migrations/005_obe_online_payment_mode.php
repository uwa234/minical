<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Obe_online_payment_mode extends CI_Migration {

    public function up()
    {
        if (!$this->db->field_exists('obe_online_payment_mode', 'company_payment_gateway')) {
            $this->dbforge->add_column('company_payment_gateway', array(
                'obe_online_payment_mode' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => FALSE,
                    'default' => 'both',
                ),
            ));
        }
    }

    public function down()
    {
        if ($this->db->field_exists('obe_online_payment_mode', 'company_payment_gateway')) {
            $this->dbforge->drop_column('company_payment_gateway', 'obe_online_payment_mode');
        }
    }
}
