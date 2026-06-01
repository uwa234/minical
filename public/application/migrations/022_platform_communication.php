<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Platform_communication extends CI_Migration {

    public function up()
    {
        if (!$this->db->table_exists('platform_email_template')) {
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'auto_increment' => TRUE,
                ),
                'slug' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => FALSE,
                ),
                'name' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 128,
                    'null' => FALSE,
                ),
                'description' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => TRUE,
                ),
                'subject' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => FALSE,
                ),
                'body_html' => array(
                    'type' => 'TEXT',
                    'null' => FALSE,
                ),
                'is_active' => array(
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ),
                'updated_at' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,
                ),
            ));
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->add_key('slug', FALSE, TRUE);
            $this->dbforge->create_table('platform_email_template', TRUE);
        }

        if (!$this->db->table_exists('platform_announcement')) {
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'auto_increment' => TRUE,
                ),
                'title' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => FALSE,
                ),
                'body' => array(
                    'type' => 'TEXT',
                    'null' => FALSE,
                ),
                'severity' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 16,
                    'default' => 'info',
                ),
                'starts_at' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,
                ),
                'ends_at' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,
                ),
                'is_active' => array(
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                ),
                'dismissible' => array(
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ),
                'created_at' => array(
                    'type' => 'DATETIME',
                    'null' => FALSE,
                ),
                'updated_at' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,
                ),
            ));
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table('platform_announcement', TRUE);
        }

        $now = gmdate('Y-m-d H:i:s');
        $templates = array(
            array(
                'slug' => 'trial_outreach',
                'name' => 'Trial lead outreach',
                'description' => 'Outreach to trial sign-up leads before property creation',
                'subject' => 'Welcome to Veurion — let\'s get {property_name} set up',
                'body_html' => '<p>Hi {first_name},</p>'
                    . '<p>Thanks for your interest in Veurion for <strong>{property_name}</strong> ({room_count} rooms).</p>'
                    . '<p>We\'d love to help you start your free trial. Reply to this email or book a quick onboarding call and we\'ll create your property workspace.</p>'
                    . '<p>Best,<br>Veurion Team</p>',
            ),
            array(
                'slug' => 'payment_reminder',
                'name' => 'Payment reminder',
                'description' => 'Reminder for tenants with overdue subscription payment',
                'subject' => 'Payment reminder: {property_name} subscription',
                'body_html' => '<p>Hello,</p>'
                    . '<p>This is a reminder that your Veurion subscription for <strong>{property_name}</strong> requires payment.</p>'
                    . '<p>Due date: <strong>{due_date}</strong></p>'
                    . '<p>Estimated amount: <strong>{amount_due}</strong></p>'
                    . '<p><a href="{subscription_url}">Open your subscription page</a> to pay online or contact support if you have already sent a bank transfer.</p>'
                    . '<p>Thank you,<br>Veurion Support</p>',
            ),
            array(
                'slug' => 'win_back',
                'name' => 'Win-back (churned tenants)',
                'description' => 'Re-engage tenants who canceled or let their subscription lapse',
                'subject' => 'We\'d love to have {property_name} back on Veurion',
                'body_html' => '<p>Hello,</p>'
                    . '<p>We noticed your Veurion subscription for <strong>{property_name}</strong> is no longer active.</p>'
                    . '<p>If you\'d like to return, you can reactivate anytime from your subscription page or reply to this email — we\'re happy to help with onboarding or billing questions.</p>'
                    . '<p><a href="{subscription_url}">View subscription options</a></p>'
                    . '<p>Thank you,<br>Veurion Support</p>',
            ),
        );

        foreach ($templates as $tpl) {
            $exists = $this->db->get_where('platform_email_template', array('slug' => $tpl['slug']))->row_array();
            if (!$exists) {
                $this->db->insert('platform_email_template', array_merge($tpl, array(
                    'is_active' => 1,
                    'updated_at' => $now,
                )));
            }
        }

        if ($this->db->table_exists('platform_settings')) {
            $settings = array(
                array('setting_key' => 'webhook_url', 'setting_value' => ''),
                array('setting_key' => 'slack_webhook_url', 'setting_value' => ''),
                array('setting_key' => 'webhook_enabled_events', 'setting_value' => json_encode(array(
                    'trial_signup',
                    'payment_received',
                    'debtor_overdue',
                ))),
            );
            foreach ($settings as $row) {
                $exists = $this->db->get_where('platform_settings', array('setting_key' => $row['setting_key']))->row_array();
                if (!$exists) {
                    $this->db->insert('platform_settings', $row);
                }
            }
        }
    }

    public function down()
    {
        if ($this->db->table_exists('platform_email_template')) {
            $this->dbforge->drop_table('platform_email_template', TRUE);
        }
        if ($this->db->table_exists('platform_announcement')) {
            $this->dbforge->drop_table('platform_announcement', TRUE);
        }
        if ($this->db->table_exists('platform_settings')) {
            $this->db->where_in('setting_key', array('webhook_url', 'slack_webhook_url', 'webhook_enabled_events'));
            $this->db->delete('platform_settings');
        }
    }
}
