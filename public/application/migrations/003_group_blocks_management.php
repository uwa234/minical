<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Group_blocks_management extends CI_Migration {

    public function up()
    {
        if (!$this->db->field_exists('billing_mode', 'booking_linked_group')) {
            $this->dbforge->add_column('booking_linked_group', array(
                'billing_mode' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => FALSE,
                    'default' => 'room',
                ),
                'master_customer_id' => array(
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'null' => TRUE,
                ),
            ));
        }

        if (!$this->db->field_exists('billed_to_group_id', 'charge')) {
            $this->dbforge->add_column('charge', array(
                'billed_to_group_id' => array(
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'null' => TRUE,
                ),
                'route_to_master' => array(
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => FALSE,
                    'default' => 0,
                ),
            ));
        }

        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'company_id' => array(
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => TRUE,
                'null' => FALSE,
            ),
            'name' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => FALSE,
            ),
            'booking_group_id' => array(
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => TRUE,
                'null' => TRUE,
            ),
            'account_customer_id' => array(
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => TRUE,
                'null' => TRUE,
            ),
            'check_in_date' => array(
                'type' => 'DATE',
                'null' => FALSE,
            ),
            'check_out_date' => array(
                'type' => 'DATE',
                'null' => FALSE,
            ),
            'cutoff_date' => array(
                'type' => 'DATE',
                'null' => TRUE,
            ),
            'release_date' => array(
                'type' => 'DATE',
                'null' => TRUE,
            ),
            'status' => array(
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => FALSE,
                'default' => 'active',
            ),
            'notes' => array(
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
        $this->dbforge->add_key('booking_group_id');
        $this->dbforge->create_table('inventory_block', TRUE);

        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'inventory_block_id' => array(
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => TRUE,
                'null' => FALSE,
            ),
            'room_type_id' => array(
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => TRUE,
                'null' => FALSE,
            ),
            'quantity' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => FALSE,
                'default' => 1,
            ),
            'picked_up' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => FALSE,
                'default' => 0,
            ),
            'rate' => array(
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => TRUE,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('inventory_block_id');
        $this->dbforge->create_table('inventory_block_line', TRUE);

        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'inventory_block_line_id' => array(
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => TRUE,
                'null' => FALSE,
            ),
            'booking_id' => array(
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => TRUE,
                'null' => FALSE,
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key(array('inventory_block_line_id', 'booking_id'));
        $this->dbforge->create_table('block_pickup', TRUE);

        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'booking_group_id' => array(
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => TRUE,
                'null' => FALSE,
            ),
            'booking_id' => array(
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => TRUE,
                'null' => TRUE,
            ),
            'room_type_id' => array(
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => TRUE,
                'null' => TRUE,
            ),
            'guest_name' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => TRUE,
            ),
            'guest_customer_id' => array(
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => TRUE,
                'null' => TRUE,
            ),
            'sort_order' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => FALSE,
                'default' => 0,
            ),
            'notes' => array(
                'type' => 'VARCHAR',
                'constraint' => 500,
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
        $this->dbforge->add_key('booking_group_id');
        $this->dbforge->create_table('group_rooming_entry', TRUE);
    }

    public function down()
    {
        $this->dbforge->drop_table('group_rooming_entry', TRUE);
        $this->dbforge->drop_table('block_pickup', TRUE);
        $this->dbforge->drop_table('inventory_block_line', TRUE);
        $this->dbforge->drop_table('inventory_block', TRUE);

        if ($this->db->field_exists('route_to_master', 'charge')) {
            $this->dbforge->drop_column('charge', 'route_to_master');
        }
        if ($this->db->field_exists('billed_to_group_id', 'charge')) {
            $this->dbforge->drop_column('charge', 'billed_to_group_id');
        }
        if ($this->db->field_exists('master_customer_id', 'booking_linked_group')) {
            $this->dbforge->drop_column('booking_linked_group', 'master_customer_id');
        }
        if ($this->db->field_exists('billing_mode', 'booking_linked_group')) {
            $this->dbforge->drop_column('booking_linked_group', 'billing_mode');
        }
    }
}
