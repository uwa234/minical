<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Whatsapp_paystack extends CI_Migration {

    public function up()
    {
        if ($this->db->table_exists('whatsapp_x_company') && !$this->db->field_exists('enable_paystack', 'whatsapp_x_company')) {
            $this->dbforge->add_column('whatsapp_x_company', array(
                'enable_paystack' => array(
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'after' => 'payment_link_base_url',
                ),
            ));
        }
    }

    public function down()
    {
        if ($this->db->field_exists('enable_paystack', 'whatsapp_x_company')) {
            $this->dbforge->drop_column('whatsapp_x_company', 'enable_paystack');
        }
    }
}
