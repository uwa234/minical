<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Payment_gateways_menu extends CI_Migration {

    public function up()
    {
        $exists = $this->db
            ->where('parent_id', 30)
            ->where('link', 'settings/accounting/payment_gateways')
            ->count_all_results('menu');

        if ($exists === 0) {
            $this->db->insert('menu', array(
                'name' => 'payment gateways',
                'link' => 'settings/accounting/payment_gateways',
                'icon' => '',
                'parent_id' => 30,
                'partner_type_id' => 1,
            ));
        }
    }

    public function down()
    {
        $this->db
            ->where('parent_id', 30)
            ->where('link', 'settings/accounting/payment_gateways')
            ->delete('menu');
    }
}
