<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Add discount_scope: apply fixed discount per night or on stay total.
 */
class Migration_Booking_discount_scope extends CI_Migration {

	public function up()
	{
		if (!$this->db->field_exists('discount_scope', 'booking')) {
			$this->dbforge->add_column('booking', array(
				'discount_scope' => array(
					'type' => 'TINYINT',
					'constraint' => 1,
					'unsigned' => TRUE,
					'null' => FALSE,
					'default' => '0',
				),
			));
		}
	}

	public function down()
	{
		if ($this->db->field_exists('discount_scope', 'booking')) {
			$this->dbforge->drop_column('booking', 'discount_scope');
		}
	}
}
