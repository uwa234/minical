<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_company_log extends CI_Migration {

    public function up()
    {
        if ($this->db->table_exists('company_log')) {
            return;
        }

        $this->dbforge->add_field(array(
            'company_log_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ),
            'company_id' => array(
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ),
            'date_time' => array(
                'type' => 'DATETIME',
                'null' => false,
            ),
            'user_id' => array(
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ),
            'log' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
            'log_type' => array(
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => false,
                'default' => 'note',
            ),
        ));

        $this->dbforge->add_key('company_log_id', true);
        $this->dbforge->add_key('company_id');
        $this->dbforge->add_key('date_time');
        $this->dbforge->create_table('company_log', true);
    }

    public function down()
    {
        if ($this->db->table_exists('company_log')) {
            $this->dbforge->drop_table('company_log', true);
        }
    }
}
