<?php

class Admin_model extends CI_Model {
	
    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }
	
	function create_company($data)
	{
		$data = (object) $data;        
        $this->db->insert("company", $data);
		
		//return $this->db->insert_id();
        $query = $this->db->query('select LAST_INSERT_ID( ) AS last_id');
		$result = $query->result_array();
        if(isset($result[0]))
        {  
             return $result[0]['last_id'];
        }
		else
        {  
             return null;
        }
	}
	
	function get_company_admin_panel_info($company_id, $filters = null)
	{
        
         $sql = "SELECT 
                        capi.creation_date,
					    capi.conversion_date,
                        c.name, 
                        c.email, 
					    c.city,
					    c.country,
					    c.selling_date, 
					    c.is_setup, 
					    c.number_of_rooms, 
					    c.time_zone, 
					    cs.invoice_link,
					    cs.balance,
					    cs.subscription_state,
					    cs.renewal_cost,
					    cs.renewal_period,
					    cs.payment_method,
					    cs.subscription_id,
                        IFNULL(wp.username, 'Veurion') as  partner,
					    capi.company_id,
					    capi.churn_date,
					    capi.utm_source,
						(DATEDIFF(NOW(), IF(c.last_login='0000-00-00', capi.creation_date, c.last_login))) as idle
                    FROM
						company_admin_panel_info as capi
                    LEFT JOIN company as c ON c.company_id = capi.company_id
                    LEFT JOIN company_subscription as cs ON cs.company_id = c.company_id
                    LEFT JOIN user_permissions as up ON c.company_id = up.company_id AND (up.permission = 'is_owner')
                    LEFT JOIN whitelabel_partner as wp ON c.partner_id = wp.id
                    LEFT JOIN whitelabel_partner_x_admin as wpxa ON wpxa.partner_id = wp.id
                    ";
            
        $where = "";
        if($filters['data_type'] == 'property_list')
        {
            $where .= " WHERE 1 ";
            if($filters['state'] == '')
            {
                $where .= " AND cs.subscription_state = 'active'";
            }
            
            if($filters['state'] != 'all' && $filters['state'] != '')
            {
                $where .= " AND cs.subscription_state = '".$filters['state']."'";
            }
            
            if($filters['country'] != '')
            {
                $where .= " AND c.country = '".$filters['country']."'";
            }
            
            if($filters['partner'] != '')
            {
                $where .= " AND c.partner_id = '".$filters['partner']."'";
            }
            
            if($filters['from_date'] != '' && $filters['to_date'] != '')
            {
                $where .= " AND capi.creation_date >= '".$filters['from_date']."' AND
                        capi.creation_date <= '".$filters['to_date']."'";
            }
        }
        else if($filters['data_type'] == 'sign_ups')
        {
            if($filters['report_type'])
            {
                if($filters['is_filtering'])
                {
                    $where .= " WHERE c.country = '".$filters['country']."' AND 
                            capi.creation_date >= '".$filters['sdate']."' AND
                            capi.creation_date <= '".$filters['edate']."'";
                }
                else
                {
                    $where .= " WHERE c.country = '".$filters['country']."'";
                }
            }
            else
            {
                if($filters['is_filtering'])
                {
                    $where .= " WHERE capi.creation_date >= '".$filters['sdate']."' AND
                            capi.creation_date <= '".$filters['edate']."'";
                }
                else
                {
                    $where .= " WHERE YEAR(capi.creation_date) = ".$filters['year']." AND
                            MONTH(capi.creation_date) = ".$filters['month'];
                }
            }
        }
        else if($filters['data_type'] == 'conversions')
        {     
            if($filters['report_type'])
            {
                if($filters['is_filtering'])
                {
                    $where .= " WHERE cs.subscription_state = 'active' AND 
                            c.country = '".$filters['country']."' AND 
                            capi.conversion_date >= '".$filters['sdate']."' AND
                            capi.conversion_date <= '".$filters['edate']."'";
                }
                else
                {
                    $where .= " WHERE cs.subscription_state = 'active' AND c.country = '".$filters['country']."'
                        ";
                }
            }
            else
            {
                if($filters['is_filtering'])
                {
                    $where .= " WHERE cs.subscription_state = 'active' AND
                            capi.conversion_date >= '".$filters['sdate']."' AND
                            capi.conversion_date <= '".$filters['edate']."'";
                }
                else
                {
                    $where .= " WHERE cs.subscription_state = 'active' AND YEAR(capi.conversion_date) = ".$filters['year']." AND MONTH(capi.conversion_date) = ".$filters['month']."
                        ";
                }
            }
        }
        elseif($filters['data_type'] == 'active_trials')
        {
            $where .= " INNER JOIN
                    (
                        SELECT tbl.comId as comId 
                        FROM 
                        (
                            SELECT count(bk.company_id) AS cont, bk.company_id as comId 
                            FROM booking as bk GROUP BY bk.company_id
                        ) as tbl 
                        WHERE tbl.cont >= 5
                    ) AS bk ON bk.comId = capi.company_id";
            
            if($filters['is_filtering'])
            {
                $where .= " WHERE cs.subscription_state = 'trialing' AND 
                        capi.creation_date >= '".$filters['sdate']."' AND 
                        capi.creation_date <= '".$filters['edate']."'";
            }
            else
            {
                $where .= " WHERE cs.subscription_state = 'trialing' AND
                        YEAR(capi.creation_date) = ".$filters['year']." AND
                        MONTH(capi.creation_date) = ".$filters['month'];
            }
        }
        else if($filters['data_type'] == 'churns')
        {       
            if($filters['report_type'])
            {
                if($filters['is_filtering'])
                {
                    $where .= " WHERE 
                            capi.conversion_date IS NOT NULL AND capi.conversion_date != '' AND capi.conversion_date != '0000-00-00' AND cs.subscription_state = 'canceled' AND 
                            c.country = '".$filters['country']."' AND 
                            capi.churn_date >= '".$filters['sdate']."' AND
                            capi.churn_date <= '".$filters['edate']."'";
                }
                else
                {
                    $where .= " WHERE capi.conversion_date IS NOT NULL AND capi.conversion_date != '' AND capi.conversion_date != '0000-00-00' AND cs.subscription_state = 'canceled' AND c.country = '".$filters['country']."' ";
                }
            }
            else
            {
                if($filters['is_filtering'])
                {
                    $where .= " WHERE 
                            capi.conversion_date IS NOT NULL AND capi.conversion_date != '' AND capi.conversion_date != '0000-00-00' AND cs.subscription_state = 'canceled' AND 
                            capi.churn_date >= '".$filters['sdate']."' AND
                            capi.churn_date <= '".$filters['edate']."'";
                }
                else
                {
                    $where .= " WHERE capi.conversion_date IS NOT NULL AND capi.conversion_date != '' AND capi.conversion_date != '0000-00-00' AND cs.subscription_state = 'canceled' AND YEAR(capi.churn_date) = ".$filters['year']." AND MONTH(capi.churn_date) = ".$filters['month']." ";
                }
            }
        }
        else if($filters['data_type'] == 'paying_customers')
        {    
            if($filters['report_type'])
            {
                if($filters['is_filtering'])
                {
                    $where .= " WHERE cs.subscription_state = 'active' AND
                            c.country = '".$filters['country']."' AND 
                            DATE(capi.conversion_date) <= '".$filters['edate']."'";
                }
                else
                {
                    $where .= " WHERE cs.subscription_state = 'active' AND c.country = '".$filters['country']."'
                        ";
                }
            }
            else
            {
                if($filters['is_filtering'])
                {
                    $where .= " WHERE cs.subscription_state = 'active' AND
                            DATE(capi.conversion_date) <= '".$filters['edate']."'";
                }
                else
                {
                    $where .= " WHERE cs.subscription_state = 'active' AND DATE(capi.conversion_date) <= '".$filters['year']."-".$filters['month']."-31' AND DATE(capi.conversion_date) != '0000-00-00'
                        ";
                }
            }
        }
        else if($filters['data_type'] == 'months')
        {                    
            if($filters['is_filtering'])
            {
                $where .= " WHERE 
                        capi.creation_date >= '".$filters['sdate']."' AND
                        capi.creation_date <= '".$filters['edate']."'";
            }
            else
            {
                $where .= " WHERE YEAR(capi.creation_date) = ".$filters['year']." AND MONTH(capi.creation_date) = ".$filters['month']."
					";
            }
        }
        else if($filters['data_type'] == 'country')
        {                    
            if($filters['is_filtering'])
            {
                $where .= " WHERE c.country = '".$filters['country']."' AND
                        capi.creation_date >= '".$filters['sdate']."' AND
                        capi.creation_date <= '".$filters['edate']."'";
            }
            else
            {
                $where .= " WHERE c.country = '".$filters['country']."'
					";
            }
        }
        
        $where = $where ? $where : "WHERE 1";
        
        $permissions = $this->session->userdata("permissions");
        $is_salesperson = in_array("is_salesperson", $permissions) ? 1 : 0;
		
		if ($this->user_id)
		{
			$where .= " AND (
                            (wpxa.admin_id = up.user_id AND wpxa.admin_id = '$this->user_id') OR
                            (wpxa.admin_id = '$this->user_id') OR
                            (up.user_id = '$this->user_id') OR
                            ('$this->user_id' = '".SUPER_ADMIN_USER_ID."') OR
                            ($is_salesperson = 1)
                        )";
						
		}
        $group_by = "GROUP BY capi.company_id";
        $sql .= $where;
        $sql .= $group_by;
        $query = $this->db->query($sql);
        if($query->num_rows() >= 1)
		{
			$result =  $query->result();
            return $result;
		}
        
		$this->db->from('company_admin_panel_info');
		$this->db->where('company_id', $company_id);
		
		$query = $this->db->get();
		
		if ($this->db->_error_message()) // error checking
			show_error($this->db->_error_message());

		if($query->num_rows() >= 1)
		{
			$result = $query->result_array();
			return $result[0];
		}
		
		return array();
	}
    
	function get_tags($company_id)
	{
		$this->db->select('tag');
		$this->db->from('company_x_tag');
		$this->db->where('company_id', $company_id);
		
		$query = $this->db->get();
		
		if ($this->db->_error_message()) // error checking
			show_error($this->db->_error_message());

		$result_array = $query->result_array();
		$result = array();
		foreach($result_array as $r => $tag)
		{
			$result[] = $tag['tag'];
		}
		return $result;
	}
	
	function get_mailing_list() {
	
		
		$selling_date = $this->session->userdata('current_selling_date');
		
		$month_before = Date("Y-m-d", strtotime("-1 month", strtotime($selling_date)));
					
		$this->db->select('			
			p.first_name,
			p.last_name,			
			u.email,
			c.name,
			c.company_id
			');
		$this->db->from('
			company as c, 
			user_permissions as up, 
			users as u, 
			user_profiles as p
			');
		$this->db->where("c.company_id = up.company_id AND up.user_id = u.id AND u.id = p.user_id ");
		
		$this->db->group_by("u.email");
		
		$query = $this->db->get();
		//echo $this->db->last_query();
			
		if ($this->db->_error_message()) // error checking
			show_error($this->db->_error_message());

		if($query->num_rows() >= 1)
		{
			return $query->result();
		}
		
		return NULL;
	
	}
	
	
	function insert_company_x_tag($company_id, $tag)
	{
		$data = array("company_id" => $company_id, "tag" => $tag);
        $insert_query = $this->db->insert_string('company_x_tag', $data);
		$insert_query = str_replace('INSERT INTO','INSERT IGNORE INTO',$insert_query);
		$this->db->query($insert_query);
	}
	
		
	function delete_company_x_tag($company_id, $tag)
	{
		$this->db->where("company_id", $company_id);
		$this->db->where("tag", $tag);
		$this->db->delete("company_x_tag");
		if ($this->db->_error_message()) 
		{
			show_error($this->db->_error_message());
		}
	}
	
	function insert_company_admin_panel_info($data)
	{
		$data = (object) $data;
		
		$this->db->insert('company_admin_panel_info', $data);
		
		if ($this->db->_error_message()) // error checking
			show_error($this->db->_error_message());
		
	}
	
	function update_company_admin_panel_info($company_id, $data)
	{
		$data = (object) $data;
		
		$this->db->where('company_id', $company_id);
		$this->db->update('company_admin_panel_info', $data);
		
		if ($this->db->_error_message()) // error checking
			show_error($this->db->_error_message());
		
		return TRUE;
	}
	
	
	/**
	 * SQL fragment matching SaaS subscription charges in company_charge.
	 *
	 * @return string
	 */
	private function _saas_subscription_charge_where()
	{
		return "cc.is_deleted = 0 AND (cc.charge_type = 'subscription' OR cc.description LIKE 'saas\\_%')";
	}

	/**
	 * Individual SaaS subscription payments for platform admin revenue.
	 *
	 * @param string $from_date Y-m-d
	 * @param string $to_date Y-m-d
	 * @return array<int, array<string, mixed>>
	 */
	function get_saas_subscription_payments($from_date, $to_date)
	{
		$from_date = $this->db->escape_str($from_date);
		$to_date = $this->db->escape_str($to_date);
		$where = $this->_saas_subscription_charge_where();

		$sql = "
			SELECT
				cc.company_charge_id,
				cc.company_id,
				CASE
					WHEN c.company_id IS NULL THEN 'Unknown property'
					WHEN c.is_deleted = 1 THEN CONCAT(COALESCE(NULLIF(TRIM(c.name), ''), 'Property'), ' (deleted)')
					ELSE COALESCE(NULLIF(TRIM(c.name), ''), CONCAT('Property #', cc.company_id))
				END AS property_name,
				CASE
					WHEN c.company_id IS NULL THEN 'missing'
					WHEN c.is_deleted = 1 THEN 'deleted'
					ELSE 'active'
				END AS property_status,
				cc.amount,
				cc.date AS paid_date,
				cc.description AS reference,
				(
					SELECT COUNT(*)
					FROM company_charge AS prior
					WHERE prior.company_id = cc.company_id
						AND prior.is_deleted = 0
						AND (prior.charge_type = 'subscription' OR prior.description LIKE 'saas\\_%')
						AND prior.date < cc.date
				) AS prior_payment_count
			FROM company_charge AS cc
			LEFT JOIN company AS c ON c.company_id = cc.company_id
			WHERE {$where}
				AND cc.date >= DATE('{$from_date}')
				AND cc.date <= DATE('{$to_date}')
			ORDER BY cc.date DESC, cc.company_charge_id DESC
		";

		$query = $this->db->query($sql);

		if ($this->db->_error_message()) {
			show_error($this->db->_error_message());
		}

		$rows = $query->result_array();
		foreach ($rows as &$row) {
			$row['payment_type'] = classify_saas_subscription_payment_type(isset($row['prior_payment_count']) ? $row['prior_payment_count'] : 0);
		}
		unset($row);

		return $rows;
	}

	/**
	 * Most recent SaaS subscription payments across all tenants (platform dashboard).
	 *
	 * @param int $limit
	 * @return array<int, array<string, mixed>>
	 */
	function get_recent_saas_subscription_payments($limit = 10)
	{
		$limit = max(1, min(50, (int) $limit));
		$where = $this->_saas_subscription_charge_where();

		$sql = "
			SELECT
				cc.company_charge_id,
				cc.company_id,
				CASE
					WHEN c.company_id IS NULL THEN 'Unknown property'
					WHEN c.is_deleted = 1 THEN CONCAT(COALESCE(NULLIF(TRIM(c.name), ''), 'Property'), ' (deleted)')
					ELSE COALESCE(NULLIF(TRIM(c.name), ''), CONCAT('Property #', cc.company_id))
				END AS property_name,
				cc.amount,
				cc.date AS paid_date,
				cc.description AS reference,
				(
					SELECT COUNT(*)
					FROM company_charge AS prior
					WHERE prior.company_id = cc.company_id
						AND prior.is_deleted = 0
						AND (prior.charge_type = 'subscription' OR prior.description LIKE 'saas\\_%')
						AND prior.date < cc.date
				) AS prior_payment_count
			FROM company_charge AS cc
			LEFT JOIN company AS c ON c.company_id = cc.company_id
			WHERE {$where}
			ORDER BY cc.date DESC, cc.company_charge_id DESC
			LIMIT {$limit}
		";

		$query = $this->db->query($sql);

		if ($this->db->_error_message()) {
			show_error($this->db->_error_message());
		}

		$rows = $query->result_array();
		foreach ($rows as &$row) {
			$row['payment_type'] = classify_saas_subscription_payment_type(
				isset($row['prior_payment_count']) ? $row['prior_payment_count'] : 0
			);
		}
		unset($row);

		return $rows;
	}

	/**
	 * Total SaaS subscription revenue in a date range.
	 *
	 * @param string $from_date Y-m-d
	 * @param string $to_date Y-m-d
	 * @return array{total_revenue: float, payment_count: int}
	 */
	function get_subscription_revenue_summary_for_range($from_date, $to_date)
	{
		$from_date = $this->db->escape_str($from_date);
		$to_date = $this->db->escape_str($to_date);
		$where = $this->_saas_subscription_charge_where();

		$sql = "
			SELECT COALESCE(SUM(cc.amount), 0) AS total_revenue, COUNT(*) AS payment_count
			FROM company_charge AS cc
			WHERE {$where}
				AND cc.date >= DATE('{$from_date}')
				AND cc.date <= DATE('{$to_date}')
		";

		$query = $this->db->query($sql);

		if ($this->db->_error_message()) {
			show_error($this->db->_error_message());
		}

		$row = $query->row_array();

		return array(
			'total_revenue' => isset($row['total_revenue']) ? (float) $row['total_revenue'] : 0.0,
			'payment_count' => isset($row['payment_count']) ? (int) $row['payment_count'] : 0,
		);
	}

	/**
	 * All-time SaaS subscription revenue (no date filter).
	 *
	 * @return array{total_revenue: float, payment_count: int, first_payment_date: string|null, last_payment_date: string|null}
	 */
	function get_subscription_revenue_lifetime_summary()
	{
		$where = $this->_saas_subscription_charge_where();

		$sql = "
			SELECT
				COALESCE(SUM(cc.amount), 0) AS total_revenue,
				COUNT(*) AS payment_count,
				MIN(cc.date) AS first_payment_date,
				MAX(cc.date) AS last_payment_date
			FROM company_charge AS cc
			WHERE {$where}
		";

		$query = $this->db->query($sql);

		if ($this->db->_error_message()) {
			show_error($this->db->_error_message());
		}

		$row = $query->row_array();

		return array(
			'total_revenue' => isset($row['total_revenue']) ? (float) $row['total_revenue'] : 0.0,
			'payment_count' => isset($row['payment_count']) ? (int) $row['payment_count'] : 0,
			'first_payment_date' => !empty($row['first_payment_date']) ? (string) $row['first_payment_date'] : null,
			'last_payment_date' => !empty($row['last_payment_date']) ? (string) $row['last_payment_date'] : null,
		);
	}

	/**
	 * SaaS subscription revenue grouped by calendar month.
	 *
	 * @param string|null $from_date Y-m-d
	 * @param string|null $to_date Y-m-d
	 * @return array<int, array<string, mixed>>
	 */
	function get_subscription_revenue_by_month($from_date = null, $to_date = null)
	{
		$where = $this->_saas_subscription_charge_where();
		if ($from_date && $to_date) {
			$from_date = $this->db->escape_str($from_date);
			$to_date = $this->db->escape_str($to_date);
			$where .= " AND cc.date >= DATE('{$from_date}') AND cc.date <= DATE('{$to_date}')";
		}

		$sql = "
			SELECT
				YEAR(cc.date) AS year,
				MONTH(cc.date) AS month,
				SUM(cc.amount) AS subscription_revenue,
				COUNT(*) AS payment_count
			FROM company_charge AS cc
			WHERE {$where}
			GROUP BY YEAR(cc.date), MONTH(cc.date)
			ORDER BY year DESC, month DESC
		";

		$query = $this->db->query($sql);

		if ($this->db->_error_message()) {
			show_error($this->db->_error_message());
		}

		return $query->result_array();
	}

	/**
	 * Merge actual subscription payment totals into monthly report rows.
	 *
	 * @param array<int, array<string, mixed>> $monthly_rows
	 * @return array<int, array<string, mixed>>
	 */
	function merge_subscription_revenue_into_monthly_report($monthly_rows)
	{
		$by_key = array();
		foreach ($monthly_rows as $row) {
			$key = (int) $row['year'] . '-' . (int) $row['month'];
			$row['subscription_revenue'] = 0.0;
			$row['payment_count'] = 0;
			$by_key[$key] = $row;
		}

		foreach ($this->get_subscription_revenue_by_month() as $rev) {
			$key = (int) $rev['year'] . '-' . (int) $rev['month'];
			if (!isset($by_key[$key])) {
				$by_key[$key] = array(
					'year' => (int) $rev['year'],
					'month' => (int) $rev['month'],
					'creation_count' => 0,
					'conversion_count' => 0,
					'churn_count' => 0,
					'active_trial_count' => 0,
					'renewal_cost' => 0,
					'subscription_revenue' => 0.0,
					'payment_count' => 0,
				);
			}
			$by_key[$key]['subscription_revenue'] = (float) $rev['subscription_revenue'];
			$by_key[$key]['payment_count'] = (int) $rev['payment_count'];
		}

		$merged = array_values($by_key);
		usort($merged, function ($a, $b) {
			if ((int) $a['year'] !== (int) $b['year']) {
				return (int) $b['year'] - (int) $a['year'];
			}

			return (int) $b['month'] - (int) $a['month'];
		});

		return $merged;
	}

	//Returns array of objects where each object represents a row from query
	function get_monthly_report($from_date = null, $to_date = null)
	{
        if($from_date && $to_date)
        {
            $sql1 = "SELECT year, month, SUM(creation_count) AS cre_count FROM (SELECT
                        COUNT(capi.company_id) AS creation_count, YEAR(capi.creation_date) as year, MONTH(capi.creation_date) as month
                    FROM
                        company_admin_panel_info AS capi
                    LEFT JOIN
                        company_subscription AS cs ON cs.company_id = capi.company_id
                    WHERE
                        capi.creation_date >= DATE('$from_date') AND capi.creation_date <= DATE('$to_date') AND cs.subscription_state != 'deleted'
                    GROUP BY
                        YEAR(capi.creation_date),
                        MONTH(capi.creation_date)) AS creation_count1";
            $query1 = $this->db->query($sql1);
            $result1 =  $query1->result_array();

            $sql2 = "SELECT SUM(conversion_count) AS conv_count FROM (SELECT
                        COUNT(capi2.company_id) AS conversion_count
                    FROM
                        company_admin_panel_info AS capi2
                    LEFT JOIN
                        company_subscription AS cs ON cs.company_id = capi2.company_id
                    WHERE
                        cs.subscription_state = 'active' AND capi2.conversion_date <= DATE('$to_date')
                    GROUP BY
                        YEAR(capi2.conversion_date),
                        MONTH(capi2.conversion_date)) AS conversion_count1";
            $query2 = $this->db->query($sql2);
            $result2 =  $query2->result_array();
            
            $sql3 = "SELECT SUM(churn_count) AS chn_count FROM (SELECT
                        COUNT(capi3.company_id) AS churn_count
                    FROM
                        company_admin_panel_info AS capi3
                    LEFT JOIN
                        company_subscription AS cs ON cs.company_id = capi3.company_id
                    WHERE
                        capi3.conversion_date IS NOT NULL AND capi3.conversion_date != '' AND capi3.conversion_date != '0000-00-00' AND cs.subscription_state = 'canceled' AND 
                        capi3.churn_date >= DATE('$from_date') AND capi3.churn_date <= DATE('$to_date') AND cs.subscription_state != 'deleted'
                    GROUP BY
                        YEAR(capi3.churn_date),
                        MONTH(capi3.churn_date)) AS churn_count1";
            $query3 = $this->db->query($sql3);
            $result3 =  $query3->result_array();
            
            $sql4 = "SELECT year, month, SUM(conversion_count) AS bw_conv_count FROM (SELECT
                        COUNT(capi2.company_id) AS conversion_count, YEAR(capi2.conversion_date) as year, MONTH(capi2.conversion_date) as month
                    FROM
                        company_admin_panel_info AS capi2
                    LEFT JOIN
                        company_subscription AS cs ON cs.company_id = capi2.company_id
                    WHERE
                        cs.subscription_state = 'active' AND capi2.conversion_date >= DATE('$from_date') AND capi2.conversion_date <= DATE('$to_date')
                    GROUP BY
                        YEAR(capi2.conversion_date),
                        MONTH(capi2.conversion_date)) AS conversion_count4";
            $query4 = $this->db->query($sql4);
            $result4 =  $query4->result_array();
            
            $sql5 = "SELECT SUM(active_trial_count) AS act_trial_count
                    FROM (SELECT
                        COUNT(capi4.company_id) AS active_trial_count
                    FROM
                        company_admin_panel_info AS capi4
                    LEFT JOIN
                        company_subscription AS cs ON cs.company_id = capi4.company_id
                    INNER JOIN
                       (SELECT tbl.comId as comId FROM (SELECT count(bk.company_id) AS cont, bk.company_id as comId FROM booking as bk GROUP BY bk.company_id) as tbl WHERE tbl.cont >= 5) AS bk ON bk.comId = capi4.company_id
                    WHERE
                        cs.subscription_state = 'trialing' AND capi4.creation_date >= DATE('$from_date') AND capi4.creation_date <= DATE('$to_date')
                    GROUP BY
                        YEAR(capi4.creation_date),
                        MONTH(capi4.creation_date)) AS active_trial_count1";
            $query5 = $this->db->query($sql5);
            $result5 =  $query5->result_array();
            
            $subscription_summary = $this->get_subscription_revenue_summary_for_range($from_date, $to_date);
            $final_result = array(
                'active_trial_count' => $result5[0],
                'creation_count' => $result1[0],
                'conversion_count' => $result2[0],
                'bw_conversion_count' => $result4[0],
                'churn_count' => $result3[0],
                'subscription_revenue' => array(
                    array(
                        'total_revenue' => $subscription_summary['total_revenue'],
                        'payment_count' => $subscription_summary['payment_count'],
                    ),
                ),
            );
            return $final_result;
        }
        
		// get total sign ups
		$sql = "
			SELECT revenue.renewal_cost,revenue.renewal_period , created.year, created.month, creation_count, conversion_count, churn_count, active_trial_count

			from
				(
					select YEAR(capi.creation_date) as year, MONTH(capi.creation_date) as month, count(capi.company_id) as creation_count
			        from
						company_admin_panel_info as capi
                    LEFT JOIN company_subscription as cs ON cs.company_id = capi.company_id
                    WHERE cs.subscription_state != 'deleted'
					group by
						YEAR(capi.creation_date), MONTH(capi.creation_date)
				) as created
			left join
				(
					select YEAR(capi2.conversion_date) as year, MONTH(capi2.conversion_date) as month, count(capi2.company_id) as conversion_count
			        from
						company_admin_panel_info as capi2
                    LEFT JOIN company_subscription as cs ON cs.company_id = capi2.company_id
                    WHERE cs.subscription_state = 'active' || cs.subscription_state = 'canceled'
					group by
						YEAR(capi2.conversion_date), MONTH(capi2.conversion_date)
				) as converted ON converted.year = created.year AND converted.month = created.month 
			left join
				(
					select YEAR(capi3.churn_date) as year, MONTH(capi3.churn_date) as month, count(capi3.company_id) as churn_count
			        from
						company_admin_panel_info as capi3
                    LEFT JOIN company_subscription as cs ON cs.company_id = capi3.company_id
                    WHERE 
                        capi3.conversion_date IS NOT NULL AND capi3.conversion_date != '' AND capi3.conversion_date != '0000-00-00' AND cs.subscription_state = 'canceled'
					group by
						YEAR(capi3.churn_date), MONTH(capi3.churn_date)
				) as churned ON churned.year = created.year AND churned.month = created.month
            left join
				(
					SELECT
                        YEAR(capi4.creation_date) AS YEAR, MONTH(capi4.creation_date) AS MONTH, COUNT(bk.comId) AS active_trial_count
                    FROM
                      company_admin_panel_info AS capi4

                    LEFT JOIN
                      company_subscription AS cs ON cs.company_id = capi4.company_id
                    LEFT JOIN
                       (SELECT tbl.comId as comId FROM (SELECT count(bk.company_id) AS cont, bk.company_id as comId FROM booking as bk GROUP BY bk.company_id) as tbl WHERE tbl.cont >= 5) AS bk ON bk.comId = capi4.company_id
                      WHERE
                      cs.subscription_state = 'trialing'
                    GROUP BY
                      YEAR(capi4.creation_date), MONTH(capi4.creation_date)
				) as active_trial ON active_trial.year = created.year AND active_trial.month = created.month
            left join
				(
					SELECT 
						SUM(IF((cs.renewal_period = 'year' OR cs.renewal_period = '1 year' OR cs.renewal_period = '12 month' OR cs.renewal_period = '12months'  OR cs.renewal_period = '12 months'), 
							(cs.renewal_cost / 12), 
							(
								IF(cs.renewal_period = '6 month', 
								(cs.renewal_cost / 6), 
								(
									IF(cs.renewal_period = '3 month', 
									(cs.renewal_cost / 3), 
									cs.renewal_cost)
								))
							))
						) as renewal_cost, 
						cs.renewal_period,
						YEAR(capi5.conversion_date) as year, 
						MONTH(capi5.conversion_date) as month
			        from
						company_admin_panel_info as capi5
                    LEFT JOIN company_subscription as cs ON cs.company_id = capi5.company_id
                    WHERE cs.subscription_state = 'active'
					group by
						YEAR(capi5.conversion_date), MONTH(capi5.conversion_date)
				) as revenue ON revenue.year = created.year AND revenue.month = created.month 
		";
		
		$query = $this->db->query($sql);	
		//echo $this->db->last_query();
			
		if ($this->db->_error_message()) // error checking
			show_error($this->db->_error_message());

		$result =  $query->result_array();

		return $this->merge_subscription_revenue_into_monthly_report($result);
	}	
	
	function get_regional_report($from_date = null, $to_date = null)
	{
        if($from_date && $to_date)
        {
            $sql1 = "SELECT c.country,
                            COUNT(capi.company_id) AS creation_count
                        FROM
                            company_admin_panel_info AS capi
                        LEFT JOIN
                            company AS c ON c.company_id = capi.company_id
                        LEFT JOIN
                            company_subscription AS cs ON cs.company_id = capi.company_id
                        WHERE
                            capi.creation_date >= DATE('$from_date') AND capi.creation_date <= DATE('$to_date') AND c.country != '' AND cs.subscription_state != 'deleted'
                        GROUP BY c.country ORDER BY creation_count DESC";
            $query1 = $this->db->query($sql1);
            $result1 =  $query1->result_array();

            $sql2 = "SELECT c.country,
                            COUNT(capi2.company_id) AS conversion_count
                        FROM
                            company_admin_panel_info AS capi2
                        LEFT JOIN
                            company AS c ON c.company_id = capi2.company_id
                        LEFT JOIN
                            company_subscription AS cs ON cs.company_id = capi2.company_id
                        WHERE
                            cs.subscription_state = 'active' AND capi2.conversion_date >= DATE('$from_date') AND capi2.conversion_date <= DATE('$to_date')
                        GROUP BY c.country";
            $query2 = $this->db->query($sql2);
            $result2 =  $query2->result_array();
            
            $sql3 = "SELECT c.country,
                            COUNT(capi3.company_id) AS churn_count
                        FROM
                            company_admin_panel_info AS capi3
                        LEFT JOIN
                            company AS c ON c.company_id = capi3.company_id
                        LEFT JOIN
                            company_subscription AS cs ON cs.company_id = capi3.company_id
                        WHERE
                            capi3.conversion_date IS NOT NULL AND capi3.conversion_date != '' AND capi3.conversion_date != '0000-00-00' AND
                            cs.subscription_state = 'canceled' AND capi3.churn_date >= DATE('$from_date') AND capi3.churn_date <= DATE('$to_date')
                        GROUP BY c.country";
            $query3 = $this->db->query($sql3);
            $result3 =  $query3->result_array();
            
            $final = $final1 = array();            
            
            foreach($result2 as $r2 => $v2)
            {
                $final[$result2[$r2]['country']] = $v2;
            }
            
            foreach($result3 as $r3 => $v3)
            {
                $final1[$result3[$r3]['country']] = $v3;
            }

            foreach($result1 as $r1 => $v1)
            {
                if(array_key_exists($result1[$r1]['country'], $final)){
                    $result1[$r1]['conversion_count'] = $final[$result1[$r1]['country']]['conversion_count'];
                }
                else
                {
                    $result1[$r1]['conversion_count'] = '';
                }
                
                if(array_key_exists($result1[$r1]['country'], $final1)){
                    $result1[$r1]['churn_count'] = $final1[$result1[$r1]['country']]['churn_count'];
                }
                else{
                    $result1[$r1]['churn_count'] = '';
                }
            }
           
            $final_result = $result1;
            return $final_result;
        }
        
		// get total sign ups
		$sql = "
			SELECT created.country, created.year, created.month, creation_count, conversion_count, churn_count
			FROM
				(
					SELECT YEAR(capi.creation_date) as year, MONTH(capi.creation_date) as month, count(capi.company_id) as creation_count, c.country as country
			        FROM
						company_admin_panel_info as capi
                    LEFT JOIN company as c ON c.company_id = capi.company_id
                    LEFT JOIN company_subscription as cs ON cs.company_id = capi.company_id
                    WHERE c.country != '' and cs.subscription_state != 'deleted'
					group by
						c.country
				) as created
			LEFT JOIN
				(
					SELECT YEAR(capi2.conversion_date) as year, MONTH(capi2.conversion_date) as month, count(capi2.company_id) as conversion_count, c.country as country
			        FROM
						company_admin_panel_info as capi2
                    LEFT JOIN company_subscription as cs ON cs.company_id = capi2.company_id
                    LEFT JOIN company as c ON c.company_id = capi2.company_id
                    WHERE cs.subscription_state = 'active' AND c.country != ''
					group by
						c.country
				) as converted ON converted.country = created.country
            LEFT JOIN
				(
					SELECT YEAR(capi3.churn_date) as year, MONTH(capi3.churn_date) as month, count(capi3.company_id) as churn_count, c.country as country
			        FROM
						company_admin_panel_info as capi3
                    LEFT JOIN company_subscription as cs ON cs.company_id = capi3.company_id
					LEFT JOIN company as c ON c.company_id = capi3.company_id
                    WHERE 
                        capi3.conversion_date IS NOT NULL AND capi3.conversion_date != '' AND capi3.conversion_date != '0000-00-00' AND 
                        cs.subscription_state = 'canceled' AND c.country != ''
					group by
						c.country
				) as churned ON churned.country = created.country
                ORDER BY creation_count DESC
		";
		
		$query = $this->db->query($sql);	
		//echo $this->db->last_query();
			
		if ($this->db->_error_message()) // error checking
			show_error($this->db->_error_message());

		$result =  $query->result_array();
		
		return $result;
	}	
    
	//Returns array of objects where each object represents a row from query
	function get_funnel_report()
	{
		// get total sign ups
		$sql = "
			SELECT 
				count(DISTINCT c.company_id) as total_sign_ups,
				DATE_FORMAT( capi.creation_date, '%Y-%m' ) as starting_date
			FROM
				company as c, 
				company_admin_panel_info as capi
			WHERE
				c.company_id = capi.company_id
			GROUP BY starting_date
			ORDER BY starting_date ASC
		";
		
		$query = $this->db->query($sql);	
		//echo $this->db->last_query();
			
		if ($this->db->_error_message()) // error checking
			show_error($this->db->_error_message());

		$result =  $query->result();
		$result_array = array();
		foreach($result as $row)
		{
			$result_array[$row->starting_date] = Array('total_sign_ups' =>$row->total_sign_ups);;
		}
		
		$sql = "
			SELECT 
				count(DISTINCT c.company_id) as completed_setup_wizard,
				DATE_FORMAT( capi.creation_date, '%Y-%m' ) as starting_date
			FROM
				company as c, 
				company_admin_panel_info as capi
			WHERE
				c.company_id = capi.company_id AND
				c.is_setup = '1'
			GROUP BY starting_date
			ORDER BY starting_date ASC
		";
		
		$query = $this->db->query($sql);	

		if ($this->db->_error_message()) // error checking
			show_error($this->db->_error_message());
		
		$result =  $query->result();
		
		foreach($result as $row)
		{
			$result_array[$row->starting_date]['completed_setup_wizard'] =  $row->completed_setup_wizard;
		}
		
		// get total JUNK subscriptions
		$sql = "
			SELECT 
				count(DISTINCT u.id) as new_users,
				DATE_FORMAT( u.created, '%Y-%m' ) as starting_date
			FROM
				users as u
			GROUP BY starting_date
			ORDER BY starting_date ASC
		";
		
		$query = $this->db->query($sql);	
	
		if ($this->db->_error_message()) // error checking
			show_error($this->db->_error_message());
		
		$result =  $query->result();
		
		foreach($result as $row)
		{
			if (isset($result_array[$row->starting_date]))
			{
				$result_array[$row->starting_date] = $result_array[$row->starting_date] + Array('new_users' => $row->new_users);
			}
			else
			{
				$result_array[$row->starting_date] = Array('new_users' => $row->new_users);
			}
		}
		
		// get total JUNK subscriptions
		$sql = "
			SELECT 
				count(DISTINCT u.id) as completed_tutorial,
				DATE_FORMAT( u.created, '%Y-%m' ) as starting_date
			FROM
				users as u
			WHERE
				u.completed_tutorial = 1
			GROUP BY starting_date
			ORDER BY starting_date ASC
		";
		
		$query = $this->db->query($sql);	
	
		if ($this->db->_error_message()) // error checking
			show_error($this->db->_error_message());
		
		$result =  $query->result();
		
		foreach($result as $row)
		{
			if (isset($result_array[$row->starting_date]))
			{
				$result_array[$row->starting_date] = $result_array[$row->starting_date] + Array('completed_tutorial' => $row->completed_tutorial);
			}
			else
			{
				$result_array[$row->starting_date] = Array('completed_tutorial' => $row->completed_tutorial);
			}
		}
		
		// get total revenue by month
		$sql = "
			SELECT 
			count(cb.company_id) as created_booking, cb.starting_date
			FROM
			(				
				SELECT 
					DISTINCT c.company_id, DATE_FORMAT( capi.creation_date, '%Y-%m' ) as starting_date
				FROM
					company as c,
					company_admin_panel_info as capi,
					booking as b
				WHERE
					capi.company_id = c.company_id AND
					b.company_id = c.company_id
			) as cb
			GROUP BY starting_date
			ORDER BY starting_date ASC
		";
		
		$query = $this->db->query($sql);	

		if ($this->db->_error_message()) // error checking
			show_error($this->db->_error_message());
		
		$result =  $query->result();
		
		foreach($result as $row)
		{
			$result_array[$row->starting_date]['created_booking'] =  $row->created_booking;
		}
		
		$sql = "
			SELECT 
				count(DISTINCT c.company_id) as ran_night_audit,
				DATE_FORMAT( capi.creation_date, '%Y-%m' ) as starting_date
			FROM
				company as c, 
				company_admin_panel_info as capi
			WHERE
				c.company_id = capi.company_id AND
				c.selling_date != capi.creation_date
			GROUP BY starting_date
			ORDER BY starting_date ASC
		";
		
		$query = $this->db->query($sql);	

		if ($this->db->_error_message()) // error checking
			show_error($this->db->_error_message());
		
		$result =  $query->result();
		
		foreach($result as $row)
		{
			$result_array[$row->starting_date]['ran_night_audit'] =  $row->ran_night_audit;
		}
		
		return $result_array;
	}	
	
	/*
	COUNT(DISTINCT c.company_id && (c.number_of_rooms >= 10 AND c.number_of_rooms <= 19)) as C,
			COUNT(DISTINCT c.company_id && (c.number_of_rooms >= 20 AND c.number_of_rooms <= 29)) as D,
			COUNT(DISTINCT c.company_id && (c.number_of_rooms >= 30 AND c.number_of_rooms <= 39)) as E,
			COUNT(DISTINCT c.company_id && (c.number_of_rooms >= 40 AND c.number_of_rooms <= 49)) as F,
			COUNT(DISTINCT c.company_id && (c.number_of_rooms >= 50 AND c.number_of_rooms <= 59)) as G,
			COUNT(DISTINCT c.company_id && (c.number_of_rooms >= 60 AND c.number_of_rooms <= 69)) as H,
		*/
		
	//Get number of companies per room size
	function get_number_of_subscriptions_by_room_size_and_by_month()
	{
		
		$room_count_ranges = Array(
			Array('start'=>1, 'end'=>4),
			Array('start'=>5, 'end'=>9)
		);
		for($i = 10; $i < 160; $i = $i +10)
		{
			$room_count_ranges[] = Array('start' => $i, 'end' => $i + 9);
		}

		$subscription_count_array = Array();
		
		foreach ($room_count_ranges as $range)
		{
			$sql = "
				SELECT 
					DATE_FORMAT( capi.creation_date, '%Y-%m' ) as starting_date, COUNT(c.company_id) as subscription_count
				FROM
					company as c, 
					company_admin_panel_info as capi
				WHERE
					c.company_id = capi.company_id AND
					c.number_of_rooms >= ".$range['start']." AND 
					c.number_of_rooms <= ".$range['end']."
				GROUP BY starting_date
				ORDER BY starting_date ASC
			";
			
			$query = $this->db->query($sql);	
			if ($this->db->_error_message()) // error checking
				show_error($this->db->_error_message());
			
			if($query->num_rows() >= 1)
			{
				$result = $query->result();
				foreach ($result as $r)
				{
					$subscription_count_array[$r->starting_date][(string)($range['start']."-".$range['end'])] = $r->subscription_count;
				}
				
			}
		}
		return $subscription_count_array;
	}
		
	function get_charges_by_room_size_and_by_month()
	{
		$room_count_ranges = Array(
			Array('start'=>1, 'end'=>4),
			Array('start'=>5, 'end'=>9)
		);
		
		for($i = 10; $i < 160; $i = $i +10)
		{
			$room_count_ranges[] = Array('start' => $i, 'end' => $i + 9);
		}

		$charge_array = Array();
		
		foreach ($room_count_ranges as $range)
		{
			$sql = "
				SELECT 
					DATE_FORMAT( cc.date, '%Y-%m' ) as starting_date, sum(cc.amount) as charge
				FROM
					company as c, company_charge as cc
				WHERE
					c.company_id = cc.company_id AND
					c.number_of_rooms >= ".$range['start']." AND 
					c.number_of_rooms <= ".$range['end']." AND
					cc.is_deleted = '0'
				GROUP BY starting_date
				ORDER BY starting_date ASC
			";
			
			$query = $this->db->query($sql);	
			
			if ($this->db->_error_message()) // error checking
				show_error($this->db->_error_message());
			
			if($query->num_rows() >= 1)
			{
				$result = $query->result();
				foreach ($result as $r)
				{
					$charge_array[$r->starting_date][(string)($range['start']."-".$range['end'])] = $r->charge;
				}
				
			}
		}
		
		return $charge_array;
	}
	
	function search($query)
	{
		//Returns array of objects where each object represents a row from query
		$this->db->select('
			up.company_id,
			pf.first_name,
			pf.last_name,
			u.email,
			');
		$this->db->from('
			users as u,
			user_permissions as up,
			user_profiles as pf
			');
		$this->db->where("up.user_id = u.id");
		$this->db->where("pf.user_id = u.id");
		$this->db->where("(u.email like '%$query%' OR pf.first_name like '%$query%' OR pf.last_name like '%$query%')");
		$this->db->group_by("u.email");
		$query = $this->db->get();
				
		if ($this->db->_error_message()) // error checking
			show_error($this->db->_error_message());

		if($query->num_rows() >= 1)
		{
			return $query->result();
		}
		
		return NULL;		
	}
	
	
	function create_company_log($data)
    {
	    $data = (object) $data;        
        $this->db->insert("company_log", $data);
		//return $this->db->insert_id();
		$query = $this->db->query('select LAST_INSERT_ID( ) AS last_id');
		$result = $query->result_array();
        if(isset($result[0]))
        {  
            return $result[0]['last_id'];
        }
		else
        {  
            return null;
        }
    
		if ($this->db->_error_message()) // error checking
			show_error($this->db->_error_message());
		
		echo $this->db->last_query();
    }
	
	/**
	 * Activity log entries for a tenant (platform admin support notes and actions).
	 *
	 * @param int $company_id
	 * @param int $limit
	 * @return array<int, array<string, mixed>>
	 */
	function get_company_log($company_id, $limit = 50)
    {
		if (!$this->db->table_exists('company_log')) {
			return array();
		}

		$company_id = (int) $company_id;
		$limit = max(1, min(200, (int) $limit));

        $sql = "
			SELECT
				cl.company_log_id,
				cl.company_id,
				cl.date_time,
				cl.user_id,
				cl.log,
				cl.log_type,
				u.email AS user_email,
				up.first_name,
				up.last_name
			FROM company_log AS cl
			LEFT JOIN users AS u ON u.id = cl.user_id
			LEFT JOIN user_profiles AS up ON up.user_id = cl.user_id
			WHERE cl.company_id = ?
			ORDER BY cl.date_time DESC
			LIMIT {$limit}
		";
		$q = $this->db->query($sql, array($company_id));

		if ($this->db->_error_message()) {
			show_error($this->db->_error_message());
		}

		return $q->result_array();
    }

	/**
	 * Record a platform admin note or action on a tenant.
	 *
	 * @param int $company_id
	 * @param string $log
	 * @param string $log_type note|action
	 * @param int|null $user_id
	 * @return int|null
	 */
	function add_company_log_entry($company_id, $log, $log_type = 'note', $user_id = null)
	{
		if (!$this->db->table_exists('company_log')) {
			return null;
		}

		$company_id = (int) $company_id;
		$log = trim((string) $log);
		if ($company_id < 1 || $log === '') {
			return null;
		}

		$allowed_types = array('note', 'action');
		if (!in_array($log_type, $allowed_types, true)) {
			$log_type = 'note';
		}

		if ($user_id === null) {
			$user_id = (int) $this->session->userdata('user_id');
		}
		if ($user_id < 1) {
			$user_id = null;
		}

		$data = array(
			'company_id' => $company_id,
			'date_time' => date('Y-m-d H:i:s'),
			'user_id' => $user_id,
			'log' => $log,
			'log_type' => $log_type,
		);

		return $this->create_company_log($data);
	}

	/**
	 * SaaS subscription payments for one tenant (all time, most recent first).
	 *
	 * @param int $company_id
	 * @param int $limit
	 * @return array<int, array<string, mixed>>
	 */
	function get_saas_subscription_payments_for_company($company_id, $limit = 100)
	{
		$company_id = (int) $company_id;
		$limit = max(1, min(500, (int) $limit));
		if ($company_id < 1) {
			return array();
		}

		$where = $this->_saas_subscription_charge_where();

		$sql = "
			SELECT
				cc.company_charge_id,
				cc.company_id,
				cc.amount,
				cc.date AS paid_date,
				cc.description AS reference,
				(
					SELECT COUNT(*)
					FROM company_charge AS prior
					WHERE prior.company_id = cc.company_id
						AND prior.is_deleted = 0
						AND (prior.charge_type = 'subscription' OR prior.description LIKE 'saas\\_%')
						AND prior.date < cc.date
				) AS prior_payment_count
			FROM company_charge AS cc
			WHERE {$where}
				AND cc.company_id = ?
			ORDER BY cc.date DESC, cc.company_charge_id DESC
			LIMIT {$limit}
		";

		$query = $this->db->query($sql, array($company_id));

		if ($this->db->_error_message()) {
			show_error($this->db->_error_message());
		}

		$rows = $query->result_array();
		foreach ($rows as &$row) {
			$row['payment_type'] = classify_saas_subscription_payment_type(
				isset($row['prior_payment_count']) ? $row['prior_payment_count'] : 0
			);
		}
		unset($row);

		return $rows;
	}

	/**
	 * Single SaaS subscription payment by reference for receipt generation.
	 *
	 * @param int $company_id
	 * @param string $reference
	 * @return array|null
	 */
	function get_saas_subscription_payment($company_id, $reference)
	{
		$company_id = (int) $company_id;
		$reference = trim((string) $reference);
		if ($company_id < 1 || $reference === '') {
			return null;
		}

		$where = $this->_saas_subscription_charge_where();

		$sql = "
			SELECT
				cc.company_charge_id,
				cc.company_id,
				cc.amount,
				cc.date AS paid_date,
				cc.description AS reference,
				(
					SELECT COUNT(*)
					FROM company_charge AS prior
					WHERE prior.company_id = cc.company_id
						AND prior.is_deleted = 0
						AND (prior.charge_type = 'subscription' OR prior.description LIKE 'saas\\_%')
						AND prior.date < cc.date
				) AS prior_payment_count
			FROM company_charge AS cc
			WHERE {$where}
				AND cc.company_id = ?
				AND cc.description = ?
			LIMIT 1
		";

		$query = $this->db->query($sql, array($company_id, $reference));
		if ($this->db->_error_message()) {
			show_error($this->db->_error_message());
		}

		if ($query->num_rows() < 1) {
			return null;
		}

		$row = $query->row_array();
		$row['payment_type'] = classify_saas_subscription_payment_type(
			isset($row['prior_payment_count']) ? $row['prior_payment_count'] : 0
		);

		return $row;
	}

	/**
	 * Find a SaaS subscription payment by reference (any company).
	 *
	 * @param string $reference
	 * @return array|null
	 */
	function get_saas_subscription_payment_by_reference($reference)
	{
		$reference = trim((string) $reference);
		if ($reference === '') {
			return null;
		}

		$where = $this->_saas_subscription_charge_where();

		$sql = "
			SELECT
				cc.company_charge_id,
				cc.company_id,
				cc.amount,
				cc.date AS paid_date,
				cc.description AS reference
			FROM company_charge AS cc
			WHERE {$where}
				AND cc.description = ?
			LIMIT 1
		";

		$query = $this->db->query($sql, array($reference));
		if ($this->db->_error_message()) {
			show_error($this->db->_error_message());
		}

		if ($query->num_rows() < 1) {
			return null;
		}

		return $query->row_array();
	}

	/**
	 * Booking volume summary for platform admin tenant detail.
	 *
	 * @param int $company_id
	 * @return array<string, mixed>
	 */
	function get_tenant_booking_stats($company_id)
	{
		$company_id = (int) $company_id;
		$empty = array(
			'total_bookings' => 0,
			'bookings_last_30_days' => 0,
			'last_booking_date' => null,
		);
		if ($company_id < 1) {
			return $empty;
		}

		$sql = "
			SELECT
				COUNT(DISTINCT b.booking_id) AS total_bookings,
				COUNT(DISTINCT CASE
					WHEN bl.date_time >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN b.booking_id
				END) AS bookings_last_30_days,
				MAX(DATE(bl.date_time)) AS last_booking_date
			FROM booking AS b
			LEFT JOIN (
				SELECT booking_id, MIN(date_time) AS date_time
				FROM booking_log
				GROUP BY booking_id
			) AS bl ON bl.booking_id = b.booking_id
			WHERE b.company_id = ?
				AND b.is_deleted = 0
		";
		$query = $this->db->query($sql, array($company_id));
		if ($this->db->_error_message()) {
			show_error($this->db->_error_message());
		}

		$row = $query->row_array();
		if (!$row) {
			return $empty;
		}

		return array(
			'total_bookings' => isset($row['total_bookings']) ? (int) $row['total_bookings'] : 0,
			'bookings_last_30_days' => isset($row['bookings_last_30_days']) ? (int) $row['bookings_last_30_days'] : 0,
			'last_booking_date' => !empty($row['last_booking_date']) ? (string) $row['last_booking_date'] : null,
		);
	}

	/**
	 * Active extensions for a tenant (excludes internal platform extensions).
	 *
	 * @param int $company_id
	 * @return array<int, array<string, mixed>>
	 */
	function get_tenant_active_extensions($company_id)
	{
		$company_id = (int) $company_id;
		if ($company_id < 1 || !$this->db->table_exists('extensions_x_company')) {
			return array();
		}

		$skip = array('subscription', 'reseller_package', 'vendor_monthly_report');
		$this->db->select('extension_name, is_active, is_favourite');
		$this->db->from('extensions_x_company');
		$this->db->where('company_id', $company_id);
		$this->db->where('is_active', 1);
		$this->db->where_not_in('extension_name', $skip);
		$this->db->order_by('extension_name', 'ASC');
		$query = $this->db->get();

		if ($query->num_rows() < 1) {
			return array();
		}

		return $query->result_array();
	}

	/**
	 * Connected channels summary for platform admin tenant detail.
	 *
	 * @param int $company_id
	 * @return array<int, array<string, mixed>>
	 */
	function get_tenant_channel_summary($company_id)
	{
		$company_id = (int) $company_id;
		if ($company_id < 1) {
			return array();
		}

		$this->load->model('Company_model');
		$company = $this->Company_model->get_company($company_id);
		if (!$company) {
			return array();
		}

		$channels = array();
		$website_enabled = !empty($company['website_is_taking_online_reservation']);
		$channels[] = array(
			'key' => 'website',
			'label' => 'Website / OBE',
			'connected' => $website_enabled,
			'detail' => $website_enabled ? 'Online reservations enabled' : 'Not accepting online reservations',
		);

		if (!$this->db->table_exists('channel_ical_mapping')) {
			return $channels;
		}

		$labels = array(
			'booking_dot_com' => 'Booking.com',
			'airbnb' => 'Airbnb',
			'expedia' => 'Expedia',
		);

		foreach ($labels as $key => $label) {
			$this->db->from('channel_ical_mapping');
			$this->db->where('company_id', $company_id);
			$this->db->where('channel_key', $key);
			$query = $this->db->get();
			$mappings = $query->result_array();

			$import_count = 0;
			$export_count = 0;
			foreach ($mappings as $mapping) {
				if (!empty($mapping['import_enabled']) && !empty($mapping['import_url'])) {
					$import_count++;
				}
				if (!empty($mapping['export_enabled'])) {
					$export_count++;
				}
			}

			$connected = ($import_count + $export_count) > 0;
			$detail_parts = array();
			if ($import_count > 0) {
				$detail_parts[] = $import_count . ' import' . ($import_count === 1 ? '' : 's');
			}
			if ($export_count > 0) {
				$detail_parts[] = $export_count . ' export' . ($export_count === 1 ? '' : 's');
			}

			$channels[] = array(
				'key' => $key,
				'label' => $label,
				'connected' => $connected,
				'detail' => $connected ? implode(', ', $detail_parts) : 'Not connected',
			);
		}

		return $channels;
	}

	/**
	 * Setup checklist items for platform admin tenant detail.
	 *
	 * @param int $company_id
	 * @return array<int, array<string, mixed>>
	 */
	function get_tenant_setup_progress($company_id)
	{
		$company_id = (int) $company_id;
		if ($company_id < 1) {
			return array();
		}

		$this->load->model('Company_model');
		$company = $this->Company_model->get_company($company_id);
		if (!$company) {
			return array();
		}

		$room_count = isset($company['number_of_rooms_actual']) ? (int) $company['number_of_rooms_actual'] : 0;
		$booking_stats = $this->get_tenant_booking_stats($company_id);
		$channels = $this->get_tenant_channel_summary($company_id);
		$channel_connected = false;
		foreach ($channels as $channel) {
			if (!empty($channel['connected']) && $channel['key'] !== 'website') {
				$channel_connected = true;
				break;
			}
		}

		$has_payment_gateway = !empty($company['selected_payment_gateway'])
			&& strtolower((string) $company['selected_payment_gateway']) !== 'none';

		return array(
			array(
				'label' => 'Setup wizard completed',
				'done' => !empty($company['is_setup']),
			),
			array(
				'label' => 'Rooms configured',
				'done' => $room_count > 0,
				'detail' => $room_count > 0 ? $room_count . ' room' . ($room_count === 1 ? '' : 's') : null,
			),
			array(
				'label' => 'First booking created',
				'done' => $booking_stats['total_bookings'] > 0,
				'detail' => $booking_stats['total_bookings'] > 0 ? (int) $booking_stats['total_bookings'] . ' total' : null,
			),
			array(
				'label' => 'Online reservations enabled',
				'done' => !empty($company['website_is_taking_online_reservation']),
			),
			array(
				'label' => 'OTA channel connected',
				'done' => $channel_connected,
			),
			array(
				'label' => 'Payment gateway configured',
				'done' => $has_payment_gateway,
				'detail' => $has_payment_gateway ? (string) $company['selected_payment_gateway'] : null,
			),
		);
	}

	/**
	 * Single tenant row for platform admin detail page.
	 *
	 * @param int $company_id
	 * @return array|null
	 */
	function get_tenant_detail($company_id)
	{
		$company_id = (int) $company_id;
		if ($company_id < 1) {
			return null;
		}

		$rows = $this->get_tenant_list(array(
			'company_id' => $company_id,
			'subscription_state' => 'all',
			'include_deleted' => true,
		));

		if (empty($rows)) {
			return null;
		}

		$row = $rows[0];
		$panel = $this->get_single_company_admin_panel_info($company_id);
		if ($panel) {
			$row['admin_comment'] = isset($panel['admin_comment']) ? (string) $panel['admin_comment'] : '';
			$row['conversion_date'] = isset($panel['conversion_date']) ? $panel['conversion_date'] : null;
			$row['churn_date'] = isset($panel['churn_date']) ? $panel['churn_date'] : null;
			$row['utm_source'] = isset($panel['utm_source']) ? $panel['utm_source'] : null;
		} else {
			$row['admin_comment'] = '';
		}

		$this->load->model('Company_model');
		$company = $this->Company_model->get_company($company_id);
		if ($company) {
			$row['number_of_rooms_actual'] = isset($company['number_of_rooms_actual'])
				? (int) $company['number_of_rooms_actual']
				: 0;
			$row['city'] = isset($company['city']) ? $company['city'] : '';
			$row['country'] = isset($company['country']) ? $company['country'] : '';
			$row['time_zone'] = isset($company['time_zone']) ? $company['time_zone'] : '';
			$row['balance'] = isset($company['balance']) ? $company['balance'] : 0;
			$row['renewal_cost'] = isset($company['renewal_cost']) ? $company['renewal_cost'] : null;
			$row['renewal_period'] = isset($company['renewal_period']) ? $company['renewal_period'] : null;
			$row['payment_method'] = isset($company['payment_method']) ? $company['payment_method'] : null;
			$row['partner_name'] = isset($company['partner_name']) ? $company['partner_name'] : null;
		}

		return $row;
	}

    function import_company($data)
    {
    	//blah blah
    }
    
    function update_trialing_status($date)
    {
        $this->db->where("capi.creation_date <=  '$date'");
        $this->db->where("cs.subscription_state =  'trialing'");
        $this->db->update('company_admin_panel_info AS capi join company_subscription AS cs ON capi.company_id = cs.company_id',array('subscription_state' => 'trial_ended'));
    }

    /**
     * End trials whose trial_expiry_date is before today.
     *
     * @return int number of companies updated
     */
    function expire_trials_past_expiry()
    {
        $today = date('Y-m-d');
        $sql = "
            SELECT capi.company_id
            FROM company_admin_panel_info AS capi
            INNER JOIN company_subscription AS cs ON cs.company_id = capi.company_id
            WHERE cs.subscription_state = 'trialing'
              AND capi.trial_expiry_date IS NOT NULL
              AND capi.trial_expiry_date != ''
              AND capi.trial_expiry_date != '0000-00-00'
              AND capi.trial_expiry_date < ?
        ";
        $query = $this->db->query($sql, array($today));
        if ($query->num_rows() < 1) {
            return 0;
        }
        $count = 0;
        foreach ($query->result_array() as $row) {
            $this->db->where('company_id', (int) $row['company_id']);
            $this->db->update('company_subscription', array('subscription_state' => 'trial_ended'));
            $count++;
        }
        return $count;
    }

    function get_subscription_state_counts()
    {
        $sql = "
            SELECT cs.subscription_state, COUNT(*) AS cnt
            FROM company AS c
            INNER JOIN company_subscription AS cs ON cs.company_id = c.company_id
            WHERE c.is_deleted = 0
            GROUP BY cs.subscription_state
        ";
        $query = $this->db->query($sql);
        $counts = array(
            'trialing' => 0,
            'active' => 0,
            'unpaid' => 0,
            'canceled' => 0,
            'trial_ended' => 0,
            'total' => 0,
        );
        if ($query->num_rows() < 1) {
            return $counts;
        }
        foreach ($query->result_array() as $row) {
            $state = $row['subscription_state'];
            $cnt = (int) $row['cnt'];
            if (isset($counts[$state])) {
                $counts[$state] = $cnt;
            }
            $counts['total'] += $cnt;
        }
        return $counts;
    }

    function get_mrr_estimate()
    {
        $sql = "
            SELECT SUM(
                IF((cs.renewal_period = 'year' OR cs.renewal_period = '1 year' OR cs.renewal_period = '12 month' OR cs.renewal_period = '12months' OR cs.renewal_period = '12 months'),
                    (cs.renewal_cost / 12),
                    IF(cs.renewal_period = '6 month',
                        (cs.renewal_cost / 6),
                        IF(cs.renewal_period = '3 month',
                            (cs.renewal_cost / 3),
                            cs.renewal_cost
                        )
                    )
                )
            ) AS mrr
            FROM company_subscription AS cs
            INNER JOIN company AS c ON c.company_id = cs.company_id
            WHERE c.is_deleted = 0 AND cs.subscription_state = 'active'
        ";
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return isset($row['mrr']) ? round((float) $row['mrr'], 2) : 0;
    }

    /**
     * SaaS funnel metrics for the current calendar month (dashboard).
     *
     * @return array<string, mixed>
     */
    function get_dashboard_month_saas_metrics()
    {
        $month_start = date('Y-m-01');
        $month_end = date('Y-m-d');

        $sql_conversions = "
            SELECT COUNT(capi.company_id) AS cnt
            FROM company_admin_panel_info AS capi
            INNER JOIN company_subscription AS cs ON cs.company_id = capi.company_id
            INNER JOIN company AS c ON c.company_id = capi.company_id
            WHERE cs.subscription_state = 'active'
              AND capi.conversion_date IS NOT NULL
              AND capi.conversion_date != ''
              AND capi.conversion_date != '0000-00-00'
              AND capi.conversion_date >= ?
              AND capi.conversion_date <= ?
              AND c.is_deleted = 0
        ";
        $conversions = (int) $this->db->query($sql_conversions, array($month_start, $month_end))->row('cnt');

        $sql_new_trials = "
            SELECT COUNT(capi.company_id) AS cnt
            FROM company_admin_panel_info AS capi
            INNER JOIN company_subscription AS cs ON cs.company_id = capi.company_id
            INNER JOIN company AS c ON c.company_id = capi.company_id
            WHERE capi.creation_date >= ?
              AND capi.creation_date <= ?
              AND cs.subscription_state != 'deleted'
              AND c.is_deleted = 0
        ";
        $new_trials = (int) $this->db->query($sql_new_trials, array($month_start, $month_end))->row('cnt');

        $sql_churn = "
            SELECT COUNT(capi.company_id) AS cnt
            FROM company_admin_panel_info AS capi
            INNER JOIN company_subscription AS cs ON cs.company_id = capi.company_id
            INNER JOIN company AS c ON c.company_id = capi.company_id
            WHERE capi.conversion_date IS NOT NULL
              AND capi.conversion_date != ''
              AND capi.conversion_date != '0000-00-00'
              AND cs.subscription_state = 'canceled'
              AND capi.churn_date >= ?
              AND capi.churn_date <= ?
              AND c.is_deleted = 0
        ";
        $churn_count = (int) $this->db->query($sql_churn, array($month_start, $month_end))->row('cnt');

        $conversion_rate = null;
        if ($new_trials > 0) {
            $conversion_rate = round(($conversions / $new_trials) * 100, 1);
        }

        return array(
            'month_label' => date('F Y'),
            'conversions' => $conversions,
            'new_trials' => $new_trials,
            'conversion_rate' => $conversion_rate,
            'churn_count' => $churn_count,
        );
    }

    /**
     * Total amount due across all subscription debtors.
     *
     * @return array{total: float, count: int}
     */
    function get_subscription_debtor_outstanding_total()
    {
        $CI =& get_instance();
        if (!isset($CI->Platform_settings_model)) {
            $CI->load->model('Platform_settings_model');
        }

        $tiers = $CI->Platform_settings_model->get_pricing_tiers(false);
        $debtors = $this->get_subscription_debtors(array('filter' => 'all'));
        $total = 0.0;

        foreach ($debtors as $row) {
            $total += resolve_subscription_debtor_amount_due($row, $tiers);
        }

        return array(
            'total' => round($total, 2),
            'count' => count($debtors),
        );
    }

    function get_trials_expiring_within_days($days = 7)
    {
        $end = date('Y-m-d', strtotime('+' . (int) $days . ' days'));
        $today = date('Y-m-d');
        $sql = "
            SELECT c.company_id, c.name, capi.trial_expiry_date, u.email AS owner_email
            FROM company_admin_panel_info AS capi
            INNER JOIN company_subscription AS cs ON cs.company_id = capi.company_id
            INNER JOIN company AS c ON c.company_id = capi.company_id
            LEFT JOIN user_permissions AS up ON up.company_id = c.company_id AND up.permission = 'is_owner'
            LEFT JOIN users AS u ON u.id = up.user_id
            WHERE cs.subscription_state = 'trialing'
              AND capi.trial_expiry_date >= ?
              AND capi.trial_expiry_date <= ?
              AND c.is_deleted = 0
            GROUP BY c.company_id
            ORDER BY capi.trial_expiry_date ASC
            LIMIT 20
        ";
        $query = $this->db->query($sql, array($today, $end));
        if ($query->num_rows() < 1) {
            return array();
        }
        return $query->result_array();
    }
    
    function get_hotels_using_old_booking_modal()
    {
        $q = "SELECT * FROM (`company` as c) LEFT JOIN `company_subscription` as cs ON `c`.`company_id` = `cs`.`company_id` WHERE `cs`.`subscription_state` = 'trialing' OR `cs`.`subscription_state` = 'unpaid' OR `cs`.`subscription_state` = 'active'";
        $query = $this->db->query($q);
        
        return $query->result_array();
    }
	
	function create_company_group($data)
	{
		$data = (object) $data;        
        $this->db->insert("company_groups", $data);
		
		//return $this->db->insert_id();
        $query = $this->db->query('select LAST_INSERT_ID( ) AS last_id');
		$result = $query->result_array();
        if(isset($result[0]))
        {  
             return $result[0]['last_id'];
        }
		else
        {  
             return null;
        }
	}
	
	function create_company_groups_x_company($data)
	{
		$data = (object) $data;        
        $this->db->insert("company_groups_x_company", $data);
		
		//return $this->db->insert_id();
        $query = $this->db->query('select LAST_INSERT_ID( ) AS last_id');
		$result = $query->result_array();
        if(isset($result[0]))
        {  
             return $result[0]['last_id'];
        }
		else
        {  
             return null;
        }
	}
	
	function get_company_groups($company_group_id = null)
	{
		$where = "";
		if($company_group_id)
		{
			$where = " AND cg.id = $company_group_id ";
		}
		$sql = "SELECT * FROM company_groups as cg
				WHERE is_deleted = 0 $where GROUP BY cg.id ORDER BY cg.id";
		
		$query = $this->db->query($sql);
		$company_groups = $query->result_array();
		
		$sql1 = "SELECT c.company_id, c.name, cgxc.* FROM company_groups_x_company as cgxc 
				LEFT JOIN company as c ON cgxc.company_id = c.company_id";
		
		$query1 = $this->db->query($sql1);
		$company_groups_x_company = $query1->result_array();
		
		foreach($company_groups as $key => $groups)
		{
			foreach($company_groups_x_company as $groups_x_company)
			{
				if($groups['id'] == $groups_x_company['company_group_id'])
				{
					$company_groups[$key]['companies'][] = $groups_x_company;
				}
			}
		}
		
		return count($company_groups) > 0 ? $company_groups : NULL;
	}
	
	function update_company_group($company_group_id, $data)
	{
		$data = (object) $data;
		
		$this->db->where('id', $company_group_id);
		$this->db->update('company_groups', $data);
		
		if ($this->db->_error_message()) // error checking
			show_error($this->db->_error_message());
		
		return TRUE;
	}
	
	function delete_company_groups_x_company($company_group_id)
    {
        $this->db->where('company_group_id', $company_group_id);
		$this->db->delete('company_groups_x_company');
    }

    function get_single_company_admin_panel_info($company_id)
    {
        $this->db->from('company_admin_panel_info');
        $this->db->where('company_id', $company_id);
        
        $query = $this->db->get();
        
        if($query->num_rows() >= 1)
        {
            return $query->row_array();
        }
        
        return NULL;
    }

    /**
     * SaaS tenant list for platform admin property list.
     *
     * @param array $filters subscription_state (all|trialing|active|...), search, include_deleted
     * @return array
     */
    function get_tenant_list($filters = array())
    {
        $this->db->select(
            'c.company_id, c.name, c.email, c.number_of_rooms, c.country, capi.creation_date, c.is_deleted, c.last_login,
            cs.subscription_state, cs.subscription_level, cs.subscription_type, cs.region, cs.meta_data, cs.expiration_date,
            capi.trial_expiry_date,
            u.email AS owner_email, pf.first_name AS owner_first_name, pf.last_name AS owner_last_name',
            false
        );
        $this->db->from('company AS c');
        $this->db->join('company_subscription AS cs', 'cs.company_id = c.company_id', 'left');
        $this->db->join('company_admin_panel_info AS capi', 'capi.company_id = c.company_id', 'left');
        $this->db->join(
            "(SELECT up1.company_id, up1.user_id
              FROM user_permissions AS up1
              INNER JOIN (
                  SELECT company_id, MIN(user_id) AS user_id
                  FROM user_permissions
                  WHERE permission = 'is_owner'
                  GROUP BY company_id
              ) AS owner_pick ON owner_pick.company_id = up1.company_id
                  AND owner_pick.user_id = up1.user_id
              WHERE up1.permission = 'is_owner'
            ) AS up",
            'up.company_id = c.company_id',
            'left',
            false
        );
        $this->db->join('users AS u', 'u.id = up.user_id', 'left');
        $this->db->join('user_profiles AS pf', 'pf.user_id = u.id', 'left');

        if (empty($filters['include_deleted'])) {
            $this->db->where('c.is_deleted', 0);
        }

        if (!empty($filters['company_id'])) {
            $this->db->where('c.company_id', (int) $filters['company_id']);
        }

        $state = isset($filters['subscription_state']) ? $filters['subscription_state'] : 'all';
        if ($state !== '' && $state !== 'all') {
            $this->db->where('cs.subscription_state', $state);
        }

        if (!empty($filters['search'])) {
            $q = $this->db->escape_like_str($filters['search']);
            $this->db->where(
                "(c.name LIKE '%{$q}%' OR c.email LIKE '%{$q}%' OR u.email LIKE '%{$q}%')",
                null,
                false
            );
        }

        $this->db->group_by('c.company_id');
        $this->db->order_by('c.company_id', 'DESC');

        $query = $this->db->get();
        if ($query->num_rows() >= 1) {
            return $query->result_array();
        }
        return array();
    }

    /**
     * Used by internal tooling (e.g. company/get_all_companies).
     *
     * @param array $options
     * @return array|null
     */
    function get_company_list($options = array())
    {
        $status = isset($options['status']) ? $options['status'] : 'active';
        $active_subscriptions = isset($options['active_subscriptions']) ? $options['active_subscriptions'] : true;

        $this->db->select('c.company_id, c.name, cs.subscription_state');
        $this->db->from('company AS c');
        $this->db->join('company_subscription AS cs', 'cs.company_id = c.company_id', 'left');
        $this->db->join('company_admin_panel_info AS capi', 'capi.company_id = c.company_id', 'left');
        $this->db->where('c.is_deleted', 0);

        if ($active_subscriptions) {
            $this->db->where('cs.subscription_state', 'active');
        } elseif ($status) {
            $this->db->where('cs.subscription_state', $status);
        }

        if (!empty($options['last_login'])) {
            $days = (int) $options['last_login'];
            $this->db->where("DATEDIFF(NOW(), IF(c.last_login = '0000-00-00', capi.creation_date, c.last_login)) <= {$days}", null, false);
        }

        $query = $this->db->get();
        if ($query->num_rows() >= 1) {
            return $query->result_array();
        }
        return null;
    }

    /**
     * Properties with subscription payment due or overdue (platform admin debtors list).
     *
     * @param array $filters filter (overdue|due_soon|all), search, include_deleted
     * @return array<int, array<string, mixed>>
     */
    function get_subscription_debtors($filters = array())
    {
        $today = date('Y-m-d');
        $due_soon_end = date('Y-m-d', strtotime('+7 days'));
        $filter = isset($filters['filter']) ? (string) $filters['filter'] : 'overdue';
        if (!in_array($filter, array('overdue', 'due_soon', 'all'), true)) {
            $filter = 'overdue';
        }

        $overdue_sql = "
            cs.subscription_state IN ('trial_ended', 'unpaid')
            OR (
                cs.subscription_state = 'trialing'
                AND capi.trial_expiry_date IS NOT NULL
                AND capi.trial_expiry_date != ''
                AND capi.trial_expiry_date != '0000-00-00'
                AND capi.trial_expiry_date < DATE('{$today}')
            )
            OR (
                cs.subscription_state = 'active'
                AND cs.expiration_date IS NOT NULL
                AND cs.expiration_date != ''
                AND cs.expiration_date != '0000-00-00'
                AND cs.expiration_date < DATE('{$today}')
            )
        ";

        $due_soon_sql = "
            (
                cs.subscription_state = 'trialing'
                AND capi.trial_expiry_date IS NOT NULL
                AND capi.trial_expiry_date != ''
                AND capi.trial_expiry_date != '0000-00-00'
                AND capi.trial_expiry_date >= DATE('{$today}')
                AND capi.trial_expiry_date <= DATE('{$due_soon_end}')
            )
            OR (
                cs.subscription_state = 'active'
                AND cs.expiration_date IS NOT NULL
                AND cs.expiration_date != ''
                AND cs.expiration_date != '0000-00-00'
                AND cs.expiration_date >= DATE('{$today}')
                AND cs.expiration_date <= DATE('{$due_soon_end}')
            )
        ";

        if ($filter === 'overdue') {
            $status_where = '(' . $overdue_sql . ')';
        } elseif ($filter === 'due_soon') {
            $status_where = '(' . $due_soon_sql . ')';
        } else {
            $status_where = '((' . $overdue_sql . ') OR (' . $due_soon_sql . '))';
        }

        $search = isset($filters['search']) ? trim((string) $filters['search']) : '';
        $search_where = '';
        if ($search !== '') {
            $q = $this->db->escape_like_str($search);
            $search_where = " AND (c.name LIKE '%{$q}%' OR c.email LIKE '%{$q}%' OR u.email LIKE '%{$q}%')";
        }

        $deleted_where = empty($filters['include_deleted']) ? ' AND c.is_deleted = 0' : '';

        $sql = "
            SELECT
                c.company_id,
                c.name,
                c.email,
                c.number_of_rooms,
                c.is_deleted,
                c.last_login,
                capi.creation_date,
                capi.trial_expiry_date,
                cs.subscription_state,
                cs.expiration_date,
                cs.renewal_cost,
                cs.renewal_period,
                cs.balance,
                cs.meta_data,
                u.email AS owner_email,
                pf.first_name AS owner_first_name,
                pf.last_name AS owner_last_name,
                CASE
                    WHEN cs.subscription_state = 'unpaid' THEN 'unpaid'
                    WHEN cs.subscription_state = 'trial_ended' THEN 'trial_ended'
                    WHEN cs.subscription_state = 'trialing'
                        AND capi.trial_expiry_date IS NOT NULL
                        AND capi.trial_expiry_date != ''
                        AND capi.trial_expiry_date != '0000-00-00'
                        AND capi.trial_expiry_date < DATE('{$today}')
                        THEN 'trial_overdue'
                    WHEN cs.subscription_state = 'active'
                        AND cs.expiration_date IS NOT NULL
                        AND cs.expiration_date != ''
                        AND cs.expiration_date != '0000-00-00'
                        AND cs.expiration_date < DATE('{$today}')
                        THEN 'plan_overdue'
                    ELSE 'due_soon'
                END AS debtor_reason,
                CASE
                    WHEN cs.subscription_state IN ('trial_ended', 'trialing')
                        AND capi.trial_expiry_date IS NOT NULL
                        AND capi.trial_expiry_date != ''
                        AND capi.trial_expiry_date != '0000-00-00'
                        THEN capi.trial_expiry_date
                    WHEN cs.expiration_date IS NOT NULL
                        AND cs.expiration_date != ''
                        AND cs.expiration_date != '0000-00-00'
                        THEN cs.expiration_date
                    ELSE NULL
                END AS due_date,
                CASE
                    WHEN cs.subscription_state IN ('trial_ended', 'trialing')
                        AND capi.trial_expiry_date IS NOT NULL
                        AND capi.trial_expiry_date != ''
                        AND capi.trial_expiry_date != '0000-00-00'
                        THEN DATEDIFF(DATE('{$today}'), capi.trial_expiry_date)
                    WHEN cs.expiration_date IS NOT NULL
                        AND cs.expiration_date != ''
                        AND cs.expiration_date != '0000-00-00'
                        THEN DATEDIFF(DATE('{$today}'), cs.expiration_date)
                    ELSE 0
                END AS days_overdue
            FROM company AS c
            INNER JOIN company_subscription AS cs ON cs.company_id = c.company_id
            LEFT JOIN company_admin_panel_info AS capi ON capi.company_id = c.company_id
            LEFT JOIN (
                SELECT up1.company_id, up1.user_id
                FROM user_permissions AS up1
                INNER JOIN (
                    SELECT company_id, MIN(user_id) AS user_id
                    FROM user_permissions
                    WHERE permission = 'is_owner'
                    GROUP BY company_id
                ) AS owner_pick ON owner_pick.company_id = up1.company_id
                    AND owner_pick.user_id = up1.user_id
                WHERE up1.permission = 'is_owner'
            ) AS up ON up.company_id = c.company_id
            LEFT JOIN users AS u ON u.id = up.user_id
            LEFT JOIN user_profiles AS pf ON pf.user_id = u.id
            WHERE {$status_where}
                {$deleted_where}
                {$search_where}
            GROUP BY c.company_id
            ORDER BY days_overdue DESC, due_date ASC, c.name ASC
        ";

        $query = $this->db->query($sql);

        if ($this->db->_error_message()) {
            show_error($this->db->_error_message());
        }

        if ($query->num_rows() < 1) {
            return array();
        }

        $rows = $query->result_array();
        foreach ($rows as &$row) {
            $row['debtor_reason_label'] = subscription_debtor_reason_label($row['debtor_reason']);
            $row['days_overdue'] = (int) $row['days_overdue'];
            if ($row['days_overdue'] < 0) {
                $row['days_overdue'] = 0;
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * Counts for subscription debtors dashboard cards.
     *
     * @return array{overdue: int, due_soon: int, all: int}
     */
    function get_subscription_debtor_counts()
    {
        return array(
            'overdue' => count($this->get_subscription_debtors(array('filter' => 'overdue'))),
            'due_soon' => count($this->get_subscription_debtors(array('filter' => 'due_soon'))),
            'all' => count($this->get_subscription_debtors(array('filter' => 'all'))),
        );
    }

    /**
     * Platform-wide extension adoption counts for upsell/support.
     *
     * @return array<int, array<string, mixed>>
     */
    function get_extension_adoption_stats()
    {
        if (!$this->db->table_exists('extensions_x_company')) {
            return array();
        }

        $skip = array('subscription', 'reseller_package', 'vendor_monthly_report');
        $this->db->select('exc.extension_name, COUNT(DISTINCT exc.company_id) AS tenant_count', false);
        $this->db->from('extensions_x_company AS exc');
        $this->db->join('company AS c', 'c.company_id = exc.company_id', 'inner');
        $this->db->join('company_subscription AS cs', 'cs.company_id = c.company_id', 'inner');
        $this->db->where('exc.is_active', 1);
        $this->db->where('c.is_deleted', 0);
        $this->db->where_in('cs.subscription_state', array('trialing', 'active'));
        $this->db->where_not_in('exc.extension_name', $skip);
        $this->db->group_by('exc.extension_name');
        $this->db->order_by('tenant_count', 'DESC');
        $this->db->order_by('exc.extension_name', 'ASC');

        $query = $this->db->get();
        if ($query->num_rows() < 1) {
            return array();
        }

        return $query->result_array();
    }

    /**
     * Count of active/trialing tenants (denominator for adoption rates).
     *
     * @return int
     */
    function get_active_tenant_count()
    {
        $sql = "
            SELECT COUNT(*) AS cnt
            FROM company AS c
            INNER JOIN company_subscription AS cs ON cs.company_id = c.company_id
            WHERE c.is_deleted = 0
              AND cs.subscription_state IN ('trialing', 'active')
        ";
        $query = $this->db->query($sql);
        if ($query->num_rows() < 1) {
            return 0;
        }
        $row = $query->row_array();

        return isset($row['cnt']) ? (int) $row['cnt'] : 0;
    }

    /**
     * Companies with Channex active but no recent booking retrieval sync.
     *
     * @param int $stale_hours
     * @return array<int, array<string, mixed>>
     */
    function get_channel_sync_issues($stale_hours = 48)
    {
        if (
            !$this->db->table_exists('extensions_x_company')
            || !$this->db->table_exists('ota_x_company')
            || !$this->db->table_exists('ota_xml_logs')
        ) {
            return array();
        }

        $stale_hours = max(1, (int) $stale_hours);
        $sql = "
            SELECT
                c.company_id,
                c.name,
                oxc.ota_property_id,
                MAX(oxl.datetime) AS last_sync_at
            FROM company AS c
            INNER JOIN extensions_x_company AS exc ON exc.company_id = c.company_id
                AND exc.extension_name IN ('channex-integration', 'channexpci_integration')
                AND exc.is_active = 1
            INNER JOIN ota_x_company AS oxc ON oxc.company_id = c.company_id AND oxc.is_active = 1
            LEFT JOIN ota_xml_logs AS oxl ON oxl.ota_property_id = oxc.ota_property_id AND oxl.request_type = 2
            INNER JOIN company_subscription AS cs ON cs.company_id = c.company_id
            WHERE c.is_deleted = 0
              AND cs.subscription_state IN ('trialing', 'active')
            GROUP BY c.company_id, c.name, oxc.ota_property_id
            HAVING MAX(oxl.datetime) IS NULL
                OR MAX(oxl.datetime) < DATE_SUB(NOW(), INTERVAL {$stale_hours} HOUR)
            ORDER BY MAX(oxl.datetime) IS NULL DESC, MAX(oxl.datetime) ASC, c.name ASC
        ";

        $query = $this->db->query($sql);
        if (!$query || $this->db->_error_message()) {
            log_message('error', 'get_channel_sync_issues: ' . $this->db->_error_message());
            return array();
        }
        if ($query->num_rows() < 1) {
            return array();
        }

        return $query->result_array();
    }

    /**
     * Night audit health across all auto-enabled properties.
     *
     * @return array<string, mixed>
     */
    function get_night_audit_platform_health()
    {
        $this->load->model(array('Company_model', 'Night_audit_model'));
        $this->load->helper(array('timezone', 'automated_night_audit'));

        $companies = $this->Company_model->get_companies_with_auto_night_audit();
        if (!$companies) {
            return array(
                'enabled_count' => 0,
                'overdue_count' => 0,
                'ok_count' => 0,
                'overdue' => array(),
            );
        }

        $overdue = array();
        $ok = 0;
        foreach ($companies as $company) {
            $company_id = (int) $company['company_id'];
            $time_zone = $this->Company_model->get_time_zone($company_id);
            $local_now = automated_night_audit_local_now($time_zone);
            $run_time = !empty($company['night_audit_auto_run_time'])
                ? $company['night_audit_auto_run_time']
                : '04:00:00';
            $selling_date = isset($company['selling_date']) ? (string) $company['selling_date'] : '';
            $has_log = $this->Night_audit_model->has_logged_for_selling_date($company_id, $selling_date);

            if (automated_night_audit_is_overdue(
                $local_now,
                $run_time,
                $selling_date,
                $has_log,
                3
            )) {
                $overdue[] = array(
                    'company_id' => $company_id,
                    'name' => isset($company['name']) ? (string) $company['name'] : ('#' . $company_id),
                    'selling_date' => $selling_date,
                    'calendar_date' => $local_now->format('Y-m-d'),
                    'run_time' => $run_time,
                );
            } else {
                $ok++;
            }
        }

        return array(
            'enabled_count' => count($companies),
            'overdue_count' => count($overdue),
            'ok_count' => $ok,
            'overdue' => $overdue,
        );
    }

    /**
     * Cron job health summary for platform admin.
     *
     * @return array<string, mixed>
     */
    function get_cron_health_summary()
    {
        $today = date('Y-m-d');
        $pending_sql = "
            SELECT COUNT(*) AS cnt
            FROM company_admin_panel_info AS capi
            INNER JOIN company_subscription AS cs ON cs.company_id = capi.company_id
            INNER JOIN company AS c ON c.company_id = capi.company_id
            WHERE cs.subscription_state = 'trialing'
              AND capi.trial_expiry_date IS NOT NULL
              AND capi.trial_expiry_date != ''
              AND capi.trial_expiry_date != '0000-00-00'
              AND capi.trial_expiry_date < ?
              AND c.is_deleted = 0
        ";
        $pending_query = $this->db->query($pending_sql, array($today));
        $pending = 0;
        if ($pending_query->num_rows() >= 1) {
            $row = $pending_query->row_array();
            $pending = isset($row['cnt']) ? (int) $row['cnt'] : 0;
        }

        $expiring_today_sql = "
            SELECT COUNT(*) AS cnt
            FROM company_admin_panel_info AS capi
            INNER JOIN company_subscription AS cs ON cs.company_id = capi.company_id
            INNER JOIN company AS c ON c.company_id = capi.company_id
            WHERE cs.subscription_state = 'trialing'
              AND capi.trial_expiry_date = ?
              AND c.is_deleted = 0
        ";
        $expiring_query = $this->db->query($expiring_today_sql, array($today));
        $expiring_today = 0;
        if ($expiring_query->num_rows() >= 1) {
            $row = $expiring_query->row_array();
            $expiring_today = isset($row['cnt']) ? (int) $row['cnt'] : 0;
        }

        $repo_root = dirname(dirname(dirname(dirname(__FILE__))));
        $trial_log = $repo_root . '/logs/cron/expire_platform_trials.log';
        $night_audit_log = $repo_root . '/logs/cron/run_automated_night_audit.log';

        return array(
            'trials_pending_expiry' => $pending,
            'trials_expiring_today' => $expiring_today,
            'trial_cron_last_run' => parse_cron_log_last_run($trial_log),
            'night_audit_cron_last_run' => parse_cron_log_last_run($night_audit_log),
            'trial_cron_schedule' => 'Daily at 02:10 UTC',
            'night_audit_cron_schedule' => 'Every 15 minutes',
        );
    }

    /**
     * Tenant health rows with composite score and risk flags.
     *
     * @param array $filters filter (all|at_risk|no_bookings|trial_expiring|night_audit|channel_sync), search
     * @return array<int, array<string, mixed>>
     */
    function get_tenant_health_rows($filters = array())
    {
        $today = date('Y-m-d');
        $trial_soon_end = date('Y-m-d', strtotime('+7 days'));
        $inactive_cutoff = date('Y-m-d', strtotime('-30 days'));

        $sql = "
            SELECT
                c.company_id,
                c.name,
                c.last_login,
                cs.subscription_state,
                capi.trial_expiry_date,
                u.email AS owner_email,
                pf.first_name AS owner_first_name,
                pf.last_name AS owner_last_name,
                COALESCE(bs.total_bookings, 0) AS total_bookings,
                COALESCE(bs.bookings_last_30_days, 0) AS bookings_last_30_days
            FROM company AS c
            INNER JOIN company_subscription AS cs ON cs.company_id = c.company_id
            LEFT JOIN company_admin_panel_info AS capi ON capi.company_id = c.company_id
            LEFT JOIN (
                SELECT company_id, MIN(user_id) AS user_id
                FROM user_permissions
                WHERE permission = 'is_owner'
                GROUP BY company_id
            ) AS owner_pick ON owner_pick.company_id = c.company_id
            LEFT JOIN users AS u ON u.id = owner_pick.user_id
            LEFT JOIN user_profiles AS pf ON pf.user_id = u.id
            LEFT JOIN (
                SELECT
                    b.company_id,
                    COUNT(DISTINCT b.booking_id) AS total_bookings,
                    COUNT(DISTINCT CASE
                        WHEN bl.date_time >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN b.booking_id
                    END) AS bookings_last_30_days
                FROM booking AS b
                LEFT JOIN (
                    SELECT booking_id, MIN(date_time) AS date_time
                    FROM booking_log
                    GROUP BY booking_id
                ) AS bl ON bl.booking_id = b.booking_id
                WHERE b.is_deleted = 0
                GROUP BY b.company_id
            ) AS bs ON bs.company_id = c.company_id
            WHERE c.is_deleted = 0
              AND cs.subscription_state IN ('trialing', 'active')
        ";

        if (!empty($filters['search'])) {
            $q = $this->db->escape_like_str($filters['search']);
            $sql .= " AND (c.name LIKE '%{$q}%' OR u.email LIKE '%{$q}%')";
        }

        $sql .= ' ORDER BY c.name ASC';

        $query = $this->db->query($sql);
        if ($query->num_rows() < 1) {
            return array();
        }

        $channel_issues = $this->get_channel_sync_issues();
        $channel_issue_ids = array();
        foreach ($channel_issues as $issue) {
            $channel_issue_ids[(int) $issue['company_id']] = true;
        }

        $night_audit = $this->get_night_audit_platform_health();
        $night_audit_overdue_ids = array();
        foreach ($night_audit['overdue'] as $row) {
            $night_audit_overdue_ids[(int) $row['company_id']] = true;
        }

        $rows = array();
        foreach ($query->result_array() as $row) {
            $company_id = (int) $row['company_id'];
            $trial_expiry = !empty($row['trial_expiry_date']) ? (string) $row['trial_expiry_date'] : '';
            $state = isset($row['subscription_state']) ? (string) $row['subscription_state'] : '';
            $last_login = !empty($row['last_login']) && $row['last_login'] !== '0000-00-00'
                ? (string) $row['last_login']
                : '';

            $flags = array(
                'no_bookings' => ((int) $row['total_bookings']) < 1,
                'trial_expiring_soon' => $state === 'trialing'
                    && $trial_expiry !== ''
                    && $trial_expiry !== '0000-00-00'
                    && $trial_expiry >= $today
                    && $trial_expiry <= $trial_soon_end,
                'trial_expired' => $state === 'trial_ended'
                    || ($state === 'trialing' && $trial_expiry !== '' && $trial_expiry !== '0000-00-00' && $trial_expiry < $today),
                'night_audit_overdue' => !empty($night_audit_overdue_ids[$company_id]),
                'channel_sync_issue' => !empty($channel_issue_ids[$company_id]),
                'inactive_login' => $last_login === '' || $last_login < $inactive_cutoff,
            );

            $score = compute_tenant_health_score($flags);
            $risk = tenant_health_risk_level($score);
            $flag_labels = tenant_health_flag_labels();
            $active_flags = array();
            foreach ($flags as $key => $active) {
                if ($active && isset($flag_labels[$key])) {
                    $active_flags[] = $flag_labels[$key];
                }
            }

            $rows[] = array(
                'company_id' => $company_id,
                'name' => isset($row['name']) ? (string) $row['name'] : '',
                'subscription_state' => $state,
                'trial_expiry_date' => $trial_expiry !== '' ? $trial_expiry : null,
                'owner_email' => isset($row['owner_email']) ? (string) $row['owner_email'] : '',
                'owner_name' => trim((string) $row['owner_first_name'] . ' ' . $row['owner_last_name']),
                'total_bookings' => (int) $row['total_bookings'],
                'bookings_last_30_days' => (int) $row['bookings_last_30_days'],
                'health_score' => $score,
                'risk_level' => $risk,
                'flags' => $flags,
                'flag_labels' => $active_flags,
            );
        }

        usort($rows, function ($a, $b) {
            if ($a['health_score'] === $b['health_score']) {
                return strcmp($a['name'], $b['name']);
            }

            return $a['health_score'] - $b['health_score'];
        });

        $filter = isset($filters['filter']) ? (string) $filters['filter'] : 'at_risk';
        if ($filter === 'all') {
            return $rows;
        }

        return array_values(array_filter($rows, function ($row) use ($filter) {
            if ($filter === 'at_risk') {
                return in_array($row['risk_level'], array('at_risk', 'critical'), true);
            }
            if ($filter === 'no_bookings') {
                return !empty($row['flags']['no_bookings']);
            }
            if ($filter === 'trial_expiring') {
                return !empty($row['flags']['trial_expiring_soon']) || !empty($row['flags']['trial_expired']);
            }
            if ($filter === 'night_audit') {
                return !empty($row['flags']['night_audit_overdue']);
            }
            if ($filter === 'channel_sync') {
                return !empty($row['flags']['channel_sync_issue']);
            }

            return true;
        }));
    }

    /**
     * Summary counts for tenant health dashboard cards.
     *
     * @return array<string, int>
     */
    function get_tenant_health_summary()
    {
        $all = $this->get_tenant_health_rows(array('filter' => 'all'));
        $counts = array(
            'total' => count($all),
            'at_risk' => 0,
            'critical' => 0,
            'no_bookings' => 0,
            'trial_expiring' => 0,
            'night_audit_overdue' => 0,
            'channel_sync_issues' => 0,
        );

        foreach ($all as $row) {
            if ($row['risk_level'] === 'at_risk') {
                $counts['at_risk']++;
            } elseif ($row['risk_level'] === 'critical') {
                $counts['critical']++;
            }
            if (!empty($row['flags']['no_bookings'])) {
                $counts['no_bookings']++;
            }
            if (!empty($row['flags']['trial_expiring_soon']) || !empty($row['flags']['trial_expired'])) {
                $counts['trial_expiring']++;
            }
            if (!empty($row['flags']['night_audit_overdue'])) {
                $counts['night_audit_overdue']++;
            }
            if (!empty($row['flags']['channel_sync_issue'])) {
                $counts['channel_sync_issues']++;
            }
        }

        return $counts;
    }

    /**
     * Company IDs for bulk tenant health actions.
     *
     * @param string $scope all_trialing|expiring_7|active|at_risk
     * @return array<int, int>
     */
    function get_tenant_health_bulk_company_ids($scope)
    {
        $scope = (string) $scope;
        $today = date('Y-m-d');
        $soon = date('Y-m-d', strtotime('+7 days'));

        if ($scope === 'all_trialing') {
            $sql = "
                SELECT c.company_id
                FROM company AS c
                INNER JOIN company_subscription AS cs ON cs.company_id = c.company_id
                WHERE c.is_deleted = 0 AND cs.subscription_state = 'trialing'
            ";
            $query = $this->db->query($sql);
        } elseif ($scope === 'expiring_7') {
            $sql = "
                SELECT c.company_id
                FROM company AS c
                INNER JOIN company_subscription AS cs ON cs.company_id = c.company_id
                INNER JOIN company_admin_panel_info AS capi ON capi.company_id = c.company_id
                WHERE c.is_deleted = 0
                  AND cs.subscription_state = 'trialing'
                  AND capi.trial_expiry_date >= ?
                  AND capi.trial_expiry_date <= ?
            ";
            $query = $this->db->query($sql, array($today, $soon));
        } elseif ($scope === 'active') {
            $sql = "
                SELECT c.company_id
                FROM company AS c
                INNER JOIN company_subscription AS cs ON cs.company_id = c.company_id
                WHERE c.is_deleted = 0 AND cs.subscription_state = 'active'
            ";
            $query = $this->db->query($sql);
        } elseif ($scope === 'at_risk') {
            $ids = array();
            foreach ($this->get_tenant_health_rows(array('filter' => 'at_risk')) as $row) {
                $ids[] = (int) $row['company_id'];
            }

            return $ids;
        } else {
            return array();
        }

        if (!$query || $query->num_rows() < 1) {
            return array();
        }

        $ids = array();
        foreach ($query->result_array() as $row) {
            $ids[] = (int) $row['company_id'];
        }

        return $ids;
    }
}
	
?>
