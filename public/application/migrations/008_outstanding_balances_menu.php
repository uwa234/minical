<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Outstanding_balances_menu extends CI_Migration {

    public function up()
    {
        $submenus = array(
            array(
                'name' => 'all guests',
                'link' => 'customer/show_customers',
            ),
            array(
                'name' => 'outstanding balances',
                'link' => 'customer/outstanding_balances',
            ),
        );

        foreach ($submenus as $item) {
            $exists = $this->db
                ->where('parent_id', 2)
                ->where('link', $item['link'])
                ->count_all_results('menu');

            if ($exists === 0) {
                $this->db->insert('menu', array(
                    'name' => $item['name'],
                    'link' => $item['link'],
                    'icon' => '',
                    'parent_id' => 2,
                    'partner_type_id' => 1,
                ));
            }
        }
    }

    public function down()
    {
        $this->db
            ->where('parent_id', 2)
            ->where_in('link', array('customer/show_customers', 'customer/outstanding_balances'))
            ->delete('menu');
    }
}
