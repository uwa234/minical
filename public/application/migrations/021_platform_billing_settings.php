<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Platform_billing_settings extends CI_Migration {

    public function up()
    {
        if (!$this->db->table_exists('platform_settings')) {
            return;
        }

        $defaults = array(
            array('setting_key' => 'paid_plan_grace_days', 'setting_value' => '7'),
            array('setting_key' => 'default_currency', 'setting_value' => 'NGN'),
        );

        foreach ($defaults as $row) {
            $exists = $this->db->get_where('platform_settings', array('setting_key' => $row['setting_key']))->row_array();
            if (!$exists) {
                $this->db->insert('platform_settings', $row);
            }
        }
    }

    public function down()
    {
        if (!$this->db->table_exists('platform_settings')) {
            return;
        }

        $this->db->where_in('setting_key', array('paid_plan_grace_days', 'default_currency'));
        $this->db->delete('platform_settings');
    }
}
