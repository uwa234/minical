<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Paystack_payment_gateway extends CI_Migration {

    public function up()
    {
        if (!$this->db->field_exists('paystack_public_key', 'company_payment_gateway')) {
            $this->dbforge->add_column('company_payment_gateway', array(
                'paystack_public_key' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => TRUE,
                ),
                'paystack_secret_key' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => TRUE,
                ),
            ));
        }
    }

    public function down()
    {
        if ($this->db->field_exists('paystack_public_key', 'company_payment_gateway')) {
            $this->dbforge->drop_column('company_payment_gateway', 'paystack_public_key');
        }
        if ($this->db->field_exists('paystack_secret_key', 'company_payment_gateway')) {
            $this->dbforge->drop_column('company_payment_gateway', 'paystack_secret_key');
        }
    }
}
