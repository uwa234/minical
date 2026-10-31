<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Corporate (city ledger) accounts: a customer billed on account, with a credit limit and payment terms.
 */
class Migration_Corporate_accounts extends CI_Migration {

    public function up()
    {
        if ($this->db->table_exists('customer')) {
            $columns = array(
                'is_corporate_account' => 'TINYINT(1) NOT NULL DEFAULT 0',
                'credit_limit' => 'DECIMAL(12,2) NULL DEFAULT NULL',
                'payment_terms_days' => 'INT(11) NULL DEFAULT NULL',
            );
            foreach ($columns as $name => $definition) {
                if (!$this->db->field_exists($name, 'customer')) {
                    $this->db->query("ALTER TABLE `customer` ADD COLUMN `{$name}` {$definition}");
                }
            }
        }

        if ($this->db->table_exists('menu')) {
            $parent = $this->db->query("SELECT id FROM menu WHERE link = 'customer' AND parent_id = 0 AND partner_type_id = 1 LIMIT 1")->row_array();
            $exists = $this->db->query("SELECT id FROM menu WHERE link = 'corporate_account' LIMIT 1")->row_array();
            if ($parent && !$exists) {
                $this->db->insert('menu', array(
                    'name' => 'corporate accounts',
                    'link' => 'corporate_account',
                    'icon' => '',
                    'parent_id' => $parent['id'],
                    'partner_type_id' => 1,
                ));
            }
        }
    }

    public function down()
    {
        if ($this->db->table_exists('menu')) {
            $this->db->where('link', 'corporate_account')->delete('menu');
        }
        foreach (array('is_corporate_account', 'credit_limit', 'payment_terms_days') as $name) {
            if ($this->db->field_exists($name, 'customer')) {
                $this->dbforge->drop_column('customer', $name);
            }
        }
    }
}
