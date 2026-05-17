<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_channel_ical extends CI_Migration {

    public function up()
    {
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'company_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => FALSE,
            ),
            'channel_key' => array(
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => FALSE,
                'default' => 'booking_dot_com',
            ),
            'room_type_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => FALSE,
            ),
            'import_url' => array(
                'type' => 'TEXT',
                'null' => TRUE,
            ),
            'export_token' => array(
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => FALSE,
            ),
            'import_enabled' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => FALSE,
                'default' => 0,
            ),
            'export_enabled' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => FALSE,
                'default' => 0,
            ),
            'last_import_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
            'last_import_message' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
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
        $this->dbforge->add_key(array('company_id', 'channel_key', 'room_type_id'), FALSE, TRUE);
        $this->dbforge->add_key('export_token', FALSE, TRUE);
        $this->dbforge->create_table('channel_ical_mapping', TRUE);

        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'company_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => FALSE,
            ),
            'channel_key' => array(
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => FALSE,
            ),
            'room_type_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => FALSE,
            ),
            'ical_uid' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => FALSE,
            ),
            'check_in_date' => array(
                'type' => 'DATE',
                'null' => FALSE,
            ),
            'check_out_date' => array(
                'type' => 'DATE',
                'null' => FALSE,
            ),
            'summary' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => TRUE,
            ),
            'updated_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key(array('company_id', 'channel_key', 'ical_uid'), FALSE, TRUE);
        $this->dbforge->create_table('channel_ical_busy_period', TRUE);
    }

    public function down()
    {
        $this->dbforge->drop_table('channel_ical_busy_period', TRUE);
        $this->dbforge->drop_table('channel_ical_mapping', TRUE);
    }
}
