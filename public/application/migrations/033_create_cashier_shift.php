<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cashier shifts: opening float, payments taken by the cashier, counted amounts and variance at close.
 */
class Migration_Create_cashier_shift extends CI_Migration {

    public function up()
    {
        if (!$this->db->table_exists('cashier_shift')) {
            $this->db->query("
                CREATE TABLE `cashier_shift` (
                    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
                    `company_id` BIGINT(20) NOT NULL,
                    `user_id` BIGINT(20) NOT NULL,
                    `status` VARCHAR(10) NOT NULL DEFAULT 'open',
                    `opened_at` DATETIME NOT NULL,
                    `closed_at` DATETIME NULL DEFAULT NULL,
                    `closed_by` BIGINT(20) NULL DEFAULT NULL,
                    `opening_float` DECIMAL(12,2) NOT NULL DEFAULT 0,
                    `expected_json` TEXT NULL,
                    `counted_json` TEXT NULL,
                    `expected_total` DECIMAL(12,2) NULL DEFAULT NULL,
                    `counted_total` DECIMAL(12,2) NULL DEFAULT NULL,
                    `variance` DECIMAL(12,2) NULL DEFAULT NULL,
                    `notes` TEXT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_cashier_shift_company_user_status` (`company_id`, `user_id`, `status`),
                    KEY `idx_cashier_shift_company_opened` (`company_id`, `opened_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8
            ");
        }

        if ($this->db->table_exists('menu')) {
            $parent = $this->db->query("SELECT id FROM menu WHERE link = 'reports' AND parent_id = 0 AND partner_type_id = 1 LIMIT 1")->row_array();
            $exists = $this->db->query("SELECT id FROM menu WHERE link = 'reports/cashier_shift' LIMIT 1")->row_array();
            if ($parent && !$exists) {
                $this->db->insert('menu', array(
                    'name' => 'cashier shifts',
                    'link' => 'reports/cashier_shift',
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
            $this->db->where('link', 'reports/cashier_shift')->delete('menu');
        }
        $this->dbforge->drop_table('cashier_shift', TRUE);
    }
}
