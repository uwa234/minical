<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Revenue_management_menu extends CI_Migration {

    public function up()
    {
        $exists = $this->db
            ->where('link', 'revenue_management')
            ->count_all_results('menu');

        if ($exists === 0) {
            $this->db->insert('menu', array(
                'name' => 'revenue management',
                'link' => 'revenue_management',
                'icon' => 'metismenu-icon pe-7s-graph2',
                'parent_id' => 0,
                'partner_type_id' => 1,
            ));
        }
    }

    public function down()
    {
        $this->db
            ->where('link', 'revenue_management')
            ->delete('menu');
    }
}
