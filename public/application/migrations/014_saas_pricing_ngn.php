<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Saas_pricing_ngn extends CI_Migration {

    public function up()
    {
        if (!$this->db->table_exists('saas_pricing_tier')) {
            return;
        }

        $tiers = array(
            'Starter' => array(
                'monthly_price' => 75000.00,
                'currency' => 'NGN',
                'features_json' => json_encode(array(
                    'Front desk calendar',
                    'Online booking engine',
                    'Basic reporting',
                    'Email support',
                )),
            ),
            'Growth' => array(
                'monthly_price' => 150000.00,
                'currency' => 'NGN',
                'features_json' => json_encode(array(
                    'Channel manager connections',
                    'Payment gateway integration',
                    'Revenue management tools',
                )),
            ),
            'Professional' => array(
                'monthly_price' => 270000.00,
                'currency' => 'NGN',
                'features_json' => json_encode(array(
                    'Multi-user permissions',
                    'Advanced analytics',
                    'Priority support',
                )),
            ),
            'Enterprise' => array(
                'monthly_price' => 450000.00,
                'currency' => 'NGN',
                'features_json' => json_encode(array(
                    'Custom integrations',
                    'Dedicated onboarding',
                    'SLA support',
                )),
            ),
        );

        foreach ($tiers as $name => $row) {
            $this->db->where('name', $name);
            $this->db->update('saas_pricing_tier', $row);
        }
    }

    public function down()
    {
        if (!$this->db->table_exists('saas_pricing_tier')) {
            return;
        }

        $tiers = array(
            'Starter' => array(
                'monthly_price' => 49.00,
                'currency' => 'USD',
                'features_json' => json_encode(array(
                    'Front desk calendar',
                    'Online booking engine',
                    'Basic reporting',
                    'Email support',
                )),
            ),
            'Growth' => array(
                'monthly_price' => 99.00,
                'currency' => 'USD',
                'features_json' => json_encode(array(
                    'Everything in Starter',
                    'Channel manager connections',
                    'Payment gateway integration',
                    'Revenue management tools',
                )),
            ),
            'Professional' => array(
                'monthly_price' => 179.00,
                'currency' => 'USD',
                'features_json' => json_encode(array(
                    'Everything in Growth',
                    'Multi-user permissions',
                    'Advanced analytics',
                    'Priority support',
                )),
            ),
            'Enterprise' => array(
                'monthly_price' => 299.00,
                'currency' => 'USD',
                'features_json' => json_encode(array(
                    'Everything in Professional',
                    'Custom integrations',
                    'Dedicated onboarding',
                    'SLA support',
                )),
            ),
        );

        foreach ($tiers as $name => $row) {
            $this->db->where('name', $name);
            $this->db->update('saas_pricing_tier', $row);
        }
    }
}
