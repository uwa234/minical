<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Whatsapp_booking_management extends CI_Migration {

    public function up()
    {
        if (!$this->db->table_exists('whatsapp_x_company')) {
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'auto_increment' => TRUE,
                ),
                'company_id' => array(
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'null' => FALSE,
                ),
                'phone_number_id' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => TRUE,
                ),
                'business_phone_display' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'null' => TRUE,
                ),
                'access_token' => array(
                    'type' => 'TEXT',
                    'null' => TRUE,
                ),
                'webhook_verify_token' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 128,
                    'null' => TRUE,
                ),
                'demo_mode' => array(
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ),
                'is_active' => array(
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ),
                'bank_name' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 128,
                    'null' => TRUE,
                ),
                'bank_account_name' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 128,
                    'null' => TRUE,
                ),
                'bank_account_number' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => TRUE,
                ),
                'payment_link_base_url' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 512,
                    'null' => TRUE,
                ),
                'notify_email' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => TRUE,
                ),
                'enable_arrival_reminders' => array(
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ),
                'enable_checkout_followup' => array(
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ),
                'welcome_message' => array(
                    'type' => 'TEXT',
                    'null' => TRUE,
                ),
                'created_at' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,
                ),
                'updated_at' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,
                ),
            ));
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->add_key('company_id');
            $this->dbforge->create_table('whatsapp_x_company', TRUE);
        }

        if (!$this->db->table_exists('whatsapp_conversations')) {
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'auto_increment' => TRUE,
                ),
                'company_id' => array(
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'null' => FALSE,
                ),
                'guest_phone' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'null' => FALSE,
                ),
                'state' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'default' => 'idle',
                ),
                'context_data' => array(
                    'type' => 'TEXT',
                    'null' => TRUE,
                ),
                'booking_id' => array(
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'null' => TRUE,
                ),
                'last_message_at' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,
                ),
                'created_at' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,
                ),
                'updated_at' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,
                ),
            ));
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->add_key(array('company_id', 'guest_phone'));
            $this->dbforge->create_table('whatsapp_conversations', TRUE);
        }

        if (!$this->db->table_exists('whatsapp_messages')) {
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'auto_increment' => TRUE,
                ),
                'company_id' => array(
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'null' => FALSE,
                ),
                'conversation_id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'null' => FALSE,
                ),
                'direction' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 8,
                    'default' => 'in',
                ),
                'body' => array(
                    'type' => 'TEXT',
                    'null' => TRUE,
                ),
                'wa_message_id' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 128,
                    'null' => TRUE,
                ),
                'created_at' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,
                ),
            ));
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->add_key('conversation_id');
            $this->dbforge->create_table('whatsapp_messages', TRUE);
        }

        if (!$this->db->table_exists('whatsapp_x_booking')) {
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'auto_increment' => TRUE,
                ),
                'company_id' => array(
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'null' => FALSE,
                ),
                'booking_id' => array(
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'null' => FALSE,
                ),
                'conversation_id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'null' => TRUE,
                ),
                'payment_reference' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => TRUE,
                ),
                'payment_status' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => 'pending',
                ),
                'created_at' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,
                ),
                'updated_at' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,
                ),
            ));
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->add_key(array('company_id', 'booking_id'));
            $this->dbforge->create_table('whatsapp_x_booking', TRUE);
        }

        $extension_name = 'whatsapp-booking-management';
        $exists = $this->db
            ->where('extension_name', $extension_name)
            ->where('vendor_id', 0)
            ->count_all_results('extensions_x_vendor');

        if ($exists === 0) {
            $this->db->insert('extensions_x_vendor', array(
                'extension_name' => $extension_name,
                'vendor_id' => 0,
                'is_installed' => 1,
            ));
        }
    }

    public function down()
    {
        $this->dbforge->drop_table('whatsapp_x_booking', TRUE);
        $this->dbforge->drop_table('whatsapp_messages', TRUE);
        $this->dbforge->drop_table('whatsapp_conversations', TRUE);
        $this->dbforge->drop_table('whatsapp_x_company', TRUE);

        $this->db
            ->where('extension_name', 'whatsapp-booking-management')
            ->delete('extensions_x_vendor');
    }
}
