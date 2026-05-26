<?php

class Owner_dashboard_model extends CI_Model
{
    /**
     * SQL fragment: earliest booking_log entry per booking (proxy for created-at).
     */
    private function booking_created_subquery_sql()
    {
        return "
            SELECT booking_id, MIN(date_time) AS created_at
            FROM booking_log
            GROUP BY booking_id
        ";
    }

    /**
     * Human-readable booking source name (common IDs + custom booking_source rows).
     */
    private function resolve_booking_source_label($source)
    {
        if ($source === null || $source === '') {
            return 'Unknown';
        }

        $source_key = (string) $source;

        if ((int) $source > 20) {
            $row = $this->db->query(
                'SELECT name FROM booking_source WHERE id = ' . (int) $source . ' LIMIT 1'
            )->row_array();
            return ($row && !empty($row['name'])) ? $row['name'] : 'Unknown';
        }

        static $common_sources = null;
        if ($common_sources === null) {
            $common_sources = json_decode(COMMON_BOOKING_SOURCES, true);
            if (!is_array($common_sources)) {
                $common_sources = array();
            }
        }

        if (isset($common_sources[$source_key])) {
            return $common_sources[$source_key];
        }

        return 'Unknown';
    }

    /**
     * High-level KPI summary for the owner: revenue, bookings, occupancy
     * for current month vs previous month.
     */
    public function get_kpi_summary($company_id, $today)
    {
        $company_id = (int) $company_id;
        $today       = $this->db->escape_str($today);

        $current_month_start = date('Y-m-01', strtotime($today));
        $current_month_end   = $today;
        $prev_month_start    = date('Y-m-01', strtotime($current_month_start . ' -1 month'));
        $prev_month_end      = date('Y-m-t',  strtotime($prev_month_start));

        $active_states = RESERVATION . ',' . INHOUSE . ',' . UNCONFIRMED_RESERVATION;

        // Revenue helper (payments in period)
        $get_revenue = function ($start, $end) use ($company_id) {
            $row = $this->db->query("
                SELECT COALESCE(SUM(p.amount), 0) AS total
                FROM payment AS p
                INNER JOIN payment_type AS pt ON pt.payment_type_id = p.payment_type_id AND pt.company_id = {$company_id}
                INNER JOIN booking AS b ON b.booking_id = p.booking_id AND b.is_deleted != '1'
                WHERE p.is_deleted = '0'
                  AND p.selling_date BETWEEN '{$start}' AND '{$end}'
            ")->row_array();
            return (float) $row['total'];
        };

        $created_subquery = $this->booking_created_subquery_sql();

        // New bookings created in period (first booking_log timestamp)
        $get_new_bookings = function ($start, $end) use ($company_id, $created_subquery) {
            $row = $this->db->query("
                SELECT COUNT(DISTINCT b.booking_id) AS total
                FROM booking AS b
                INNER JOIN ({$created_subquery}) AS bl ON bl.booking_id = b.booking_id
                WHERE b.company_id = {$company_id}
                  AND b.is_deleted != '1'
                  AND DATE(bl.created_at) BETWEEN '{$start}' AND '{$end}'
            ")->row_array();
            return (int) $row['total'];
        };

        // Average daily rate for period (total charges / room-nights)
        $get_adr = function ($start, $end) use ($company_id, $active_states) {
            $row = $this->db->query("
                SELECT
                    COALESCE(SUM(c.amount), 0) AS room_revenue,
                    COALESCE(COUNT(DISTINCT CONCAT(bb.booking_id, '-', bb.room_id, '-', DATE(bb.check_in_date))), 0) AS room_nights
                FROM booking AS b
                INNER JOIN booking_block AS bb ON bb.booking_id = b.booking_id
                LEFT JOIN charge AS c ON c.booking_id = b.booking_id AND c.is_deleted = '0'
                WHERE b.company_id = {$company_id}
                  AND b.is_deleted != '1'
                  AND b.state IN ({$active_states})
                  AND DATE(bb.check_in_date) BETWEEN '{$start}' AND '{$end}'
            ")->row_array();
            $room_nights = (int) $row['room_nights'];
            return $room_nights > 0 ? round((float) $row['room_revenue'] / $room_nights, 2) : 0;
        };

        $curr_revenue  = $get_revenue($current_month_start, $current_month_end);
        $prev_revenue  = $get_revenue($prev_month_start, $prev_month_end);
        $curr_bookings = $get_new_bookings($current_month_start, $current_month_end);
        $prev_bookings = $get_new_bookings($prev_month_start, $prev_month_end);
        $curr_adr      = $get_adr($current_month_start, $current_month_end);
        $prev_adr      = $get_adr($prev_month_start, $prev_month_end);

        // Outstanding balance (all time active bookings)
        $balance_row = $this->db->query("
            SELECT COALESCE(SUM(b.balance), 0) AS total, COUNT(*) AS cnt
            FROM booking AS b
            WHERE b.company_id = {$company_id}
              AND b.is_deleted != '1'
              AND b.state IN ({$active_states})
              AND b.balance > 0.005
        ")->row_array();

        $calc_change = function ($curr, $prev) {
            if ($prev == 0) return $curr > 0 ? 100 : 0;
            return round((($curr - $prev) / $prev) * 100, 1);
        };

        return array(
            'revenue' => array(
                'current'  => $curr_revenue,
                'previous' => $prev_revenue,
                'change'   => $calc_change($curr_revenue, $prev_revenue),
            ),
            'bookings' => array(
                'current'  => $curr_bookings,
                'previous' => $prev_bookings,
                'change'   => $calc_change($curr_bookings, $prev_bookings),
            ),
            'adr' => array(
                'current'  => $curr_adr,
                'previous' => $prev_adr,
                'change'   => $calc_change($curr_adr, $prev_adr),
            ),
            'outstanding' => array(
                'total' => (float) $balance_row['total'],
                'count' => (int)   $balance_row['cnt'],
            ),
        );
    }

    /**
     * Monthly revenue for the last N months (for sparkline/bar chart).
     */
    public function get_monthly_revenue($company_id, $today, $months = 12)
    {
        $company_id = (int) $company_id;
        $months     = max(2, min(24, (int) $months));

        $rows = array();
        for ($i = $months - 1; $i >= 0; $i--) {
            $month_start = date('Y-m-01', strtotime($today . " -{$i} months"));
            $month_end   = date('Y-m-t',  strtotime($month_start));
            $label       = date('M Y',    strtotime($month_start));

            $row = $this->db->query("
                SELECT COALESCE(SUM(p.amount), 0) AS total
                FROM payment AS p
                INNER JOIN payment_type AS pt ON pt.payment_type_id = p.payment_type_id AND pt.company_id = {$company_id}
                INNER JOIN booking AS b ON b.booking_id = p.booking_id AND b.is_deleted != '1'
                WHERE p.is_deleted = '0'
                  AND p.selling_date BETWEEN '{$month_start}' AND '{$month_end}'
            ")->row_array();

            $rows[] = array(
                'label'   => $label,
                'revenue' => round((float) $row['total'], 2),
            );
        }
        return $rows;
    }

    /**
     * Monthly occupancy % for the last N months.
     */
    public function get_monthly_occupancy($company_id, $today, $months = 12)
    {
        $company_id  = (int) $company_id;
        $months      = max(2, min(24, (int) $months));
        $active_states = RESERVATION . ',' . INHOUSE . ',' . UNCONFIRMED_RESERVATION;

        $this->load->model('Room_model');
        $total_rooms = (int) $this->Room_model->get_number_of_rooms($company_id);
        if ($total_rooms < 1) $total_rooms = 1;

        $rows = array();
        for ($i = $months - 1; $i >= 0; $i--) {
            $month_start = date('Y-m-01', strtotime($today . " -{$i} months"));
            $month_end   = date('Y-m-t',  strtotime($month_start));
            $days_in_month = (int) date('t', strtotime($month_start));
            $label       = date('M Y',    strtotime($month_start));

            $row = $this->db->query("
                SELECT COALESCE(SUM(occ.occupied), 0) AS total_occ
                FROM (
                    SELECT di.date, COUNT(DISTINCT brh.room_id) AS occupied
                    FROM date_interval AS di
                    INNER JOIN booking_block AS brh ON
                        DATE(brh.check_in_date) <= di.date AND DATE(brh.check_out_date) > di.date
                        AND brh.room_id IS NOT NULL AND brh.room_id != 0
                    INNER JOIN booking AS b ON
                        b.booking_id = brh.booking_id AND b.company_id = {$company_id}
                        AND b.is_deleted != '1' AND b.state IN ({$active_states})
                    WHERE di.date BETWEEN '{$month_start}' AND '{$month_end}'
                    GROUP BY di.date
                ) AS occ
            ")->row_array();

            $total_possible = $total_rooms * $days_in_month;
            $occupancy_pct  = $total_possible > 0
                ? round(((float) $row['total_occ'] / $total_possible) * 100, 1)
                : 0;

            $rows[] = array(
                'label'     => $label,
                'occupancy' => $occupancy_pct,
            );
        }
        return $rows;
    }

    /**
     * Booking source breakdown for current month.
     */
    public function get_booking_source_breakdown($company_id, $today)
    {
        $company_id  = (int) $company_id;
        $month_start = date('Y-m-01', strtotime($today));
        $created_subquery = $this->booking_created_subquery_sql();

        $rows = $this->db->query("
            SELECT
                b.source AS source_id,
                COUNT(b.booking_id) AS count
            FROM booking AS b
            INNER JOIN ({$created_subquery}) AS bl ON bl.booking_id = b.booking_id
            WHERE b.company_id = {$company_id}
              AND b.is_deleted != '1'
              AND DATE(bl.created_at) >= '{$month_start}'
            GROUP BY b.source
            ORDER BY count DESC
            LIMIT 8
        ")->result_array();

        $labeled = array();
        foreach ($rows as $row) {
            $name = $this->resolve_booking_source_label($row['source_id']);
            if (!isset($labeled[$name])) {
                $labeled[$name] = 0;
            }
            $labeled[$name] += (int) $row['count'];
        }
        arsort($labeled);

        $result = array();
        foreach ($labeled as $source => $count) {
            $result[] = array('source' => $source, 'count' => $count);
        }
        return $result;
    }

    /**
     * Top rooms by revenue this month.
     */
    public function get_top_rooms_by_revenue($company_id, $today, $limit = 8)
    {
        $company_id  = (int) $company_id;
        $month_start = date('Y-m-01', strtotime($today));
        $month_end   = $today;
        $limit       = (int) $limit;

        $rows = $this->db->query("
            SELECT
                r.room_name,
                COALESCE(SUM(p.amount), 0) AS revenue,
                COUNT(DISTINCT b.booking_id) AS bookings
            FROM room AS r
            INNER JOIN booking_block AS bb ON bb.room_id = r.room_id
            INNER JOIN booking AS b ON b.booking_id = bb.booking_id
                AND b.company_id = {$company_id} AND b.is_deleted != '1'
            LEFT JOIN payment AS p ON p.booking_id = b.booking_id
                AND p.is_deleted = '0'
                AND p.selling_date BETWEEN '{$month_start}' AND '{$month_end}'
            WHERE r.company_id = {$company_id} AND r.is_deleted = '0'
            GROUP BY r.room_id
            ORDER BY revenue DESC
            LIMIT {$limit}
        ")->result_array();

        return $rows;
    }

    /**
     * Forward-looking occupancy: occupancy % for next 30 days.
     */
    public function get_forward_occupancy($company_id, $today, $days = 30)
    {
        $company_id  = (int) $company_id;
        $days        = max(7, min(60, (int) $days));
        $end_date    = date('Y-m-d', strtotime($today . " +{$days} days"));
        $active_states = RESERVATION . ',' . INHOUSE . ',' . UNCONFIRMED_RESERVATION;

        $this->load->model('Room_model');
        $total_rooms = (int) $this->Room_model->get_number_of_rooms($company_id);
        if ($total_rooms < 1) $total_rooms = 1;

        $rows = $this->db->query("
            SELECT di.date, COUNT(DISTINCT brh.room_id) AS occupied
            FROM date_interval AS di
            LEFT JOIN booking_block AS brh ON
                DATE(brh.check_in_date) <= di.date AND DATE(brh.check_out_date) > di.date
                AND brh.room_id IS NOT NULL AND brh.room_id != 0
            LEFT JOIN booking AS b ON
                b.booking_id = brh.booking_id AND b.company_id = {$company_id}
                AND b.is_deleted != '1' AND b.state IN ({$active_states})
            WHERE di.date BETWEEN '{$today}' AND '{$end_date}'
            GROUP BY di.date
            ORDER BY di.date ASC
        ")->result_array();

        $labels     = array();
        $values     = array();
        foreach ($rows as $row) {
            $labels[] = date('M j', strtotime($row['date']));
            $values[] = $total_rooms > 0 ? round(($row['occupied'] / $total_rooms) * 100) : 0;
        }

        return array('labels' => $labels, 'values' => $values);
    }

    /**
     * Recent bookings list for the owner feed.
     */
    public function get_recent_bookings($company_id, $limit = 10)
    {
        $company_id = (int) $company_id;
        $limit      = (int) $limit;
        $created_subquery = $this->booking_created_subquery_sql();

        $rows = $this->db->query("
            SELECT
                b.booking_id,
                b.state,
                b.balance,
                b.source AS source_id,
                bl.created_at AS date_created,
                c.customer_name,
                MIN(bb.check_in_date) AS check_in,
                MAX(bb.check_out_date) AS check_out
            FROM booking AS b
            INNER JOIN ({$created_subquery}) AS bl ON bl.booking_id = b.booking_id
            LEFT JOIN customer AS c ON c.customer_id = b.booking_customer_id
            LEFT JOIN booking_block AS bb ON bb.booking_id = b.booking_id
            WHERE b.company_id = {$company_id} AND b.is_deleted != '1'
            GROUP BY b.booking_id
            ORDER BY bl.created_at DESC
            LIMIT {$limit}
        ")->result_array();

        foreach ($rows as &$row) {
            $row['source'] = $this->resolve_booking_source_label($row['source_id']);
            unset($row['source_id']);
        }
        unset($row);

        return $rows;
    }

    /**
     * Staff/employee count for this property.
     */
    public function get_staff_count($company_id)
    {
        $company_id = (int) $company_id;
        $row = $this->db->query("
            SELECT COUNT(DISTINCT up.user_id) AS total
            FROM user_permissions AS up
            WHERE up.company_id = {$company_id}
        ")->row_array();
        return (int) $row['total'];
    }

    /**
     * Year-to-date revenue summary.
     */
    public function get_ytd_revenue($company_id, $today)
    {
        $company_id  = (int) $company_id;
        $year_start  = date('Y-01-01', strtotime($today));

        $row = $this->db->query("
            SELECT COALESCE(SUM(p.amount), 0) AS total
            FROM payment AS p
            INNER JOIN payment_type AS pt ON pt.payment_type_id = p.payment_type_id AND pt.company_id = {$company_id}
            INNER JOIN booking AS b ON b.booking_id = p.booking_id AND b.is_deleted != '1'
            WHERE p.is_deleted = '0'
              AND p.selling_date BETWEEN '{$year_start}' AND '{$today}'
        ")->row_array();

        return round((float) $row['total'], 2);
    }
}
