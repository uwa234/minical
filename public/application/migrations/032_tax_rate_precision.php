<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * tax_type.tax_rate was DECIMAL(10,0), so 7.5% VAT was stored as 8%.
 */
class Migration_Tax_rate_precision extends CI_Migration {

    public function up()
    {
        if (!$this->db->table_exists('tax_type')) {
            return;
        }

        $this->db->query("ALTER TABLE `tax_type` MODIFY `tax_rate` DECIMAL(10,4) NOT NULL DEFAULT 0");

        // Nigerian VAT entered as 7.5 was rounded to 8; restore it for NGN / Nigeria properties.
        $this->db->query("
            UPDATE `tax_type` tt
            JOIN `company` c ON c.company_id = tt.company_id
            LEFT JOIN `currency` cu ON cu.currency_id = c.default_currency_id
            SET tt.tax_rate = 7.5
            WHERE tt.tax_rate = 8
              AND tt.is_percentage = 1
              AND tt.is_brackets_active = 0
              AND tt.tax_type LIKE '%VAT%'
              AND (cu.currency_code = 'NGN' OR c.country LIKE '%nigeria%')
        ");
    }

    public function down()
    {
        if (!$this->db->table_exists('tax_type')) {
            return;
        }

        $this->db->query("ALTER TABLE `tax_type` MODIFY `tax_rate` DECIMAL(10,0) NOT NULL DEFAULT 0");
    }
}
