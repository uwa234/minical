<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Align Starter SaaS tier with legacy STARTER subscription gating and seed route restrictions.
 */
class Migration_Saas_starter_plan_gating extends CI_Migration {

    public function up()
    {
        if ($this->db->table_exists('saas_pricing_tier')) {
            $this->db->where('name', 'Starter');
            $this->db->update('saas_pricing_tier', array('subscription_level' => (int) STARTER));
        }

        if ($this->db->table_exists('company_subscription')) {
            $rows = $this->db->get('company_subscription')->result_array();
            foreach ($rows as $row) {
                if (empty($row['meta_data'])) {
                    continue;
                }
                $meta = json_decode($row['meta_data'], true);
                if (!is_array($meta)) {
                    continue;
                }
                $tier_name = isset($meta['tier_name']) ? trim((string) $meta['tier_name']) : '';
                $tier_id = isset($meta['tier_id']) ? (int) $meta['tier_id'] : 0;
                if (strcasecmp($tier_name, 'Starter') !== 0 && $tier_id !== 1) {
                    continue;
                }
                $this->db->where('company_id', (int) $row['company_id']);
                $this->db->update('company_subscription', array(
                    'subscription_level' => (int) STARTER,
                    'limit_feature' => 1,
                ));
            }
        }

        if (!$this->db->table_exists('subscription_restriction')) {
            return;
        }

        if ($this->db->count_all('subscription_restriction') > 0) {
            return;
        }

        $starter_plan = (int) STARTER;
        $restricted_controllers = array(
            'revenue_management',
            'channel_manager',
            'channels',
            'extensions',
            'groups',
        );

        foreach ($restricted_controllers as $controller) {
            $this->db->insert('subscription_restriction', array(
                'subscription_plan' => $starter_plan,
                'controller' => $controller,
                'function' => '',
            ));
        }
    }

    public function down()
    {
        if ($this->db->table_exists('saas_pricing_tier')) {
            $this->db->where('name', 'Starter');
            $this->db->update('saas_pricing_tier', array('subscription_level' => 0));
        }

        if ($this->db->table_exists('subscription_restriction')) {
            $this->db->where('subscription_plan', (int) STARTER);
            $this->db->where_in('controller', array(
                'revenue_management',
                'channel_manager',
                'channels',
                'extensions',
                'groups',
            ));
            $this->db->delete('subscription_restriction');
        }
    }
}
