<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Idempotency key for external POS / integration charges (e.g. Odoo room posting).
 */
class Migration_Charge_external_reference extends CI_Migration {

    public function up()
    {
        if (!$this->db->table_exists('charge')) {
            return;
        }

        if (!$this->db->field_exists('external_reference', 'charge')) {
            $this->dbforge->add_column('charge', array(
                'external_reference' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 191,
                    'null' => TRUE,
                    'default' => NULL,
                    'after' => 'description',
                ),
            ));
        }

        // Empty string is not NULL — unique index would fail on duplicate ''.
        $this->db->query("UPDATE `charge` SET `external_reference` = NULL WHERE `external_reference` = ''");

        // Unique among non-NULL values (MySQL allows multiple NULLs).
        $index_exists = $this->db->query("SHOW INDEX FROM `charge` WHERE Key_name = 'uniq_charge_external_reference'");
        if ($index_exists && $index_exists->num_rows() === 0) {
            $this->db->query('ALTER TABLE `charge` ADD UNIQUE INDEX `uniq_charge_external_reference` (`external_reference`)');
        }
    }

    public function down()
    {
        if (!$this->db->table_exists('charge')) {
            return;
        }

        $index_exists = $this->db->query("SHOW INDEX FROM `charge` WHERE Key_name = 'uniq_charge_external_reference'");
        if ($index_exists && $index_exists->num_rows() > 0) {
            $this->db->query('ALTER TABLE `charge` DROP INDEX `uniq_charge_external_reference`');
        }

        if ($this->db->field_exists('external_reference', 'charge')) {
            $this->dbforge->drop_column('charge', 'external_reference');
        }
    }
}
