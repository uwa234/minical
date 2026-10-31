<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Indexes for the owner activity log (invoice_log and booking_log had none) and its menu entry.
 */
class Migration_Activity_log extends CI_Migration {

    private $indexes = array(
        'invoice_log' => array('idx_invoice_log_date_time' => 'date_time', 'idx_invoice_log_booking' => 'booking_id'),
        'booking_log' => array('idx_booking_log_date_time' => 'date_time', 'idx_booking_log_booking' => 'booking_id'),
    );

    public function up()
    {
        foreach ($this->indexes as $table => $indexes) {
            if (!$this->db->table_exists($table)) {
                continue;
            }
            foreach ($indexes as $name => $column) {
                $exists = $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$name}'");
                if ($exists && $exists->num_rows() === 0) {
                    $this->db->query("ALTER TABLE `{$table}` ADD INDEX `{$name}` (`{$column}`)");
                }
            }
        }

        if ($this->db->table_exists('menu')) {
            $parent = $this->db->query("SELECT id FROM menu WHERE link = 'reports' AND parent_id = 0 AND partner_type_id = 1 LIMIT 1")->row_array();
            $exists = $this->db->query("SELECT id FROM menu WHERE link = 'reports/activity_log' LIMIT 1")->row_array();
            if ($parent && !$exists) {
                $this->db->insert('menu', array(
                    'name' => 'activity log',
                    'link' => 'reports/activity_log',
                    'icon' => '',
                    'parent_id' => $parent['id'],
                    'partner_type_id' => 1,
                ));
            }
        }
    }

    public function down()
    {
        if ($this->db->table_exists('menu')) {
            $this->db->where('link', 'reports/activity_log')->delete('menu');
        }
        foreach ($this->indexes as $table => $indexes) {
            foreach ($indexes as $name => $column) {
                $exists = $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$name}'");
                if ($exists && $exists->num_rows() > 0) {
                    $this->db->query("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
                }
            }
        }
    }
}
