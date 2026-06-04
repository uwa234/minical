<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Add optional percentage or fixed discount on room rate at reservation time.
 */
class Migration_Booking_discount extends CI_Migration {

	public function up()
	{
		if (!$this->db->field_exists('discount_type', 'booking')) {
			$this->dbforge->add_column('booking', array(
				'discount_type' => array(
					'type' => 'TINYINT',
					'constraint' => 1,
					'unsigned' => TRUE,
					'null' => FALSE,
					'default' => '0',
				),
			));
		}

		if (!$this->db->field_exists('discount_value', 'booking')) {
			$this->dbforge->add_column('booking', array(
				'discount_value' => array(
					'type' => 'DECIMAL',
					'constraint' => '10,2',
					'null' => FALSE,
					'default' => '0.00',
				),
			));
		}
	}

	public function down()
	{
		if ($this->db->field_exists('discount_value', 'booking')) {
			$this->dbforge->drop_column('booking', 'discount_value');
		}
		if ($this->db->field_exists('discount_type', 'booking')) {
			$this->dbforge->drop_column('booking', 'discount_type');
		}
	}
}
