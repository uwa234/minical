<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rebrand default SaaS whitelabel partner from Minical to Veurion.
 */
class Migration_Veurion_default_partner_branding extends CI_Migration {

    public function up()
    {
        if (!$this->db->table_exists('whitelabel_partner')) {
            return;
        }

        $this->db->query(
            "UPDATE `whitelabel_partner` SET `name` = 'Veurion', `username` = 'veurion'
             WHERE `id` = 0 OR `username` = 'minical' OR `name` = 'Minical'"
        );
    }

    public function down()
    {
        if (!$this->db->table_exists('whitelabel_partner')) {
            return;
        }

        $this->db->query(
            "UPDATE `whitelabel_partner` SET `name` = 'Minical', `username` = 'minical'
             WHERE `id` = 0 OR `username` = 'veurion'"
        );
    }
}
