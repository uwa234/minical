<?php

class Dashboard_model extends CI_Model
{
    public function get_operational_snapshot($company_id, $selling_date)
    {
        $company_id = (int) $company_id;
        $selling_date = $this->db->escape_str($selling_date);
        $next_date = date('Y-m-d', strtotime($selling_date . ' +1 day'));
        $check_in = $selling_date . ' 00:00:00';
        $check_out = $next_date . ' 00:00:00';

        $active_states = RESERVATION . ',' . INHOUSE . ',' . UNCONFIRMED_RESERVATION;

        $arrivals_count_row = $this->db->query("
            SELECT COUNT(DISTINCT b.booking_id) AS total
            FROM booking AS b
            INNER JOIN booking_block AS brh ON brh.booking_id = b.booking_id
            WHERE
                b.company_id = {$company_id}
                AND b.is_deleted != '1'
                AND b.state IN (" . RESERVATION . ',' . UNCONFIRMED_RESERVATION . ")
                AND DATE(brh.check_in_date) = '{$selling_date}'
        ")->row_array();

        $departures_count_row = $this->db->query("
            SELECT COUNT(DISTINCT b.booking_id) AS total
            FROM booking AS b
            INNER JOIN booking_block AS brh ON brh.booking_id = b.booking_id
            WHERE
                b.company_id = {$company_id}
                AND b.is_deleted != '1'
                AND b.state = " . INHOUSE . "
                AND DATE(brh.check_out_date) = '{$selling_date}'
        ")->row_array();

        $arrivals = $this->db->query("
            SELECT
                b.booking_id,
                b.state,
                b.balance,
                c.customer_name,
                MIN(brh.check_in_date) AS check_in_date,
                MAX(brh.check_out_date) AS check_out_date,
                GROUP_CONCAT(DISTINCT r.room_name ORDER BY r.room_name SEPARATOR ', ') AS room_names
            FROM booking AS b
            INNER JOIN booking_block AS brh ON brh.booking_id = b.booking_id
            LEFT JOIN customer AS c ON c.customer_id = b.booking_customer_id
            LEFT JOIN room AS r ON r.room_id = brh.room_id AND r.is_deleted = '0'
            WHERE
                b.company_id = {$company_id}
                AND b.is_deleted != '1'
                AND b.state IN (" . RESERVATION . ',' . UNCONFIRMED_RESERVATION . ")
                AND DATE(brh.check_in_date) = '{$selling_date}'
            GROUP BY b.booking_id
            ORDER BY check_in_date ASC, b.booking_id ASC
            LIMIT 20
        ")->result_array();

        $departures = $this->db->query("
            SELECT
                b.booking_id,
                b.state,
                b.balance,
                c.customer_name,
                MIN(brh.check_in_date) AS check_in_date,
                MAX(brh.check_out_date) AS check_out_date,
                GROUP_CONCAT(DISTINCT r.room_name ORDER BY r.room_name SEPARATOR ', ') AS room_names
            FROM booking AS b
            INNER JOIN booking_block AS brh ON brh.booking_id = b.booking_id
            LEFT JOIN customer AS c ON c.customer_id = b.booking_customer_id
            LEFT JOIN room AS r ON r.room_id = brh.room_id AND r.is_deleted = '0'
            WHERE
                b.company_id = {$company_id}
                AND b.is_deleted != '1'
                AND b.state = " . INHOUSE . "
                AND DATE(brh.check_out_date) = '{$selling_date}'
            GROUP BY b.booking_id
            ORDER BY check_out_date ASC, b.booking_id ASC
            LIMIT 20
        ")->result_array();

        $in_house_row = $this->db->query("
            SELECT COUNT(DISTINCT b.booking_id) AS total
            FROM booking AS b
            INNER JOIN booking_block AS brh ON brh.booking_id = b.booking_id
            WHERE
                b.company_id = {$company_id}
                AND b.is_deleted != '1'
                AND b.state = " . INHOUSE . "
                AND DATE(brh.check_in_date) <= '{$selling_date}'
                AND DATE(brh.check_out_date) > '{$selling_date}'
        ")->row_array();

        $balance_row = $this->db->query("
            SELECT
                COUNT(*) AS booking_count,
                COALESCE(SUM(b.balance), 0) AS balance_total
            FROM booking AS b
            WHERE
                b.company_id = {$company_id}
                AND b.is_deleted != '1'
                AND b.state IN ({$active_states})
                AND b.balance > 0.005
        ")->row_array();

        $unassigned_row = $this->db->query("
            SELECT COUNT(DISTINCT b.booking_id) AS total
            FROM booking AS b
            INNER JOIN booking_block AS brh ON brh.booking_id = b.booking_id
            WHERE
                b.company_id = {$company_id}
                AND b.is_deleted != '1'
                AND b.state IN ({$active_states})
                AND DATE(brh.check_in_date) <= '{$selling_date}'
                AND DATE(brh.check_out_date) > '{$selling_date}'
                AND (brh.room_id IS NULL OR brh.room_id = 0)
        ")->row_array();

        $unpaid_stays = $this->db->query("
            SELECT
                b.booking_id,
                b.balance,
                c.customer_name,
                GROUP_CONCAT(DISTINCT r.room_name ORDER BY r.room_name SEPARATOR ', ') AS room_names
            FROM booking AS b
            LEFT JOIN customer AS c ON c.customer_id = b.booking_customer_id
            LEFT JOIN booking_block AS brh ON brh.booking_id = b.booking_id
            LEFT JOIN room AS r ON r.room_id = brh.room_id AND r.is_deleted = '0'
            WHERE
                b.company_id = {$company_id}
                AND b.is_deleted != '1'
                AND b.state IN ({$active_states})
                AND b.balance > 0.005
            GROUP BY b.booking_id
            ORDER BY b.balance DESC
            LIMIT 15
        ")->result_array();

        $unassigned_stays = $this->db->query("
            SELECT
                b.booking_id,
                b.state,
                c.customer_name,
                MIN(brh.check_in_date) AS check_in_date,
                MAX(brh.check_out_date) AS check_out_date
            FROM booking AS b
            INNER JOIN booking_block AS brh ON brh.booking_id = b.booking_id
            LEFT JOIN customer AS c ON c.customer_id = b.booking_customer_id
            WHERE
                b.company_id = {$company_id}
                AND b.is_deleted != '1'
                AND b.state IN ({$active_states})
                AND DATE(brh.check_in_date) <= '{$selling_date}'
                AND DATE(brh.check_out_date) > '{$selling_date}'
                AND (brh.room_id IS NULL OR brh.room_id = 0)
            GROUP BY b.booking_id
            ORDER BY check_in_date ASC
            LIMIT 15
        ")->result_array();

        $this->load->model('Room_type_model');
        $inventory = $this->Room_type_model->get_room_types_and_availabilities($company_id, $check_in, $check_out);

        $total_rooms = 0;
        $occupied_rooms = 0;

        foreach ($inventory['available_room_types'] as $room_type) {
            $total_rooms += (int) $room_type['availability'];
        }

        foreach ($inventory['occupancies'] as $occupancy) {
            $occupied_rooms += (int) $occupancy['occupancy'];
        }

        $occupancy_rate = $total_rooms > 0
            ? round(($occupied_rooms / $total_rooms) * 100)
            : 0;

        return array(
            'arrivals' => $arrivals,
            'arrivals_count' => isset($arrivals_count_row['total']) ? (int) $arrivals_count_row['total'] : 0,
            'departures' => $departures,
            'departures_count' => isset($departures_count_row['total']) ? (int) $departures_count_row['total'] : 0,
            'in_house_count' => isset($in_house_row['total']) ? (int) $in_house_row['total'] : 0,
            'outstanding_count' => isset($balance_row['booking_count']) ? (int) $balance_row['booking_count'] : 0,
            'outstanding_total' => isset($balance_row['balance_total']) ? (float) $balance_row['balance_total'] : 0,
            'unassigned_count' => isset($unassigned_row['total']) ? (int) $unassigned_row['total'] : 0,
            'unpaid_stays' => $unpaid_stays,
            'unassigned_stays' => $unassigned_stays,
            'total_rooms' => $total_rooms,
            'occupied_rooms' => $occupied_rooms,
            'occupancy_rate' => $occupancy_rate,
        );
    }

    public function get_analytics($company_id, $end_date, $days = 14)
    {
        $company_id = (int) $company_id;
        $days = max(7, min(30, (int) $days));
        $end_date = $this->db->escape_str($end_date);
        $start_date = date('Y-m-d', strtotime($end_date . ' -' . ($days - 1) . ' days'));

        $this->load->model('Room_model');
        $total_rooms = (int) $this->Room_model->get_number_of_rooms($company_id);
        if ($total_rooms < 1) {
            $total_rooms = 1;
        }

        $active_states = RESERVATION . ',' . INHOUSE . ',' . UNCONFIRMED_RESERVATION;

        $revenue_rows = $this->db->query("
            SELECT
                di.date,
                COALESCE(report_table.total_payment, 0) AS revenue
            FROM date_interval AS di
            LEFT JOIN (
                SELECT
                    p.selling_date,
                    SUM(p.amount) AS total_payment
                FROM payment AS p
                INNER JOIN payment_type AS pt ON
                    pt.payment_type_id = p.payment_type_id
                    AND pt.company_id = {$company_id}
                INNER JOIN booking AS b ON
                    b.booking_id = p.booking_id
                    AND b.is_deleted != '1'
                WHERE
                    p.is_deleted = '0'
                    AND p.selling_date >= '{$start_date}'
                    AND p.selling_date <= '{$end_date}'
                GROUP BY p.selling_date
            ) AS report_table ON report_table.selling_date = di.date
            WHERE
                di.date >= '{$start_date}'
                AND di.date <= '{$end_date}'
            ORDER BY di.date ASC
        ")->result_array();

        $occupancy_rows = $this->db->query("
            SELECT
                di.date,
                COALESCE(occ.occupied_rooms, 0) AS occupied_rooms
            FROM date_interval AS di
            LEFT JOIN (
                SELECT
                    di_inner.date,
                    COUNT(DISTINCT brh.room_id) AS occupied_rooms
                FROM date_interval AS di_inner
                INNER JOIN booking_block AS brh ON
                    DATE(brh.check_in_date) <= di_inner.date
                    AND DATE(brh.check_out_date) > di_inner.date
                    AND brh.room_id IS NOT NULL
                    AND brh.room_id != 0
                INNER JOIN booking AS b ON
                    b.booking_id = brh.booking_id
                    AND b.company_id = {$company_id}
                    AND b.is_deleted != '1'
                    AND b.state IN ({$active_states})
                WHERE
                    di_inner.date >= '{$start_date}'
                    AND di_inner.date <= '{$end_date}'
                GROUP BY di_inner.date
            ) AS occ ON occ.date = di.date
            WHERE
                di.date >= '{$start_date}'
                AND di.date <= '{$end_date}'
            ORDER BY di.date ASC
        ")->result_array();

        $arrival_rows = $this->db->query("
            SELECT
                DATE(brh.check_in_date) AS date,
                COUNT(DISTINCT b.booking_id) AS arrivals
            FROM booking AS b
            INNER JOIN booking_block AS brh ON brh.booking_id = b.booking_id
            WHERE
                b.company_id = {$company_id}
                AND b.is_deleted != '1'
                AND b.state IN ({$active_states})
                AND DATE(brh.check_in_date) >= '{$start_date}'
                AND DATE(brh.check_in_date) <= '{$end_date}'
            GROUP BY DATE(brh.check_in_date)
            ORDER BY date ASC
        ")->result_array();

        $arrivals_by_date = array();
        foreach ($arrival_rows as $row) {
            $arrivals_by_date[$row['date']] = (int) $row['arrivals'];
        }

        $labels = array();
        $revenue_series = array();
        $occupancy_series = array();
        $arrivals_series = array();
        $period_revenue = 0;
        $period_arrivals = 0;
        $occupancy_sum = 0;
        $occupancy_days = 0;

        $revenue_by_date = array();
        foreach ($revenue_rows as $row) {
            $revenue_by_date[$row['date']] = (float) $row['revenue'];
        }

        $occupied_by_date = array();
        foreach ($occupancy_rows as $row) {
            $occupied_by_date[$row['date']] = (int) $row['occupied_rooms'];
        }

        $cursor = strtotime($start_date);
        $end_ts = strtotime($end_date);
        while ($cursor <= $end_ts) {
            $date = date('Y-m-d', $cursor);
            $label = date('M j', $cursor);
            $revenue = isset($revenue_by_date[$date]) ? $revenue_by_date[$date] : 0;
            $occupied = isset($occupied_by_date[$date]) ? $occupied_by_date[$date] : 0;
            $arrivals = isset($arrivals_by_date[$date]) ? $arrivals_by_date[$date] : 0;
            $occupancy_pct = round(($occupied / $total_rooms) * 100);

            $labels[] = $label;
            $revenue_series[] = round($revenue, 2);
            $occupancy_series[] = $occupancy_pct;
            $arrivals_series[] = $arrivals;

            $period_revenue += $revenue;
            $period_arrivals += $arrivals;
            if ($date <= $end_date) {
                $occupancy_sum += $occupancy_pct;
                $occupancy_days++;
            }

            $cursor = strtotime('+1 day', $cursor);
        }

        $avg_occupancy = $occupancy_days > 0 ? round($occupancy_sum / $occupancy_days) : 0;

        return array(
            'days' => $days,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'labels' => $labels,
            'revenue' => $revenue_series,
            'occupancy' => $occupancy_series,
            'arrivals' => $arrivals_series,
            'summary' => array(
                'period_revenue' => round($period_revenue, 2),
                'period_arrivals' => $period_arrivals,
                'avg_occupancy' => $avg_occupancy,
                'total_rooms' => $total_rooms,
            ),
        );
    }
}
