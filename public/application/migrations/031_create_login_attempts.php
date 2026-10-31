<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Brute-force protection table used by Tank_auth on every login attempt.
 * Missing table caused blank white screens after POST /auth/login (mysqli_sql_exception).
 */
class Migration_Create_login_attempts extends CI_Migration {

    public function up()
    {
        if ($this->db->table_exists('login_attempts')) {
            return;
        }

        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'ip_address' => array(
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => FALSE,
            ),
            'login' => array(
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => FALSE,
            ),
            'time' => array(
                'type' => 'TIMESTAMP',
                'null' => FALSE,
                // dbforge may omit DEFAULT CURRENT_TIMESTAMP on some drivers; set in raw SQL below if needed.
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('ip_address');
        $this->dbforge->add_key('login');
        $this->dbforge->create_table('login_attempts', TRUE);

        // Ensure timestamp default (Tank_auth inserts without an explicit time).
        $this->db->query('ALTER TABLE `login_attempts` MODIFY `time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    public function down()
    {
        if ($this->db->table_exists('login_attempts')) {
            $this->dbforge->drop_table('login_attempts');
        }
    }
}
