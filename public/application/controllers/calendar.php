<?php

require_once APPPATH . 'traits/Booking_room_operations_trait.php';

class Calendar extends MY_Controller
{
	use Booking_room_operations_trait;

	function __construct()
	{
		parent::__construct();
		
		$this->load->model(array(
			'Booking_room_history_model',
			'Booking_model',
			'Rate_plan_model',
			'Rate_model',
			'Date_range_model',
			'Room_model',
			'Room_type_model',
		));
		
		$global_data['js_files'] = array( 
			base_url() . auto_version('js/channel_manager/channel_manager.js'),
			base_url() . 'js/calendar/_loader.js',
			base_url() . 'js/calendar.js'
		);

		$this->load->vars($global_data);
		
	}

	// called from calendar.js
	// update by manipulating booking blocks
	function resize_booking_room_history()
	{
		$booking_id = $this->input->post('booking_id');
		$room_id = $this->input->post('room_id');
		$state = $this->input->post('state');
		$start1 = urldecode($this->input->post('start1'));
		$end1 = urldecode($this->input->post('end1'));
		$start2 = urldecode($this->input->post('start2'));
		$end2 = urldecode($this->input->post('end2'));

		// check for date integrity. If not permitted, print error
		$latest_booking_room_history_data = $this->Booking_room_history_model->get_latest_booking_room_history($booking_id);

		// Ensure that the block that's being resized is the latest booking_room_history row		
		if (
			($latest_booking_room_history_data['booking_id'] == $booking_id AND
			$latest_booking_room_history_data['room_id'] == $room_id AND
			$latest_booking_room_history_data['check_in_date'] == $start1)
			AND !$this->Booking_model->check_overbooking(date('Y-m-d H:i:s', strtotime($this->input->post('start1'))), date('Y-m-d H:i:s', strtotime($this->input->post('end2'))), $room_id, $booking_id)
		) {

			$booking_room_history_data['booking_id'] = $booking_id;
			$booking_room_history_data['room_id'] = trim($room_id);
			$booking_room_history_data['check_in_date'] = $start2;
			
			$destination = array(
					'booking_id' => $booking_id,
					'room_id' => trim($room_id),
					'check_in_date' => $start2,
					'check_out_date' => $end2
				);
			$changes = $this->_generate_log_of_differences_between_new_booking_data_and_database($booking_id, $destination);
			$this->_create_booking_log($booking_id, $changes);

			$this->Booking_room_history_model->update_check_out_date($booking_room_history_data, $end2);
			$this->_update_booking_rate_plan($booking_id, $destination, $end1);
			$this->Booking_model->update_booking_balance($booking_id);
            
			echo l("success",true);
			return;
		}
		echo l("Warning: Selected cannot be modified",true);
		return;
	}
	
	
	// called from calendar.js
	// update by manipulating booking blocks
	/**
	* @param string $booking_id
	* @param string $room_id
	* @param string $state state of booking
	* @param string $start1 check-in date of booking before the move
	* @param string $end1 check-out date of booking before the move
	* @param string $start2 check-in date of booking after the move
	* @param string $end2	check-out date of booking after the move
	*/
	function move_booking_room_history()
	{

        $booking_id = $this->input->post('booking_id');
		$room_id = $this->input->post('room_id');
		$state = $this->input->post('state');
		$start1 = urldecode($this->input->post('start1'));
		$end1 = urldecode($this->input->post('end1'));
		$start2 = urldecode($this->input->post('start2'));
		$end2 = urldecode($this->input->post('end2'));

        $this->load->model('Booking_model');
        
		// check for date integrity. If not permitted, print error
		$error_message = "";
	/*
		if ($state == RESERVATION && $start2 < $this->selling_date) {
			$error_message = "Warning: Reservation cannot start before today";			
		}
	*/
		if ($state == INHOUSE && $start1 != $start2) {
			//echo "$start1, $end1, $start2, $end2";
			$error_message = l("Warning: In-house guest's check-in date cannot change", true);			
		}
		elseif ($state == CHECKOUT) {
			$error_message = l("Warning: Already checked-out booking cannot be modified", true);
		}
		elseif ($this->Booking_room_history_model->check_if_booking_exists_between_two_dates($room_id, $start2, $end2, $booking_id)) {
			$error_message = l("Warning: This room is already occupied", true);
		}	
		if ($error_message != "")
		{
			echo $error_message;
			return true;
		}
		
		$latest_booking_room_history_data = $this->Booking_room_history_model->get_latest_booking_room_history($booking_id, true);
		
		//print_r($latest_booking_room_history_data);
		//echo $latest_booking_room_history_data['booking_id'].", ".$latest_booking_room_history_data['room_id']."<br/>";
		
		// Ensure that the block that's being moved is the latest booking_room_history row 
		// (in other words, make sure the block is the tail-most block
		// The reason we do this is because...?
		if ($latest_booking_room_history_data['booking_id'] == $booking_id AND
			$latest_booking_room_history_data['check_in_date'] == $start1
			)
		{
			
			$duration_of_stay = strtotime($latest_booking_room_history_data['check_out_date']) - strtotime($latest_booking_room_history_data['check_in_date']);
			
			// check out date is based on duration of stay and check-in date, because $end2 isn't necessarily correct check-out-date 
			// (i.e. short stays with same check-in & check-out date passes $end2 as check-out-date +1 day)
			$destination = array(
					'booking_id' => $booking_id,
					'room_id' => trim($room_id),
					'check_in_date' => $start2,
					'check_out_date' => $this->enable_new_calendar ? $end2 : Date("Y-m-d", (strtotime($start2) + $duration_of_stay))
				);

			
			$changes = $this->_generate_log_of_differences_between_new_booking_data_and_database($booking_id, $destination);
			$this->_create_booking_log($booking_id, $changes);
			
			$this->Booking_room_history_model->update_booking_room_history($latest_booking_room_history_data, $destination);		
			
			$this->_combine_booking_blocks($booking_id);

			if(
				isset($latest_booking_room_history_data['is_ota_booking']) &&
				$latest_booking_room_history_data['is_ota_booking'] != 1
			) {
				$this->_update_booking_rate_plan($booking_id, $destination);
			}
            
            $this->Booking_model->update_booking_balance($booking_id);
            
			echo "success";
			return true;
			
			
		}
		echo l("Warning: Selected cannot be modified",true);
		return;
	}
	
	function _generate_log_of_differences_between_new_booking_data_and_database($booking_id, $new_data) 
	{
		// Load existing_data from database
		$this->load->model('Booking_room_history_model');
		$booking_room_history_data = $this->Booking_room_history_model->get_booking_detail($booking_id);		
		$this->load->model('Room_model');
		$existing_room_data = $this->Room_model->get_room($booking_room_history_data['room_id']);		
		$new_data = $new_data + $this->Room_model->get_room($new_data['room_id']);		
		
		$existing_data = $booking_room_history_data + $existing_room_data;

		$field_array = array(
			'check_in_date' => 'Check-in date',
			'check_out_date' => 'Check-out date',
			'room_name' => 'Room name'
		);
		
		$log_array = array();

		// Find the differences between existing data and new data.
		// and returns a <br /> seperated string
		foreach (array_keys($field_array) as $field)
		{	 
			if (isset($existing_data[$field]))
			{	
				if (isset($new_data[$field]) )
				{
					if ($existing_data[$field] != $new_data[$field])
					{
						$log_array[] = $field_array[$field]." changed from ".$existing_data[$field]." to ".$new_data[$field];
					}
				}
			}
		}
		
		return implode("<br/>", $log_array);
	}
		
	// create booking log in the database
	// by default, the log is considered to be automatically generated from events that happens in booking
	// such as: check-in, check-out, create/cancel reservation, and other changes in booking information
	function _create_booking_log($booking_id, $log, $log_type=USER_LOG) 
	{
		$log_data['selling_date'] = $this->selling_date;
		$log_data['user_id'] = $this->user_id;
		$log_data['booking_id'] = $booking_id;
		$log_data['log_type'] = $log_type;
		$log_data['date_time'] = gmdate('Y-m-d H:i:s');
		$log_data['log'] = $log;
		
		$this->load->model('Booking_log_model');
		$this->Booking_log_model->insert_log($log_data);
	}
}
