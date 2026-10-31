<?php

class Charge_model extends CI_Model {

	function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }	
    
    function get_last_applied_charge($booking_id, $charge_type_id, $end_date = null, $only_night_audit_charge = false)
	{
		$this->db->where('charge_type_id', $charge_type_id);		
        $this->db->where('booking_id', $booking_id);		
        $this->db->where('is_deleted', 0);		
        if($only_night_audit_charge) {
            $this->db->where('is_night_audit_charge', 1);	
        }
        
        if($end_date){
            $this->db->where('selling_date <', $end_date);	
        }
        
        $this->db->order_by('selling_date', 'DESC');		
        $this->db->limit(1);		
		$query = $this->db->get('charge');
		
		if ($query->num_rows() > 0)
		{
			return $query->result_array()[0];
		}
		else
		{
			return null;
		}
	}
    function get_charges($booking_id, $exclude_deleted_bookings = false, $company_id = null)
    {
		$this->db->select('c.*');
		
		if($exclude_deleted_bookings) {
			$this->db->join('booking as b', 'b.booking_id = c.booking_id', 'left');
			$this->db->where('b.is_deleted', '0');
		}

        if($company_id) {
            $this->db->join('booking as b', 'b.booking_id = c.booking_id', 'left');
            $this->db->where('b.company_id', $company_id);
        }
		
        $this->db->where('c.booking_id', $booking_id);

        $this->db->where('c.is_deleted', '0');
		
        $query = $this->db->get("charge as c");
				
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        else{
            return null;
        }
    }	
    
    function insert_charges($charge_data)
    {
        $this->db->insert_batch('charge', $charge_data); 
        
        if ($this->db->_error_message())
		{
			return show_error($this->db->_error_message());
		}
    }

    function has_external_reference_column()
    {
        return $this->db->field_exists('external_reference', 'charge');
    }

    /**
     * Find a non-deleted charge by external_reference (POS idempotency key).
     */
    function get_charge_by_external_reference($external_reference)
    {
        if (!$external_reference || !$this->has_external_reference_column()) {
            return null;
        }
        $this->db->where('external_reference', $external_reference);
        $this->db->where('is_deleted', '0');
        $this->db->limit(1);
        $query = $this->db->get('charge');
        if ($query->num_rows() > 0) {
            return $query->row_array();
        }
        return null;
    }

    function insert_charge($data)
    {
        if (isset($data['external_reference']) && !$this->has_external_reference_column()) {
            unset($data['external_reference']);
        }
        if (isset($data['description']) && is_string($data['description']) && strlen($data['description']) > 125) {
            $data['description'] = substr($data['description'], 0, 125);
        }

        $db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $this->db->insert('charge', $data);
        $error = $this->db->_error_message();
        $insert_id = $this->db->insert_id();
        $this->db->db_debug = $db_debug;

        if ($error) {
            // Duplicate external_reference — caller may resolve via get_charge_by_external_reference.
            if (stripos($error, 'Duplicate') !== false || stripos($error, 'uniq_charge_external_reference') !== false) {
                return false;
            }
            show_error($error);
        }
        return $insert_id;
    }

    /**
     * Charge with its booking's company and state, including deleted charges.
     */
    function get_charge_for_void($charge_id = null, $external_reference = null)
    {
        $this->db->select('ch.*, b.company_id, b.state as booking_state');
        $this->db->from('charge as ch');
        $this->db->join('booking as b', 'b.booking_id = ch.booking_id');
        if ($external_reference !== null && $external_reference !== '') {
            if (!$this->has_external_reference_column()) {
                return null;
            }
            $this->db->where('ch.external_reference', $external_reference);
        } else {
            $this->db->where('ch.charge_id', (int) $charge_id);
        }
        $this->db->limit(1);
        $row = $this->db->get()->row_array();
        return $row ? $row : null;
    }

    function soft_delete_charge($charge_id)
    {
        $this->db->where('charge_id', (int) $charge_id);
        $this->db->update('charge', array('is_deleted' => 1));
        return $this->db->affected_rows() > 0;
    }

    function insert_invoice_log($booking_id, $action_id, $log, $charge_id, $amount)
    {
        $this->db->insert('invoice_log', array(
            'booking_id' => (int) $booking_id,
            'date_time' => gmdate('Y-m-d H:i:s'),
            'user_id' => 0,
            'action_id' => $action_id,
            'log' => $log,
            'charge_or_payment_id' => (int) $charge_id,
            'new_amount' => $amount,
        ));
    }

    function insert_charge_folio($charge_id, $folio_id = 0)
    {
        $data = array('charge_id' => $charge_id, 'folio_id' => $folio_id);
        $this->db->insert('charge_folio', $data);
        if ($this->db->_error_message()) {
            show_error($this->db->_error_message());
        }
        return $this->db->insert_id();
    }
}