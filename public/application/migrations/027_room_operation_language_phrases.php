<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Seed translation phrases for guest room move, room exchange, and unassign room UI.
 */
class Migration_Room_operation_language_phrases extends CI_Migration {

	private $phrases = array(
		'guest_room_move' => 'Change room',
		'room_exchange' => 'Room exchange',
		'unassign_room' => 'Unassign room',
		'room_exchange_inhouse_only' => 'Room exchange is only available for checked-in guests.',
		'room_exchange_requires_assigned_rooms' => 'Both guests must have a room assigned to exchange.',
		'room_exchange_same_room' => 'Both guests are already in the same room.',
		'room_exchange_success' => 'Rooms exchanged successfully.',
		'room_exchange_log' => 'Room exchanged with booking',
		'room_exchange_with' => 'Exchange room with',
		'room_exchange_no_candidates' => 'No other checked-in guests with an assigned room are available to exchange with.',
		'select_room_for_move' => 'Select new room',
		'select_rate_plan_for_move' => 'Select rate plan',
		'update_folio_rates' => 'Update folio rates for remaining nights',
		'unassign_room_success' => 'Room unassigned successfully.',
		'unassign_room_already' => 'This stay has no room assigned.',
		'unassign_room_log' => 'Room unassigned:',
		'unassign_room_confirm' => 'Remove the room assignment for this guest? The stay will appear on the unassigned row.',
		'room_move_success' => 'Guest moved to the new room successfully.',
	);

	public function up()
	{
		foreach ($this->phrases as $keyword => $phrase) {
			$this->_insert_phrase($keyword, $phrase);
		}

		$this->db->set('version', 'version + 1', FALSE);
		$this->db->update('language');
	}

	public function down()
	{
		foreach (array_keys($this->phrases) as $keyword) {
			$row = $this->db->get_where('language_phrase', array('phrase_keyword' => $keyword))->row_array();
			if (!$row) {
				continue;
			}
			$this->db->delete('language_translation', array('phrase_id' => $row['id']));
			$this->db->delete('language_phrase', array('id' => $row['id']));
		}
	}

	private function _insert_phrase($keyword, $phrase, $language_id = 1)
	{
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
			'language_id' => $language_id,
		))->row_array();

		if (!$translation) {
			$this->db->insert('language_translation', array(
				'phrase_id' => $phrase_id,
				'language_id' => $language_id,
				'phrase' => $phrase,
			));
			return;
		}

		if (empty($translation['phrase'])) {
			$this->db->where('id', $translation['id']);
			$this->db->update('language_translation', array('phrase' => $phrase));
		}
	}
}
