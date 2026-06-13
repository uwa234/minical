<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Add room exchange empty-candidates translation phrase.
 */
class Migration_Room_exchange_no_candidates_phrase extends CI_Migration {

	public function up()
	{
		$keyword = 'room_exchange_no_candidates';
		$phrase = 'No other checked-in guests with an assigned room are available to exchange with.';

		$row = $this->db->get_where('language_phrase', array('phrase_keyword' => $keyword))->row_array();
		if ($row) {
			$phrase_id = $row['id'];
		} else {
			$this->db->insert('language_phrase', array(
				'phrase_keyword' => $keyword,
				'created_at' => date('Y-m-d H:i:s'),
			));
			$phrase_id = $this->db->insert_id();
		}

		$translation = $this->db->get_where('language_translation', array(
			'phrase_id' => $phrase_id,
			'language_id' => 1,
		))->row_array();

		if (!$translation) {
			$this->db->insert('language_translation', array(
				'phrase_id' => $phrase_id,
				'language_id' => 1,
				'phrase' => $phrase,
			));
		} elseif (empty($translation['phrase'])) {
			$this->db->where('id', $translation['id']);
			$this->db->update('language_translation', array('phrase' => $phrase));
		}

		$this->db->set('version', 'version + 1', FALSE);
		$this->db->update('language');
	}

	public function down()
	{
		$row = $this->db->get_where('language_phrase', array('phrase_keyword' => 'room_exchange_no_candidates'))->row_array();
		if (!$row) {
			return;
		}
		$this->db->delete('language_translation', array('phrase_id' => $row['id']));
		$this->db->delete('language_phrase', array('id' => $row['id']));
	}
}
