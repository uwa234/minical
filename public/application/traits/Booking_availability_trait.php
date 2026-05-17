<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Room availability endpoints extracted from the booking controller.
 */
trait Booking_availability_trait
{
    function get_available_room_types_in_JSON($check_in_date = null, $check_out_date = null, $isAJAX = true)
    {
        if(!$check_in_date && !$check_out_date)
        {
            $check_in_date = $this->input->post('check_in_date');
            $check_out_date =  $this->input->post('check_out_date');
            $isAJAX = $this->input->post('isAJAX');

            $check_in_date = urldecode($check_in_date);
            $check_out_date = urldecode($check_out_date);
        }

        $check_in_date = date('Y-m-d H:i:s', strtotime($check_in_date));
        $check_out_date = date('Y-m-d H:i:s', strtotime($check_out_date));
        if ($check_in_date <= $this->selling_date && $this->selling_date <= $check_out_date) {
            $check_in_date = $this->selling_date;
        }
        $company_data = $this->company_data;
        $force_room_selection = $company_data['force_room_selection'];

        $room_types_array = $this->Room_type_model->get_room_types_and_availabilities(
            $this->company_id,
            $check_in_date,
            $check_out_date
        );
        $available_room_types = $room_types_array['available_room_types'];
        $occupancies = $room_types_array['occupancies'];

        if(!$force_room_selection)
        {
            $room_types = array();
            $filters = array(
                'start_date' => $check_in_date,
                'end_date' => $check_out_date,
                'unassigned_bookings' => true,
                'state' => 'active'
            );
            $bookings = $this->Booking_model->get_bookings($filters, null, null ,true);
            foreach ($bookings as $booking) {
                if($booking['room_id'] && $booking['check_out_date'] > $check_in_date && $check_out_date > $booking['check_in_date'])
                {
                    $_room_type_id = $booking['r_room_type_id'] ? $booking['r_room_type_id'] : $booking['brh_room_type_id'];
                    if(!isset($room_types[$_room_type_id])) {
                        $room_types[$_room_type_id] = array();
                    }
                    if(!isset($room_types[$_room_type_id][$booking['room_id']])) {
                        $room_types[$_room_type_id][$booking['room_id']] = array();
                    }
                    $room_types[$_room_type_id][$booking['room_id']][] = $booking;
                }
            }
            foreach ($bookings as $booking) {
                if(!$booking['room_id'] && $booking['check_out_date'] > $check_in_date && $check_out_date > $booking['check_in_date'])
                {
                    if (isset($room_types[$booking['brh_room_type_id']]) && $room_types[$booking['brh_room_type_id']]) {
                        $overlapping_with_other_bookings = false;
                        foreach ($room_types[$booking['brh_room_type_id']] as $key => $room_bookings) {
                            foreach ($room_bookings as $room_booking)
                            {
                                if ($booking['check_out_date'] > $room_booking['check_in_date'] && $booking['check_in_date'] < $room_booking['check_out_date']) {
                                    $overlapping_with_other_bookings = true;
                                }
                            }
                        }

                        if($overlapping_with_other_bookings) {
                            $room_types[$booking['brh_room_type_id']][] = array($booking);
                        } else {
                            $room_types[$booking['brh_room_type_id']][$key][] = $booking;
                        }

                    } else {
                        $room_types[$booking['brh_room_type_id']][] = array($booking);
                    }
                }
            }

            foreach ($available_room_types as $key => $room_type) {
                $availability = $room_type['availability'];

                $unassigned_occupancy = isset($room_types[$room_type['id']]) ? count($room_types[$room_type['id']]) : 0;
                $availability = $availability - $unassigned_occupancy;

                $available_room_types[$key]['id']           = $room_type['id'];
                $available_room_types[$key]['availability'] = $availability > 0 ? $availability : 0;
            }
        }
        else
        {
            foreach ($available_room_types as $key => $room_type) {
                $availability = $room_type['availability'];
                foreach ($occupancies as $occupancy) {
                    if ($room_type['id'] == $occupancy['id']) {
                        $availability = $room_type['availability'] - $occupancy['occupancy'];
                    }
                }

                $available_room_types[$key]['id']           = $room_type['id'];
                $available_room_types[$key]['availability'] = $availability > 0 ? $availability : 0;
            }
        }
        if ($isAJAX) {
            echo json_encode($available_room_types);
            return;
        }

        return $available_room_types;
    }

    function get_available_rooms_in_AJAX($check_in_date = null, $check_out_date = null, $room_type_id = null, $booking_id = null, $room_id = null, $isAJAX = true)
    {
        $check_in_date = $check_in_date ? $check_in_date : sqli_clean($this->security->xss_clean($this->input->post('check_in_date', TRUE)));
        $check_out_date = $check_out_date ? $check_out_date : sqli_clean($this->security->xss_clean($this->input->post('check_out_date', TRUE)));
        $room_type_id = $room_type_id ? $room_type_id : sqli_clean($this->security->xss_clean($this->input->post('room_type_id', TRUE)));
        $booking_id = $booking_id ? $booking_id : sqli_clean($this->security->xss_clean($this->input->post('booking_id', TRUE)));
        $room_id = $room_id ? $room_id : sqli_clean($this->security->xss_clean($this->input->post('room_id', TRUE)));

        $check_in_date = date('Y-m-d H:i:s', strtotime($check_in_date));
        $check_out_date = date('Y-m-d H:i:s', strtotime($check_out_date));

        if ($check_in_date <= $this->selling_date." 00:00:00" && $this->selling_date." 00:00:00" <= $check_out_date) {
            $check_in_date = $this->selling_date." 00:00:00";
        }

        $available_rooms = $this->Room_model->get_available_rooms(
            $check_in_date,
            $check_out_date,
            $room_type_id,
            $booking_id,
            null,
            0,
            null,
            null,
            $room_id
        );

        if ($isAJAX) {
            echo json_encode($available_rooms);
            return;
        }

        return $available_rooms;
    }

    function get_rooms_available()
    {
        $check_in_date = sqli_clean($this->security->xss_clean($this->input->post('check_in_date')));
        $check_out_date = sqli_clean($this->security->xss_clean($this->input->post('check_out_date')));

        $booked_reservations = $this->Room_model->get_rooms_for_reservations(
            $check_in_date,
            $check_out_date,
            $this->company_id
        );

        $dates = array();
        foreach($booked_reservations as $book)
        {
            $check_in_date = $book['check_in_date'];
            $check_out_date = $book['check_out_date'];

            $diff = abs(strtotime($check_out_date) - strtotime($check_in_date));

            $years = floor($diff / (365*60*60*24));
            $months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
            $days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));

            for($i = 0; $i <= $days ; $i++)
            {
                $dates[] = $check_in_date;
                $check_in_date = date('Y-m-d',strtotime($check_in_date . "+1 days"));
            }
        }

        echo json_encode($dates);
    }
}
