<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_company_payment_table extends CI_Migration {

    public function up()
    {
        if ($this->db->table_exists('company_payment')) {
            return;
        }

        $this->dbforge->add_field(array(
            'company_payment_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ),
            'company_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ),
            'description' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ),
            'amount' => array(
                'type' => 'DECIMAL',
                'constraint' => '20,2',
                'null' => false,
                'default' => '0.00',
            ),
            'date' => array(
                'type' => 'DATE',
                'null' => true,
            ),
            'is_deleted' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0,
            ),
        ));

        $this->dbforge->add_key('company_payment_id', true);
        $this->dbforge->add_key('company_id');
        $this->dbforge->create_table('company_payment', true);
    }

    public function down()
    {
        if ($this->db->table_exists('company_payment')) {
            $this->dbforge->drop_table('company_payment', true);
        }
    }
}
