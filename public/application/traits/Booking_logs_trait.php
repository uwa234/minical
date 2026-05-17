<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Booking audit-log helpers extracted from the booking controller.
 */
trait Booking_logs_trait
{
    function _generate_logs($new_data, $old_data)
    {
        $new_array = isset($new_data['customers']) && isset($new_data['customers']['staying_customers']) ? $new_data['customers']['staying_customers'] : array();
        $old_array = isset($old_data['customers']) && isset($old_data['customers']['staying_customers']) ?$old_data['customers']['staying_customers'] : array();
        $flag = 2;
        $added_arr = $delete_arr = array();
        foreach($new_array as $value){

            $flag = 2;
            foreach($old_array as $v2){

                if($value['customer_name'] == $v2['customer_name'] && !empty($value['customer_name'])){
                    $flag = 1;

                }

            }
            if($flag != 1)
            {
                $added_arr[] = $value['customer_name'];
            }


        }

        foreach($old_array as $value){

            $deleteflag = 2;
            foreach($new_array as $v2){

                if($value['customer_id'] == $v2['customer_id']){
                    $deleteflag = 1;
                }

            }

            if($deleteflag != 1)
            {
                if(in_array($value['customer_name'], $added_arr)){

                }else{
                    $delete_arr[] = $value['customer_name'];
                }

            }

        }

        $fields = array(
            'booking' => Array(
                'state' => 5,
                'charge_type_id' => 6,
                'adult_count' => 7,
                'children_count' => 8,
                'rate' => 9,
                'use_rate_plan' => 10,
                'rate_plan_id' => 11,
                'booking_notes' => 12,
                'color' => 13,
                'is_deleted' => 14,
                'pay_period' => 20,
                'booking_customer_name' => 18,
                'source' => 23
            ),
            'booking_block' => Array(
                'check_in_date' => 15,
                'check_out_date' => 16,
                'room_id' => 17,
            ),
            'customers' => Array(
                'staying_customers' => 19
            )
        );
        $new_data = array(
            'booking' => Array(
                'state' => isset($new_data['booking']['state']) ? $new_data['booking']['state'] : null,
                'charge_type_id' => isset($new_data['rooms'][0]['charge_type_id']) ? $new_data['rooms'][0]['charge_type_id'] : null,
                'adult_count' => isset($new_data['booking']['adult_count']) ? $new_data['booking']['adult_count'] : null,
                'children_count' => isset($new_data['booking']['children_count']) ? $new_data['booking']['children_count'] : null,
                'rate' => isset($new_data['rooms'][0]['rate']) ? $new_data['rooms'][0]['rate'] : null,
                'use_rate_plan' => isset($new_data['rooms'][0]['use_rate_plan']) ? $new_data['rooms'][0]['use_rate_plan'] : null,
                'rate_plan_id' => null,
                'booking_notes' => isset($new_data['booking']['booking_notes']) ? $new_data['booking']['booking_notes'] : null,
                'color' => isset($new_data['booking']['color']) ? $new_data['booking']['color'] : null,
                'is_deleted' => null,
                'pay_period' => isset($new_data['rooms'][0]['pay_period']) ? $new_data['rooms'][0]['pay_period'] : null,
                'booking_customer_name' => isset($new_data['customers']['paying_customer']['customer_name']) ? $new_data['customers']['paying_customer']['customer_name'] : null,
                'source'=> isset($new_data['booking']['source']) ? $new_data['booking']['source'] : null
            ),
            'booking_block' => Array(
                'check_in_date' => isset($new_data['rooms'][0]['check_in_date']) ? $new_data['rooms'][0]['check_in_date'] : null,
                'check_out_date' => isset($new_data['rooms'][0]['check_out_date']) ? $new_data['rooms'][0]['check_out_date'] : null,
                'room_id' => (isset($new_data['rooms'][0]['room_id']) && $new_data['rooms'][0]['room_id']) ? $new_data['rooms'][0]['room_id'] : 0,
            ),
            'customers' => Array(
                'staying_customers' => isset($new_data['customers']['staying_customers']) ? $new_data['customers']['staying_customers'] : array()
            )
        );

        $logs = array();

        $date_time = gmdate('Y-m-d H:i:s');

        foreach ($fields as $category => $sub_fields)
        {
            foreach ($sub_fields as $index => $log_type){
                if (isset($old_data[$category]) && isset($new_data[$category]) && isset($old_data[$category][$index]) && isset($new_data[$category][$index]) && $old_data[$category][$index] != $new_data[$category][$index])
                {
                    if($log_type == '19'){

                        if (!empty($added_arr)) {
                            $added_guest_string = implode(',', $added_arr);
                            $added_guest = "A guest named " . $added_guest_string ." was added";
                        }
                        if (!empty($delete_arr)) {
                            if((!empty($new_data['booking']['booking_customer_name']) && !empty($old_data['booking']['booking_customer_name'])) && ($new_data['booking']['booking_customer_name'] != $old_data['booking']['booking_customer_name'])){

                            }else{
                                $deleted_guest_string = implode(',', $delete_arr);
                                $deleted_guest = "A guest named " . $deleted_guest_string ." was deleted";
                            }

                        }
                        if(!empty($added_guest) && !empty($deleted_guest)){
                            $guest_log = $added_guest." and ".$deleted_guest;
                        }elseif (!empty($added_guest)) {
                            $guest_log = $added_guest;
                        }elseif (!empty($deleted_guest)) {
                            $guest_log = $deleted_guest;
                        }

                        if(!empty($guest_log)){
                            $log_type = 19;
                            $logs[] = Array(
                                "booking_id" => $old_data['booking']['booking_id'],
                                "date_time" => $date_time,
                                "log_type" => $log_type,
                                "log" => $guest_log,
                                "user_id" => $this->user_id,
                                "selling_date" => $this->selling_date
                            );
                        }
                    }else{

                        $log_data = $new_data[$category][$index];
                        if($log_type == 18){
                            if(!empty($new_data[$category][$index]) && empty($old_data[$category][$index])){
                                $log_data = 'A paying customer named '.$new_data[$category][$index].' was added';
                            }elseif(!empty($new_data[$category][$index]) && !empty($old_data[$category][$index])){
                                $log_data = 'A paying customer named '.$old_data[$category][$index].' was deleted and a paying customer named '.$new_data[$category][$index].' (who was a guest) updated';
                            }else{
                                $log_data = 'A paying customer named '.$old_data[$category][$index].' was deleted';
                            }
                        }
                        if($log_type == 12){
                            if(!empty($new_data[$category][$index]) && empty($old_data[$category][$index])){
                                $log_data = 'Added booking notes is '.$new_data[$category][$index];
                            }elseif(!empty($new_data[$category][$index]) && !empty($old_data[$category][$index])){
                                $log_data = 'Changed booking notes to '.$new_data[$category][$index];
                            }elseif(empty($new_data[$category][$index]) && !empty($old_data[$category][$index])){
                                $log_data = 'Deleted booking notes is '.$old_data[$category][$index];
                            }
                        }

                        if(isset($log_data)){
                            $logs[] = Array(
                                "booking_id" => $old_data['booking']['booking_id'],
                                "date_time" => $date_time,
                                "log_type" => $log_type,
                                "log" => $log_data,
                                "user_id" => $this->user_id,
                                "selling_date" => $this->selling_date
                            );
                        }
                    }
                }
            }
        }

        if (empty($logs))
            return;

        $this->Booking_log_model->insert_logs($logs);
    }

    function _create_booking_log($booking_id, $log, $log_type = USER_LOG) {

        $this->Booking_log_model->insert_log(
            array(
                "selling_date" => $this->selling_date,
                "booking_id" => $booking_id,
                "date_time" => gmdate('Y-m-d H:i:s'),
                "log_type" => $log_type,
                "log" => $log,
                "user_id" => $this->user_id
            )
        );
    }

    function _create_booking_log_batch ($booking_ids, $log, $log_type = USER_LOG) {

        $batch = array();

        foreach ($booking_ids as $booking_id) {
            $batch[] = array(
                "selling_date" => $this->selling_date,
                "booking_id" => $booking_id,
                "date_time" => gmdate('Y-m-d H:i:s'),
                "log_type" => $log_type,
                "log" => $log,
                "user_id" => $this->user_id
            );
        }

        $this->Booking_log_model->insert_logs($batch);
    }
}
