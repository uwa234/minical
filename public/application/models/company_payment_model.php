<?php

class Company_payment_model extends CI_Model {
	private $table_name = null;

    function __construct()
    {        
        parent::__construct();
    }		

	private function resolve_table_name()
	{
		if ($this->table_name !== null) {
			return $this->table_name;
		}

		if ($this->db->table_exists('company_payment')) {
			$this->table_name = 'company_payment';
			return $this->table_name;
		}

		if ($this->db->table_exists('company_payments')) {
			$this->table_name = 'company_payments';
			return $this->table_name;
		}

		$this->table_name = false;
		return $this->table_name;
	}
	
	function insert_company_payment($payments)
    {
		$table = $this->resolve_table_name();
		if ($table === false) {
			return false;
		}

	   $this->db->insert_batch($table, $payments);
		
		if ($this->db->_error_message()) 
		{
			show_error($this->db->_error_message());
		}		

		return true;
    }
		
	function get_company_payments($company_id)
    {		
		$table = $this->resolve_table_name();
		if ($table === false) {
			return array();
		}

        $sql = "
			SELECT * 
			FROM {$table}
			WHERE 
				company_id = '$company_id'
			ORDER BY date ASC
		";
		
		$q = $this->db->query($sql);
		
		if ($this->db->_error_message())
		{
			show_error($this->db->_error_message());
		}
		
		$result = $q->result_array();
		
		return $result;
    }	
	
	function get_company_payment_total($company_id)
    {		
		$table = $this->resolve_table_name();
		if ($table === false) {
			return 0;
		}

        $sql = "
			SELECT SUM(amount) as payment_total
			FROM {$table}
			WHERE 
				company_id = '$company_id' AND
				is_deleted = '0'
		";
		
		$q = $this->db->query($sql);
		
		if ($this->db->_error_message())
		{
			show_error($this->db->_error_message());
		}
		
		if($q->num_rows() >= 1)
		{
			$result = $q->result_array();
			return $result[0]['payment_total'];
		}
		return 0;
    }	
	
	function does_transaction_exist($company_id, $transaction_reference)
	{
		$table = $this->resolve_table_name();
		if ($table === false) {
			return false;
		}

		$this->db->from($table);
		$this->db->where('company_id', $company_id);
		$this->db->where('description', $transaction_reference);
		
		$q = $this->db->get();
		
		if ($this->db->_error_message())
		{
			show_error($this->db->_error_message());
		}
		
		if($q->num_rows() >= 1)
		{
			return true;
		}
		return false;
	}
	
	function delete_company_payment($company_payment_id) {
		$table = $this->resolve_table_name();
		if ($table === false) {
			return false;
		}

		$data['is_deleted'] = '1';
        $this->db->where('company_payment_id', $company_payment_id);
        $this->db->update($table, $data);
		//echo $this->db->last_query();
		return true;
	}


}