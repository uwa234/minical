<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Increase minimum logo crop dimensions (was 200×100).
 */
class Migration_Increase_logo_image_dimensions extends CI_Migration {

	public function up()
	{
		$this->db->where('id', LOGO_IMAGE_TYPE_ID);
		$this->db->update('image_type', array(
			'width' => 800,
			'height' => 400,
		));
	}

	public function down()
	{
		$this->db->where('id', LOGO_IMAGE_TYPE_ID);
		$this->db->update('image_type', array(
			'width' => 200,
			'height' => 100,
		));
	}
}
