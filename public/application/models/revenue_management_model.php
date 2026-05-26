<?php

class Revenue_management_model extends CI_Model
{
    public function get_rate_plan_options($company_id)
    {
        $this->load->model('Rate_plan_model');
        $this->load->model('Room_type_model');

        $room_types = $this->Room_type_model->get_room_types($company_id);
        $options = array();

        if (!$room_types) {
            return $options;
        }

        foreach ($room_types as $room_type) {
            $plans = $this->Rate_plan_model->get_rate_plans_by_room_type_id($room_type['id'], null, false);
            if (!$plans) {
                continue;
            }

            foreach ($plans as $plan) {
                $options[] = array(
                    'rate_plan_id' => $plan['rate_plan_id'],
                    'rate_plan_name' => $plan['rate_plan_name'],
                    'room_type_id' => $room_type['id'],
                    'room_type_name' => $room_type['name'],
                );
            }
        }

        return $options;
    }

    public function get_revenue_forecast($company_id, $start_date, $end_date)
    {
        $company_id = (int) $company_id;
        $start_date = $this->db->escape_str($start_date);
        $end_date = $this->db->escape_str($end_date);
        $end_exclusive = date('Y-m-d', strtotime($end_date . ' +1 day'));

        $this->load->model('Room_model');
        $this->load->model('Room_type_model');
        $this->load->model('Rate_model');

        $total_rooms = (int) $this->Room_model->get_number_of_rooms($company_id);
        if ($total_rooms < 1) {
            $total_rooms = 1;
        }

        $active_states = RESERVATION . ',' . INHOUSE . ',' . UNCONFIRMED_RESERVATION;

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

        $occupied_by_date = array();
        foreach ($occupancy_rows as $row) {
            $occupied_by_date[$row['date']] = (int) $row['occupied_rooms'];
        }

        $room_types = $this->Room_type_model->get_room_types($company_id);
        $bar_rate_plan_ids = array();
        $default_rates_index = array();

        if ($room_types) {
            foreach ($room_types as $room_type) {
                if (empty($room_type['default_room_charge'])) {
                    continue;
                }

                $bar_rate_plan_ids[] = $room_type['default_room_charge'];
                $daily_rates = $this->Rate_model->get_daily_rates(
                    $room_type['default_room_charge'],
                    $start_date,
                    $end_exclusive
                );

                foreach ($daily_rates as $rate) {
                    $default_rates_index[$room_type['id']][$rate['date']] = (float) (
                        isset($rate['adult_1_rate']) ? $rate['adult_1_rate'] : 0
                    );
                }
            }
        }

        $bar_by_date = array();
        if (count($bar_rate_plan_ids) > 0) {
            $bar_rates = $this->Rate_model->get_daily_rates_optimized(
                array_unique($bar_rate_plan_ids),
                $start_date,
                $end_exclusive
            );

            foreach ($bar_rates as $rate) {
                $date = $rate['date'];
                $value = (float) (isset($rate['adult_1_rate']) ? $rate['adult_1_rate'] : 0);

                if (!isset($bar_by_date[$date])) {
                    $bar_by_date[$date] = array('sum' => 0, 'count' => 0);
                }

                $bar_by_date[$date]['sum'] += $value;
                $bar_by_date[$date]['count']++;
            }
        }

        $on_books_rows = $this->db->query("
            SELECT
                di.date,
                b.rate_plan_id,
                b.use_rate_plan,
                brh.room_type_id,
                COUNT(*) AS room_nights
            FROM date_interval AS di
            INNER JOIN booking_block AS brh ON
                DATE(brh.check_in_date) <= di.date
                AND DATE(brh.check_out_date) > di.date
            INNER JOIN booking AS b ON
                b.booking_id = brh.booking_id
                AND b.company_id = {$company_id}
                AND b.is_deleted != '1'
                AND b.state IN ({$active_states})
            WHERE
                di.date >= '{$start_date}'
                AND di.date <= '{$end_date}'
            GROUP BY di.date, b.rate_plan_id, b.use_rate_plan, brh.room_type_id
        ")->result_array();

        $forecast_plan_ids = array();
        foreach ($on_books_rows as $row) {
            if ($row['use_rate_plan'] == '1' && !empty($row['rate_plan_id'])) {
                $forecast_plan_ids[] = $row['rate_plan_id'];
            }
        }

        $on_books_rates_index = array();
        if (count($forecast_plan_ids) > 0) {
            $booking_rates = $this->Rate_model->get_daily_rates_optimized(
                array_unique($forecast_plan_ids),
                $start_date,
                $end_exclusive
            );

            foreach ($booking_rates as $rate) {
                $on_books_rates_index[$rate['rate_plan_id']][$rate['date']] = (float) (
                    isset($rate['adult_1_rate']) ? $rate['adult_1_rate'] : 0
                );
            }
        }

        $on_books_by_date = array();
        foreach ($on_books_rows as $row) {
            $date = $row['date'];
            $room_nights = (int) $row['room_nights'];
            $nightly_rate = 0;

            if ($row['use_rate_plan'] == '1' && !empty($row['rate_plan_id'])) {
                $nightly_rate = isset($on_books_rates_index[$row['rate_plan_id']][$date])
                    ? $on_books_rates_index[$row['rate_plan_id']][$date]
                    : 0;
            } elseif (!empty($row['room_type_id']) && isset($default_rates_index[$row['room_type_id']][$date])) {
                $nightly_rate = $default_rates_index[$row['room_type_id']][$date];
            } elseif (isset($bar_by_date[$date]) && $bar_by_date[$date]['count'] > 0) {
                $nightly_rate = $bar_by_date[$date]['sum'] / $bar_by_date[$date]['count'];
            }

            if (!isset($on_books_by_date[$date])) {
                $on_books_by_date[$date] = 0;
            }

            $on_books_by_date[$date] += $room_nights * $nightly_rate;
        }

        $labels = array();
        $on_books_series = array();
        $potential_series = array();
        $total_series = array();
        $occupancy_series = array();
        $adr_series = array();
        $revpar_series = array();
        $rows = array();
        $summary_on_books = 0;
        $summary_potential = 0;
        $summary_occupancy = 0;
        $day_count = 0;

        $cursor = strtotime($start_date);
        $end_ts = strtotime($end_date);

        while ($cursor <= $end_ts) {
            $date = date('Y-m-d', $cursor);
            $occupied = isset($occupied_by_date[$date]) ? $occupied_by_date[$date] : 0;
            $available = max(0, $total_rooms - $occupied);

            $bar = 0;
            if (isset($bar_by_date[$date]) && $bar_by_date[$date]['count'] > 0) {
                $bar = $bar_by_date[$date]['sum'] / $bar_by_date[$date]['count'];
            }

            $on_books = isset($on_books_by_date[$date]) ? $on_books_by_date[$date] : 0;
            $potential = $available * $bar;
            $forecast_total = $on_books + $potential;
            $occupancy_pct = round(($occupied / $total_rooms) * 100);
            $adr = $occupied > 0 ? round($on_books / $occupied, 2) : 0;
            $revpar = round($on_books / $total_rooms, 2);

            $labels[] = date('M j', $cursor);
            $on_books_series[] = round($on_books, 2);
            $potential_series[] = round($potential, 2);
            $total_series[] = round($forecast_total, 2);
            $occupancy_series[] = $occupancy_pct;
            $adr_series[] = $adr;
            $revpar_series[] = $revpar;

            $rows[] = array(
                'date' => $date,
                'occupied' => $occupied,
                'available' => $available,
                'occupancy_pct' => $occupancy_pct,
                'on_books_revenue' => round($on_books, 2),
                'potential_revenue' => round($potential, 2),
                'forecast_total' => round($forecast_total, 2),
                'adr' => $adr,
                'revpar' => $revpar,
            );

            $summary_on_books += $on_books;
            $summary_potential += $potential;
            $summary_occupancy += $occupancy_pct;
            $day_count++;

            $cursor = strtotime('+1 day', $cursor);
        }

        return array(
            'labels' => $labels,
            'on_books' => $on_books_series,
            'potential' => $potential_series,
            'forecast_total' => $total_series,
            'occupancy' => $occupancy_series,
            'adr' => $adr_series,
            'revpar' => $revpar_series,
            'rows' => $rows,
            'total_rooms' => $total_rooms,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'summary' => array(
                'on_books_revenue' => round($summary_on_books, 2),
                'potential_revenue' => round($summary_potential, 2),
                'forecast_total' => round($summary_on_books + $summary_potential, 2),
                'avg_occupancy' => $day_count > 0 ? round($summary_occupancy / $day_count) : 0,
            ),
        );
    }
}
