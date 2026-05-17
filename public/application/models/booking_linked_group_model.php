<?php
class Booking_linked_group_model extends CI_Model {
    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }
    
    function create_booking_linked_group($group_name, $billing_mode = 'room'){
        $this->db->insert('booking_linked_group', array(
            'name' => $group_name,
            'billing_mode' => $billing_mode ? $billing_mode : 'room',
        ));
        $insert_id = $this->db->insert_id();
        if(isset($insert_id))
            return $insert_id;
        return null ;
    }
    
    function insert_booking_x_booking_linked_group($data){
        $this->db->insert('booking_x_booking_linked_group', $data);
    }

    function insert_booking_x_booking_linked_group_batch($data){
        $this->db->insert_batch('booking_x_booking_linked_group', $data);
    }
    
    function get_booking_linked_group($booking_id, $company_id){
        $sql = 'SELECT
                    blg.id,
                    blg.name,
                    bxblg.booking_id
                FROM
                    `booking_linked_group` AS blg
                LEFT JOIN
                    booking_x_booking_linked_group AS bxblg ON blg.id = bxblg.booking_group_id
                LEFT JOIN
                    booking AS b ON bxblg.booking_id = b.booking_id
                WHERE
                    bxblg.booking_id = '.$booking_id.' AND 
                    b.company_id = '.$company_id;
        
        $query = $this->db->query($sql);	
     	
		if ($this->db->_error_message()) 
		{
			show_error($this->db->_error_message());
		}
		
		$result = $query->result_array();
        if($result)
            return $result[0];
        else
            return null;
    }
    
    function get_group_booking_ids($group_id){
        $this->db->select('*');
        $this->db->where('booking_group_id',$group_id );
        $this->db->from('booking_x_booking_linked_group');
        $query = $this->db->get();
        return $query->result_array();
    }
    
    function get_linked_groups($data_filters, $company_id){
        $where = '';
        if(!empty($data_filters)){
            if(isset($data_filters['group_id']) && $data_filters['group_id'])
                $where .= ' AND blg.id = '.$data_filters['group_id'] ;

            if(isset($data_filters['group_name']) && $data_filters['group_name'])
                $where .= ' AND blg.name Like "%'.$data_filters['group_name'].'%" ';

            if(isset($data_filters['customer_name']) && $data_filters['customer_name'])
                 $where .= ' AND c.customer_name Like "%'.$data_filters['customer_name'].'%" ';
        }
     
        $sql = "SELECT
                    blg.*,
                    b.booking_id,
                    MIN(brh.check_in_date) as check_in_date,
                    MAX(brh.check_out_date) as check_out_date,
                    c.customer_name,
                    c.phone,
                    c.customer_id
                FROM
                    booking_linked_group AS blg
                LEFT JOIN
                    booking_x_booking_linked_group AS bxblg ON blg.id = bxblg.booking_group_id
                LEFT JOIN
                    booking AS b ON bxblg.booking_id = b.booking_id
                LEFT JOIN
                    booking_block AS brh ON b.booking_id = brh.booking_id
                LEFT JOIN
                    customer AS c ON b.booking_customer_id = c.customer_id 
               
                WHERE
                    b.company_id = $company_id AND
                    b.is_deleted != 1
                    $where 
                GROUP By 
                    blg.id 
                ORDER BY 
                    blg.id ASC";
       
        $query = $this->db->query($sql);
        if ($this->db->_error_message()) 
		{
			show_error($this->db->_error_message());
		}
		
		$result = $query->result_array();
       
        if($result)
            return $result;
        else
            return null;
    }

    function create_booking_linked_groups($group_name){

        $data = array(
            'name' => $group_name
        );

        $this->db->insert('booking_linked_group', $data);
        $insert_id = $this->db->insert_id();
        // echo $this->db->last_query();die;
        if(isset($insert_id))
            return $insert_id;
        return null ;
    }

    function update_group_settings($group_id, $company_id, $data)
    {
        $allowed = array('name', 'billing_mode', 'master_customer_id');
        $update = array();
        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $update[$key] = $data[$key];
            }
        }
        if (empty($update)) {
            return false;
        }

        $group = $this->get_group_by_id($group_id, $company_id);
        if (!$group) {
            return false;
        }

        $this->db->where('id', (int) $group_id);
        return $this->db->update('booking_linked_group', $update);
    }

    function get_group_by_id($group_id, $company_id)
    {
        $sql = "
            SELECT blg.*
            FROM booking_linked_group blg
            INNER JOIN booking_x_booking_linked_group bxblg ON bxblg.booking_group_id = blg.id
            INNER JOIN booking b ON b.booking_id = bxblg.booking_id
            WHERE blg.id = ? AND b.company_id = ? AND b.is_deleted = 0
            LIMIT 1
        ";
        $query = $this->db->query($sql, array((int) $group_id, (int) $company_id));

        if ($query->num_rows() < 1) {
            return null;
        }

        return $query->row_array();
    }

    function get_group_summary($group_id, $company_id)
    {
        $group = $this->get_group_by_id($group_id, $company_id);
        if (!$group) {
            return null;
        }

        $bookings = $this->get_group_booking_ids($group_id);
        $booking_count = is_array($bookings) ? count($bookings) : 0;

        $sql = "
            SELECT
                MIN(brh.check_in_date) as check_in_date,
                MAX(brh.check_out_date) as check_out_date,
                SUM(b.balance) as total_balance
            FROM booking_x_booking_linked_group bxblg
            INNER JOIN booking b ON b.booking_id = bxblg.booking_id
            LEFT JOIN booking_block brh ON brh.booking_id = b.booking_id
            WHERE bxblg.booking_group_id = ? AND b.company_id = ? AND b.is_deleted = 0
        ";
        $query = $this->db->query($sql, array((int) $group_id, (int) $company_id));
        $dates = $query->row_array();

        return array_merge($group, array(
            'booking_count' => $booking_count,
            'check_in_date' => isset($dates['check_in_date']) ? $dates['check_in_date'] : null,
            'check_out_date' => isset($dates['check_out_date']) ? $dates['check_out_date'] : null,
            'total_balance' => isset($dates['total_balance']) ? $dates['total_balance'] : 0,
        ));
    }
}
