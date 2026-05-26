<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Platform_saas_settings extends CI_Migration {

    public function up()
    {
        if (!$this->db->table_exists('platform_settings')) {
            $this->dbforge->add_field(array(
                'setting_key' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                ),
                'setting_value' => array(
                    'type' => 'TEXT',
                    'null' => TRUE,
                ),
            ));
            $this->dbforge->add_key('setting_key', TRUE);
            $this->dbforge->create_table('platform_settings', TRUE);
        }

        if (!$this->db->table_exists('saas_pricing_tier')) {
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'auto_increment' => TRUE,
                ),
                'name' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => FALSE,
                ),
                'min_rooms' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'default' => 1,
                ),
                'max_rooms' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'null' => TRUE,
                ),
                'monthly_price' => array(
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => '0.00',
                ),
                'currency' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 8,
                    'default' => 'USD',
                ),
                'subscription_level' => array(
                    'type' => 'TINYINT',
                    'constraint' => 4,
                    'default' => 0,
                ),
                'features_json' => array(
                    'type' => 'TEXT',
                    'null' => TRUE,
                ),
                'sort_order' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                ),
                'is_active' => array(
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ),
            ));
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table('saas_pricing_tier', TRUE);
        }

        $defaults = array(
            array('setting_key' => 'default_trial_days', 'setting_value' => '14'),
            array('setting_key' => 'marketing_hero_title', 'setting_value' => 'Hotel management software that grows with you'),
            array('setting_key' => 'marketing_hero_subtitle', 'setting_value' => 'Reservations, channel manager, payments, and revenue tools in one platform.'),
        );
        foreach ($defaults as $row) {
            $exists = $this->db->get_where('platform_settings', array('setting_key' => $row['setting_key']))->row_array();
            if (!$exists) {
                $this->db->insert('platform_settings', $row);
            }
        }

        if ($this->db->count_all('saas_pricing_tier') == 0) {
            $tiers = array(
                array(
                    'name' => 'Starter',
                    'min_rooms' => 1,
                    'max_rooms' => 20,
                    'monthly_price' => 49.00,
                    'currency' => 'USD',
                    'subscription_level' => 0,
                    'features_json' => json_encode(array(
                        'Front desk calendar',
                        'Online booking engine',
                        'Basic reporting',
                        'Email support',
                    )),
                    'sort_order' => 1,
                    'is_active' => 1,
                ),
                array(
                    'name' => 'Growth',
                    'min_rooms' => 21,
                    'max_rooms' => 50,
                    'monthly_price' => 99.00,
                    'currency' => 'USD',
                    'subscription_level' => 1,
                    'features_json' => json_encode(array(
                        'Everything in Starter',
                        'Channel manager connections',
                        'Payment gateway integration',
                        'Revenue management tools',
                    )),
                    'sort_order' => 2,
                    'is_active' => 1,
                ),
                array(
                    'name' => 'Professional',
                    'min_rooms' => 51,
                    'max_rooms' => 100,
                    'monthly_price' => 179.00,
                    'currency' => 'USD',
                    'subscription_level' => 1,
                    'features_json' => json_encode(array(
                        'Everything in Growth',
                        'Multi-user permissions',
                        'Advanced analytics',
                        'Priority support',
                    )),
                    'sort_order' => 3,
                    'is_active' => 1,
                ),
                array(
                    'name' => 'Enterprise',
                    'min_rooms' => 101,
                    'max_rooms' => NULL,
                    'monthly_price' => 299.00,
                    'currency' => 'USD',
                    'subscription_level' => 2,
                    'features_json' => json_encode(array(
                        'Everything in Professional',
                        'Custom integrations',
                        'Dedicated onboarding',
                        'SLA support',
                    )),
                    'sort_order' => 4,
                    'is_active' => 1,
                ),
            );
            foreach ($tiers as $tier) {
                $this->db->insert('saas_pricing_tier', $tier);
            }
        }
    }

    public function down()
    {
        $this->dbforge->drop_table('saas_pricing_tier', TRUE);
        $this->dbforge->drop_table('platform_settings', TRUE);
    }
}
