<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SaaS receipt URLs include a signed token and exceed the original VARCHAR(100).
 */
class Migration_Increase_company_subscription_invoice_link extends CI_Migration {

    public function up()
    {
        if (!$this->db->table_exists('company_subscription')) {
            return;
        }

        if (!$this->db->field_exists('invoice_link', 'company_subscription')) {
            return;
        }

        $this->dbforge->modify_column('company_subscription', array(
            'invoice_link' => array(
                'type' => 'VARCHAR',
                'constraint' => 512,
                'null' => true,
            ),
        ));
    }

    public function down()
    {
        if (!$this->db->table_exists('company_subscription')) {
            return;
        }

        if (!$this->db->field_exists('invoice_link', 'company_subscription')) {
            return;
        }

        $this->dbforge->modify_column('company_subscription', array(
            'invoice_link' => array(
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ),
        ));
    }
}
