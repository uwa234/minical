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

    /**
     * Per room-type snapshot for the property overview tab.
     */
    public function get_room_type_overview($company_id, $as_of_date, $horizon_days = 7)
    {
        $company_id = (int) $company_id;
        $as_of_date = $this->db->escape_str($as_of_date);
        $horizon_days = max(1, min(60, (int) $horizon_days));
        $end_date = date('Y-m-d', strtotime($as_of_date . ' +' . ($horizon_days - 1) . ' days'));

        $this->load->model('Room_type_model');
        $this->load->model('Room_model');
        $this->load->model('Rate_model');

        $room_types = $this->Room_type_model->get_room_types($company_id);
        $active_states = RESERVATION . ',' . INHOUSE . ',' . UNCONFIRMED_RESERVATION;
        $overview = array();

        if (!$room_types) {
            return $overview;
        }

        foreach ($room_types as $room_type) {
            $room_type_id = (int) $room_type['id'];
            $count_row = $this->Room_model->get_room_count_by_room_type_id($room_type_id);
            $rooms_count = isset($count_row['room_count']) ? (int) $count_row['room_count'] : 0;
            if ($rooms_count < 1) {
                continue;
            }

            $occ_row = $this->db->query("
                SELECT COUNT(DISTINCT brh.room_id) AS occupied_rooms
                FROM booking_block AS brh
                INNER JOIN booking AS b ON
                    b.booking_id = brh.booking_id
                    AND b.company_id = {$company_id}
                    AND b.is_deleted != '1'
                    AND b.state IN ({$active_states})
                INNER JOIN room AS r ON r.room_id = brh.room_id AND r.room_type_id = {$room_type_id}
                WHERE
                    DATE(brh.check_in_date) <= '{$as_of_date}'
                    AND DATE(brh.check_out_date) > '{$as_of_date}'
                    AND brh.room_id IS NOT NULL
                    AND brh.room_id != 0
            ")->row_array();

            $occupied = isset($occ_row['occupied_rooms']) ? (int) $occ_row['occupied_rooms'] : 0;
            $occupancy_pct = round(($occupied / $rooms_count) * 100);

            $bar_rate = 0;
            if (!empty($room_type['default_room_charge'])) {
                $rates = $this->Rate_model->get_daily_rates(
                    $room_type['default_room_charge'],
                    $as_of_date,
                    date('Y-m-d', strtotime($as_of_date . ' +1 day'))
                );
                if (!empty($rates[0]['adult_1_rate'])) {
                    $bar_rate = (float) $rates[0]['adult_1_rate'];
                }
            }

            $avg_occ_row = $this->db->query("
                SELECT COALESCE(AVG(daily_occ.occupancy_pct), 0) AS avg_occupancy
                FROM (
                    SELECT
                        di.date,
                        ROUND(
                            COALESCE(occ.occupied_rooms, 0) / {$rooms_count} * 100
                        ) AS occupancy_pct
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
                        INNER JOIN room AS r ON r.room_id = brh.room_id AND r.room_type_id = {$room_type_id}
                        WHERE
                            di_inner.date >= '{$as_of_date}'
                            AND di_inner.date <= '{$end_date}'
                        GROUP BY di_inner.date
                    ) AS occ ON occ.date = di.date
                    WHERE
                        di.date >= '{$as_of_date}'
                        AND di.date <= '{$end_date}'
                ) AS daily_occ
            ")->row_array();

            $overview[] = array(
                'room_type_id' => $room_type_id,
                'room_type_name' => $room_type['name'],
                'rooms_count' => $rooms_count,
                'occupied_today' => $occupied,
                'occupancy_pct' => $occupancy_pct,
                'avg_occupancy_pct' => isset($avg_occ_row['avg_occupancy'])
                    ? (int) round($avg_occ_row['avg_occupancy'])
                    : 0,
                'bar_rate' => round($bar_rate, 2),
            );
        }

        return $overview;
    }

    /**
     * Booking pickup: room-nights added in the last N days for future stay dates.
     */
    public function get_pickup_metrics($company_id, $start_date, $end_date, $pickup_days = 7)
    {
        $company_id = (int) $company_id;
        $start_date = $this->db->escape_str($start_date);
        $end_date = $this->db->escape_str($end_date);
        $pickup_days = max(1, min(30, (int) $pickup_days));
        $pickup_since = date('Y-m-d', strtotime('-' . $pickup_days . ' days'));

        $active_states = RESERVATION . ',' . INHOUSE . ',' . UNCONFIRMED_RESERVATION;
        $created_subquery = "
            SELECT
                b.booking_id,
                COALESCE(
                    (SELECT MIN(bl.date_time) FROM booking_log AS bl WHERE bl.booking_id = b.booking_id),
                    (SELECT MIN(bb.check_in_date) FROM booking_block AS bb WHERE bb.booking_id = b.booking_id)
                ) AS created_at
            FROM booking AS b
            WHERE b.company_id = {$company_id}
        ";

        $row = $this->db->query("
            SELECT COUNT(*) AS room_nights
            FROM booking_block AS brh
            INNER JOIN booking AS b ON
                b.booking_id = brh.booking_id
                AND b.company_id = {$company_id}
                AND b.is_deleted != '1'
                AND b.state IN ({$active_states})
            INNER JOIN ({$created_subquery}) AS bc ON bc.booking_id = b.booking_id
            INNER JOIN date_interval AS di ON
                di.date >= DATE(brh.check_in_date)
                AND di.date < DATE(brh.check_out_date)
            WHERE
                di.date >= '{$start_date}'
                AND di.date <= '{$end_date}'
                AND DATE(bc.created_at) >= '{$pickup_since}'
        ")->row_array();

        return array(
            'pickup_days' => $pickup_days,
            'room_nights' => isset($row['room_nights']) ? (int) $row['room_nights'] : 0,
            'since_date' => $pickup_since,
        );
    }

    /**
     * Compare forecast occupancy to the same calendar window one year ago.
     */
    public function get_yoy_comparison($company_id, $start_date, $end_date)
    {
        $prior_start = date('Y-m-d', strtotime($start_date . ' -1 year'));
        $prior_end = date('Y-m-d', strtotime($end_date . ' -1 year'));

        $current = $this->get_revenue_forecast($company_id, $start_date, $end_date);
        $prior = $this->get_revenue_forecast($company_id, $prior_start, $prior_end);

        $current_occ = isset($current['summary']['avg_occupancy']) ? (int) $current['summary']['avg_occupancy'] : 0;
        $prior_occ = isset($prior['summary']['avg_occupancy']) ? (int) $prior['summary']['avg_occupancy'] : 0;
        $occ_change = $prior_occ > 0 ? round((($current_occ - $prior_occ) / $prior_occ) * 100) : 0;

        $current_total = isset($current['summary']['forecast_total'])
            ? (float) $current['summary']['forecast_total']
            : 0;
        $prior_total = isset($prior['summary']['forecast_total'])
            ? (float) $prior['summary']['forecast_total']
            : 0;
        $rev_change = $prior_total > 0
            ? round((($current_total - $prior_total) / $prior_total) * 100)
            : 0;

        return array(
            'current_avg_occupancy' => $current_occ,
            'prior_avg_occupancy' => $prior_occ,
            'occupancy_change_pct' => $occ_change,
            'current_forecast_total' => round($current_total, 2),
            'prior_forecast_total' => round($prior_total, 2),
            'forecast_change_pct' => $rev_change,
            'prior_start_date' => $prior_start,
            'prior_end_date' => $prior_end,
        );
    }

    /**
     * Deterministic suggestions used for AI context and as a fallback when no LLM is configured.
     */
    public function build_rule_based_suggestions($forecast)
    {
        $suggestions = array();
        $rows = isset($forecast['rows']) ? $forecast['rows'] : array();
        $avg_occ = isset($forecast['summary']['avg_occupancy'])
            ? (int) $forecast['summary']['avg_occupancy']
            : 0;

        foreach ($rows as $row) {
            $occ = (int) $row['occupancy_pct'];
            $date = $row['date'];

            if ($occ < 35 && $row['available'] > 0) {
                $suggestions[] = array(
                    'type' => 'rate_decrease',
                    'date' => $date,
                    'title' => 'Low occupancy on ' . date('M j', strtotime($date)),
                    'detail' => $occ . '% occupied with ' . (int) $row['available'] . ' rooms open. Consider a 5–10% BAR reduction or a short promotion.',
                    'priority' => 'high',
                );
            } elseif ($occ >= 85) {
                $suggestions[] = array(
                    'type' => 'rate_increase',
                    'date' => $date,
                    'title' => 'Strong demand on ' . date('M j', strtotime($date)),
                    'detail' => $occ . '% occupied. Test a modest rate increase on BAR.',
                    'priority' => 'medium',
                );
                $suggestions[] = array(
                    'type' => 'restriction',
                    'date' => $date,
                    'title' => 'Protect ' . date('M j', strtotime($date)) . ' with min stay',
                    'detail' => $occ . '% occupied. Consider a 2-night minimum to reduce short stays.',
                    'priority' => 'medium',
                );
            }
        }

        if ($avg_occ < 50 && count($suggestions) === 0) {
            $suggestions[] = array(
                'type' => 'strategy',
                'date' => '',
                'title' => 'Soft demand period',
                'detail' => 'Average occupancy is ' . $avg_occ . '% for this window. Review channel mix and weekend BAR gaps.',
                'priority' => 'medium',
            );
        }

        $suggestions = array_slice($suggestions, 0, 6);

        return $this->enrich_suggestions_with_actions($suggestions);
    }

    /**
     * Default BAR rate plans (one per room type) used for one-click apply actions.
     */
    public function get_default_bar_rate_plans($company_id)
    {
        $this->load->model('Room_type_model');
        $room_types = $this->Room_type_model->get_room_types($company_id);
        $plans = array();

        if (!$room_types) {
            return $plans;
        }

        foreach ($room_types as $room_type) {
            if (empty($room_type['default_room_charge'])) {
                continue;
            }

            $rate_plan_id = (int) $room_type['default_room_charge'];
            $plans[$rate_plan_id] = array(
                'rate_plan_id' => $rate_plan_id,
                'room_type_id' => (int) $room_type['id'],
                'room_type_name' => $room_type['name'],
            );
        }

        return array_values($plans);
    }

    /**
     * Attach executable action metadata to suggestion cards.
     */
    public function enrich_suggestions_with_actions($suggestions)
    {
        if (!is_array($suggestions)) {
            return array();
        }

        foreach ($suggestions as $index => $suggestion) {
            if (!empty($suggestion['action'])) {
                $suggestions[$index]['applicable'] = true;
                continue;
            }

            $type = isset($suggestion['type']) ? $suggestion['type'] : '';
            $date = isset($suggestion['date']) ? $suggestion['date'] : '';

            if ($type === 'rate_decrease' && $date) {
                $suggestions[$index]['action'] = 'rate_adjust';
                $suggestions[$index]['adjustment_pct'] = -8;
                $suggestions[$index]['apply_label'] = 'Apply −8% BAR';
                $suggestions[$index]['applicable'] = true;
            } elseif ($type === 'rate_increase' && $date) {
                $suggestions[$index]['action'] = 'rate_adjust';
                $suggestions[$index]['adjustment_pct'] = 5;
                $suggestions[$index]['apply_label'] = 'Apply +5% BAR';
                $suggestions[$index]['applicable'] = true;
            } elseif ($type === 'restriction' && $date) {
                $suggestions[$index]['action'] = 'min_stay';
                $suggestions[$index]['field'] = 'minimum_length_of_stay';
                $suggestions[$index]['value'] = 2;
                $suggestions[$index]['apply_label'] = 'Set 2-night min stay';
                $suggestions[$index]['applicable'] = true;
            } else {
                $suggestions[$index]['action'] = '';
                $suggestions[$index]['applicable'] = false;
            }
        }

        return $suggestions;
    }

    /**
     * Structured payload for the revenue AI assistant.
     */
    public function build_insights_context($company_id, $selling_date, $forecast_days = 28)
    {
        $forecast_days = max(14, min(60, (int) $forecast_days));
        $forecast_end = date('Y-m-d', strtotime($selling_date . ' +' . ($forecast_days - 1) . ' days'));

        $forecast = $this->get_revenue_forecast($company_id, $selling_date, $forecast_end);
        $pickup = $this->get_pickup_metrics($company_id, $selling_date, $forecast_end, 7);
        $yoy = $this->get_yoy_comparison($company_id, $selling_date, $forecast_end);
        $overview = $this->get_room_type_overview($company_id, $selling_date, min(14, $forecast_days));
        $suggestions = $this->build_rule_based_suggestions($forecast);
        $default_bar_plans = $this->get_default_bar_rate_plans($company_id);

        return array(
            'selling_date' => $selling_date,
            'forecast_days' => $forecast_days,
            'default_bar_plans' => $default_bar_plans,
            'forecast' => array(
                'summary' => isset($forecast['summary']) ? $forecast['summary'] : array(),
                'total_rooms' => isset($forecast['total_rooms']) ? $forecast['total_rooms'] : 0,
                'start_date' => isset($forecast['start_date']) ? $forecast['start_date'] : $selling_date,
                'end_date' => isset($forecast['end_date']) ? $forecast['end_date'] : $forecast_end,
                'low_occupancy_dates' => $this->extract_extreme_dates($forecast, 'low'),
                'high_occupancy_dates' => $this->extract_extreme_dates($forecast, 'high'),
            ),
            'pickup' => $pickup,
            'yoy' => $yoy,
            'room_types' => $overview,
            'rule_suggestions' => $suggestions,
        );
    }

    private function extract_extreme_dates($forecast, $mode = 'low')
    {
        $rows = isset($forecast['rows']) ? $forecast['rows'] : array();
        $dates = array();

        foreach ($rows as $row) {
            $occ = (int) $row['occupancy_pct'];
            if ($mode === 'low' && $occ <= 40) {
                $dates[] = array('date' => $row['date'], 'occupancy_pct' => $occ);
            } elseif ($mode === 'high' && $occ >= 80) {
                $dates[] = array('date' => $row['date'], 'occupancy_pct' => $occ);
            }
        }

        return array_slice($dates, 0, 5);
    }

    /**
     * Daily booking pace: current on-books vs same calendar date last year, plus recent pickup.
     */
    public function get_daily_pace_report($company_id, $start_date, $end_date, $pickup_days = 7)
    {
        $company_id = (int) $company_id;
        $start_date = $this->db->escape_str($start_date);
        $end_date = $this->db->escape_str($end_date);
        $pickup_days = max(1, min(30, (int) $pickup_days));
        $pickup_since = date('Y-m-d', strtotime('-' . $pickup_days . ' days'));

        $current = $this->get_revenue_forecast($company_id, $start_date, $end_date);
        $prior_start = date('Y-m-d', strtotime($start_date . ' -1 year'));
        $prior_end = date('Y-m-d', strtotime($end_date . ' -1 year'));
        $prior = $this->get_revenue_forecast($company_id, $prior_start, $prior_end);

        $prior_by_date = array();
        if (!empty($prior['rows'])) {
            foreach ($prior['rows'] as $row) {
                $prior_by_date[$row['date']] = $row;
            }
        }

        $active_states = RESERVATION . ',' . INHOUSE . ',' . UNCONFIRMED_RESERVATION;
        $created_subquery = "
            SELECT
                b.booking_id,
                COALESCE(
                    (SELECT MIN(bl.date_time) FROM booking_log AS bl WHERE bl.booking_id = b.booking_id),
                    (SELECT MIN(bb.check_in_date) FROM booking_block AS bb WHERE bb.booking_id = b.booking_id)
                ) AS created_at
            FROM booking AS b
            WHERE b.company_id = {$company_id}
        ";

        $pickup_rows = $this->db->query("
            SELECT di.date, COUNT(*) AS pickup_room_nights
            FROM date_interval AS di
            INNER JOIN booking_block AS brh ON
                di.date >= DATE(brh.check_in_date)
                AND di.date < DATE(brh.check_out_date)
            INNER JOIN booking AS b ON
                b.booking_id = brh.booking_id
                AND b.company_id = {$company_id}
                AND b.is_deleted != '1'
                AND b.state IN ({$active_states})
            INNER JOIN ({$created_subquery}) AS bc ON bc.booking_id = b.booking_id
            WHERE
                di.date >= '{$start_date}'
                AND di.date <= '{$end_date}'
                AND DATE(bc.created_at) >= '{$pickup_since}'
            GROUP BY di.date
        ")->result_array();

        $pickup_by_date = array();
        foreach ($pickup_rows as $pickup_row) {
            $pickup_by_date[$pickup_row['date']] = (int) $pickup_row['pickup_room_nights'];
        }

        $labels = array();
        $current_occ_series = array();
        $prior_occ_series = array();
        $pickup_series = array();
        $rows = array();
        $summary_pace_delta = 0;
        $summary_pickup = 0;
        $summary_days = 0;
        $days_ahead = 0;
        $days_behind = 0;

        $current_rows = isset($current['rows']) ? $current['rows'] : array();
        foreach ($current_rows as $row) {
            $date = $row['date'];
            $prior_date = date('Y-m-d', strtotime($date . ' -1 year'));
            $prior_row = isset($prior_by_date[$prior_date]) ? $prior_by_date[$prior_date] : null;

            $occupancy_pct = (int) $row['occupancy_pct'];
            $prior_occupancy_pct = $prior_row ? (int) $prior_row['occupancy_pct'] : 0;
            $prior_occupied = $prior_row ? (int) $prior_row['occupied'] : 0;
            $pace_delta = $occupancy_pct - $prior_occupancy_pct;
            $pickup = isset($pickup_by_date[$date]) ? $pickup_by_date[$date] : 0;

            $labels[] = date('M j', strtotime($date));
            $current_occ_series[] = $occupancy_pct;
            $prior_occ_series[] = $prior_occupancy_pct;
            $pickup_series[] = $pickup;

            $rows[] = array(
                'date' => $date,
                'prior_date' => $prior_date,
                'occupied' => (int) $row['occupied'],
                'prior_occupied' => $prior_occupied,
                'occupancy_pct' => $occupancy_pct,
                'prior_occupancy_pct' => $prior_occupancy_pct,
                'pace_delta' => $pace_delta,
                'pickup_room_nights' => $pickup,
                'on_books_revenue' => isset($row['on_books_revenue']) ? (float) $row['on_books_revenue'] : 0,
                'prior_on_books_revenue' => $prior_row ? (float) $prior_row['on_books_revenue'] : 0,
            );

            $summary_pace_delta += $pace_delta;
            $summary_pickup += $pickup;
            $summary_days++;
            if ($pace_delta > 0) {
                $days_ahead++;
            } elseif ($pace_delta < 0) {
                $days_behind++;
            }
        }

        return array(
            'labels' => $labels,
            'currentOccupancy' => $current_occ_series,
            'priorOccupancy' => $prior_occ_series,
            'pickup' => $pickup_series,
            'rows' => $rows,
            'pickup_days' => $pickup_days,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'summary' => array(
                'avg_pace_delta' => $summary_days > 0 ? round($summary_pace_delta / $summary_days) : 0,
                'total_pickup' => $summary_pickup,
                'days_ahead' => $days_ahead,
                'days_behind' => $days_behind,
                'day_count' => $summary_days,
            ),
        );
    }

    /**
     * Property-wide BAR grid: one row per room type default rate plan.
     */
    public function get_property_rate_grid($company_id, $start_date, $day_count = 14)
    {
        $company_id = (int) $company_id;
        $start_date = $this->db->escape_str($start_date);
        $day_count = max(7, min(28, (int) $day_count));
        $end_exclusive = date('Y-m-d', strtotime($start_date . ' +' . $day_count . ' days'));
        $end_date = date('Y-m-d', strtotime($end_exclusive . ' -1 day'));

        $this->load->model('Rate_model');
        $plans = $this->get_default_bar_rate_plans($company_id);
        $dates = array();
        $rows = array();

        foreach ($plans as $plan) {
            $rates = $this->Rate_model->get_daily_rates(
                $plan['rate_plan_id'],
                $start_date,
                $end_exclusive
            );

            $rate_by_date = array();
            foreach ($rates as $rate) {
                if (empty($dates) || !in_array($rate['date'], $dates, true)) {
                    $dates[] = $rate['date'];
                }
                $rate_by_date[$rate['date']] = array(
                    'adult_1_rate' => isset($rate['adult_1_rate']) ? $rate['adult_1_rate'] : null,
                    'adult_2_rate' => isset($rate['adult_2_rate']) ? $rate['adult_2_rate'] : null,
                );
            }

            $rows[] = array(
                'rate_plan_id' => (int) $plan['rate_plan_id'],
                'room_type_id' => (int) $plan['room_type_id'],
                'room_type_name' => $plan['room_type_name'],
                'rates_by_date' => $rate_by_date,
            );
        }

        if (empty($dates)) {
            $cursor = strtotime($start_date);
            $end_ts = strtotime($end_date);
            while ($cursor <= $end_ts) {
                $dates[] = date('Y-m-d', $cursor);
                $cursor = strtotime('+1 day', $cursor);
            }
        }

        return array(
            'dates' => $dates,
            'rows' => $rows,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'day_count' => $day_count,
        );
    }

    /**
     * Evaluate built-in pricing rules against the daily pace report.
     */
    public function evaluate_pricing_rules($company_id, $start_date, $end_date)
    {
        $pace = $this->get_daily_pace_report($company_id, $start_date, $end_date, 7);
        $actions = array();

        foreach ($pace['rows'] as $row) {
            if ($row['occupancy_pct'] < 35 && $row['pace_delta'] <= -5) {
                $actions[] = array(
                    'action' => 'rate_adjust',
                    'date' => $row['date'],
                    'adjustment_pct' => -5,
                    'reason' => 'Soft pace (' . $row['occupancy_pct'] . '% occ, '
                        . $row['pace_delta'] . ' pts vs STLY)',
                );
            } elseif ($row['occupancy_pct'] >= 85 && $row['pace_delta'] >= 0) {
                $actions[] = array(
                    'action' => 'rate_adjust',
                    'date' => $row['date'],
                    'adjustment_pct' => 5,
                    'reason' => 'Strong demand (' . $row['occupancy_pct'] . '% occ)',
                );
            } elseif ($row['pickup_room_nights'] < 1 && $row['occupancy_pct'] < 60) {
                $actions[] = array(
                    'action' => 'min_stay',
                    'date' => $row['date'],
                    'value' => 2,
                    'reason' => 'No recent pickup (' . $row['occupancy_pct'] . '% occ)',
                );
            }
        }

        return array(
            'actions' => array_slice($actions, 0, 20),
            'pace_summary' => isset($pace['summary']) ? $pace['summary'] : array(),
        );
    }

    /**
     * Connected distribution channels for parity context.
     */
    public function get_connected_channels($company_id)
    {
        $company_id = (int) $company_id;
        $channels = array();

        $this->load->model('Company_model');
        $company = $this->Company_model->get_company($company_id);
        if ($company && !empty($company['website_is_taking_online_reservation'])) {
            $channels[] = array(
                'key' => 'obe',
                'label' => 'Direct (OBE)',
                'connected' => true,
            );
        }

        if ($this->db->table_exists('channel_ical_mapping')) {
            $labels = array(
                'booking_dot_com' => 'Booking.com',
                'airbnb' => 'Airbnb',
                'expedia' => 'Expedia',
            );

            foreach ($labels as $key => $label) {
                $this->db->from('channel_ical_mapping');
                $this->db->where('company_id', $company_id);
                $this->db->where('channel_key', $key);
                $mappings = $this->db->get()->result_array();

                $connected = false;
                foreach ($mappings as $mapping) {
                    if (
                        (!empty($mapping['import_enabled']) && !empty($mapping['import_url']))
                        || !empty($mapping['export_enabled'])
                    ) {
                        $connected = true;
                        break;
                    }
                }

                if ($connected) {
                    $channels[] = array(
                        'key' => $key,
                        'label' => $label,
                        'connected' => true,
                    );
                }
            }
        }

        if ($this->db->table_exists('ota_x_company') && $this->db->table_exists('ota_manager')) {
            $ota_join = $this->db->table_exists('otas')
                ? 'LEFT JOIN otas AS o ON o.id = om.ota_id'
                : '';
            $name_select = $this->db->table_exists('otas')
                ? 'COALESCE(o.name, om.email, CONCAT("OTA #", om.id))'
                : 'COALESCE(om.email, CONCAT("OTA #", om.id))';

            $rows = $this->db->query("
                SELECT DISTINCT {$name_select} AS channel_label
                FROM ota_x_company AS oxc
                INNER JOIN ota_manager AS om ON om.id = oxc.ota_manager_id
                {$ota_join}
                WHERE oxc.company_id = {$company_id}
                  AND oxc.is_active = 1
            ")->result_array();

            foreach ($rows as $row) {
                $label = trim((string) $row['channel_label']);
                if ($label === '') {
                    continue;
                }

                $exists = false;
                foreach ($channels as $channel) {
                    if ($channel['label'] === $label) {
                        $exists = true;
                        break;
                    }
                }

                if (!$exists) {
                    $channels[] = array(
                        'key' => 'ota_' . md5($label),
                        'label' => $label,
                        'connected' => true,
                    );
                }
            }
        }

        return $channels;
    }

    /**
     * Rate plans linked to BAR (child/derived or OBE) for channel parity.
     */
    public function get_channel_rate_plans($company_id)
    {
        $company_id = (int) $company_id;
        $bar_plan_ids = array();
        foreach ($this->get_default_bar_rate_plans($company_id) as $plan) {
            $bar_plan_ids[(int) $plan['rate_plan_id']] = true;
        }

        if (empty($bar_plan_ids)) {
            return array();
        }

        $bar_ids_sql = implode(',', array_keys($bar_plan_ids));
        $rows = $this->db->query("
            SELECT
                rp.rate_plan_id,
                rp.rate_plan_name,
                rp.parent_rate_plan_id,
                rp.room_type_id,
                rp.is_shown_in_online_booking_engine,
                rt.name AS room_type_name
            FROM rate_plan AS rp
            INNER JOIN room_type AS rt ON rt.id = rp.room_type_id
            WHERE rt.company_id = {$company_id}
              AND rp.is_deleted != '1'
              AND (
                  (rp.parent_rate_plan_id IS NOT NULL
                   AND rp.parent_rate_plan_id != ''
                   AND rp.parent_rate_plan_id != '0'
                   AND rp.parent_rate_plan_id IN ({$bar_ids_sql}))
                  OR rp.is_shown_in_online_booking_engine = '1'
              )
            ORDER BY rt.name ASC, rp.rate_plan_name ASC
        ")->result_array();

        $plans = array();
        foreach ($rows as $row) {
            $parent_id = (int) $row['parent_rate_plan_id'];
            if ($parent_id < 1 && !empty($row['is_shown_in_online_booking_engine'])) {
                foreach ($bar_plan_ids as $bar_id => $_) {
                    if ((int) $row['room_type_id'] === (int) $this->get_room_type_for_bar($company_id, $bar_id)) {
                        $parent_id = $bar_id;
                        break;
                    }
                }
            }

            if ($parent_id < 1 || !isset($bar_plan_ids[$parent_id])) {
                continue;
            }

            if ((int) $row['rate_plan_id'] === $parent_id) {
                continue;
            }

            $channel_type = !empty($row['is_shown_in_online_booking_engine']) ? 'direct' : 'ota';
            $plans[] = array(
                'rate_plan_id' => (int) $row['rate_plan_id'],
                'rate_plan_name' => $row['rate_plan_name'],
                'parent_rate_plan_id' => $parent_id,
                'room_type_id' => (int) $row['room_type_id'],
                'room_type_name' => $row['room_type_name'],
                'channel_type' => $channel_type,
                'channel_label' => $this->infer_channel_label($row['rate_plan_name'], $channel_type),
            );
        }

        return $plans;
    }

    /**
     * BAR vs channel rate comparison grid with parity flags.
     */
    public function get_channel_parity_grid($company_id, $start_date, $day_count = 14)
    {
        $company_id = (int) $company_id;
        $start_date = $this->db->escape_str($start_date);
        $day_count = max(7, min(28, (int) $day_count));
        $end_exclusive = date('Y-m-d', strtotime($start_date . ' +' . $day_count . ' days'));
        $end_date = date('Y-m-d', strtotime($end_exclusive . ' -1 day'));

        $this->load->model('Rate_model');
        $bar_plans = $this->get_default_bar_rate_plans($company_id);
        $channel_plans = $this->get_channel_rate_plans($company_id);
        $channels = $this->get_connected_channels($company_id);

        $dates = array();
        $cursor = strtotime($start_date);
        $end_ts = strtotime($end_date);
        while ($cursor <= $end_ts) {
            $dates[] = date('Y-m-d', $cursor);
            $cursor = strtotime('+1 day', $cursor);
        }

        $sections = array();
        $summary_issues = 0;
        $summary_aligned = 0;

        foreach ($bar_plans as $bar_plan) {
            $bar_rates = $this->Rate_model->get_daily_rates(
                $bar_plan['rate_plan_id'],
                $start_date,
                $end_exclusive
            );
            $bar_by_date = array();
            foreach ($bar_rates as $rate) {
                $bar_by_date[$rate['date']] = isset($rate['adult_1_rate']) ? (float) $rate['adult_1_rate'] : null;
            }

            $room_channel_plans = array();
            foreach ($channel_plans as $channel_plan) {
                if ((int) $channel_plan['room_type_id'] !== (int) $bar_plan['room_type_id']) {
                    continue;
                }
                if ((int) $channel_plan['parent_rate_plan_id'] !== (int) $bar_plan['rate_plan_id']) {
                    continue;
                }
                $room_channel_plans[] = $channel_plan;
            }

            if (empty($room_channel_plans)) {
                continue;
            }

            $channel_rate_maps = array();
            foreach ($room_channel_plans as $channel_plan) {
                $rates = $this->Rate_model->get_daily_rates(
                    $channel_plan['rate_plan_id'],
                    $start_date,
                    $end_exclusive
                );
                $by_date = array();
                foreach ($rates as $rate) {
                    $by_date[$rate['date']] = isset($rate['adult_1_rate']) ? (float) $rate['adult_1_rate'] : null;
                }
                $channel_rate_maps[$channel_plan['rate_plan_id']] = $by_date;
            }

            $rows = array();
            foreach ($dates as $date) {
                $bar_rate = isset($bar_by_date[$date]) ? $bar_by_date[$date] : null;
                $channels_for_date = array();
                $worst_status = 'aligned';

                foreach ($room_channel_plans as $channel_plan) {
                    $plan_id = $channel_plan['rate_plan_id'];
                    $channel_rate = isset($channel_rate_maps[$plan_id][$date])
                        ? $channel_rate_maps[$plan_id][$date]
                        : null;
                    $status = $this->parity_status($bar_rate, $channel_rate);
                    $delta = ($bar_rate !== null && $channel_rate !== null)
                        ? round($channel_rate - $bar_rate, 2)
                        : null;
                    $delta_pct = ($bar_rate > 0 && $delta !== null)
                        ? round(($delta / $bar_rate) * 100, 1)
                        : null;

                    if ($status === 'undercut') {
                        $worst_status = 'undercut';
                    } elseif ($status === 'mismatch' && $worst_status !== 'undercut') {
                        $worst_status = 'mismatch';
                    }

                    $channels_for_date[] = array(
                        'rate_plan_id' => $plan_id,
                        'label' => $channel_plan['channel_label'],
                        'rate' => $channel_rate,
                        'delta' => $delta,
                        'delta_pct' => $delta_pct,
                        'status' => $status,
                    );
                }

                if ($worst_status === 'undercut' || $worst_status === 'mismatch') {
                    $summary_issues++;
                } elseif ($worst_status === 'aligned') {
                    $summary_aligned++;
                }

                $rows[] = array(
                    'date' => $date,
                    'bar_rate' => $bar_rate,
                    'channels' => $channels_for_date,
                    'status' => $worst_status,
                );
            }

            $sections[] = array(
                'room_type_id' => (int) $bar_plan['room_type_id'],
                'room_type_name' => $bar_plan['room_type_name'],
                'bar_rate_plan_id' => (int) $bar_plan['rate_plan_id'],
                'channel_plans' => $room_channel_plans,
                'rows' => $rows,
            );
        }

        return array(
            'dates' => $dates,
            'sections' => $sections,
            'connected_channels' => $channels,
            'has_channel_plans' => count($channel_plans) > 0,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'summary' => array(
                'issue_days' => $summary_issues,
                'aligned_days' => $summary_aligned,
                'channel_plan_count' => count($channel_plans),
            ),
        );
    }

    /**
     * Internal demand signals derived from on-books, pickup, and seasonality.
     */
    public function get_demand_signals($company_id, $start_date, $end_date)
    {
        $company_id = (int) $company_id;
        $start_date = $this->db->escape_str($start_date);
        $end_date = $this->db->escape_str($end_date);

        $forecast = $this->get_revenue_forecast($company_id, $start_date, $end_date);
        $yoy = $this->get_yoy_comparison($company_id, $start_date, $end_date);
        $pickup_recent = $this->get_pickup_metrics($company_id, $start_date, $end_date, 7);
        $pickup_prior = $this->get_pickup_metrics(
            $company_id,
            date('Y-m-d', strtotime($start_date . ' -7 days')),
            date('Y-m-d', strtotime($end_date . ' -7 days')),
            7
        );

        $source_mix = $this->get_forecast_source_mix($company_id, $start_date, $end_date);
        $dow_pattern = $this->get_day_of_week_pattern($forecast);
        $weekend = $this->get_weekend_weekday_split($forecast);

        $pickup_delta = (int) $pickup_recent['room_nights'] - (int) $pickup_prior['room_nights'];
        $pickup_trend = 'flat';
        if ($pickup_delta >= 3) {
            $pickup_trend = 'up';
        } elseif ($pickup_delta <= -3) {
            $pickup_trend = 'down';
        }

        $signals = array();

        if (isset($yoy['occupancy_change_pct'])) {
            $delta = (int) $yoy['occupancy_change_pct'];
            $signals[] = array(
                'type' => $delta >= 5 ? 'positive' : ($delta <= -5 ? 'warning' : 'neutral'),
                'title' => 'Year-over-year demand',
                'detail' => $delta >= 0
                    ? 'Occupancy is ' . $delta . '% ahead of the same period last year.'
                    : 'Occupancy is ' . abs($delta) . '% behind the same period last year.',
                'metric' => ($delta >= 0 ? '+' : '') . $delta . '%',
            );
        }

        $signals[] = array(
            'type' => $pickup_trend === 'up' ? 'positive' : ($pickup_trend === 'down' ? 'warning' : 'neutral'),
            'title' => 'Booking momentum',
            'detail' => (int) $pickup_recent['room_nights'] . ' room-nights booked in the last 7 days for this window'
                . ($pickup_delta !== 0 ? ' (' . ($pickup_delta > 0 ? '+' : '') . $pickup_delta . ' vs prior week).' : '.'),
            'metric' => (int) $pickup_recent['room_nights'],
        );

        if (!empty($weekend['weekend_avg']) || !empty($weekend['weekday_avg'])) {
            $premium = (int) $weekend['weekend_avg'] - (int) $weekend['weekday_avg'];
            $signals[] = array(
                'type' => $premium >= 10 ? 'positive' : ($premium <= -10 ? 'warning' : 'neutral'),
                'title' => 'Weekend demand premium',
                'detail' => 'Weekend occupancy averages ' . (int) $weekend['weekend_avg']
                    . '% vs ' . (int) $weekend['weekday_avg'] . '% on weekdays.',
                'metric' => ($premium >= 0 ? '+' : '') . $premium . ' pts',
            );
        }

        if (!empty($source_mix)) {
            $top = $source_mix[0];
            $signals[] = array(
                'type' => 'neutral',
                'title' => 'Channel mix (on-books)',
                'detail' => $top['source'] . ' leads with ' . $top['pct'] . '% of room-nights in this period.',
                'metric' => $top['pct'] . '%',
            );
        }

        return array(
            'signals' => $signals,
            'source_mix' => $source_mix,
            'day_of_week' => $dow_pattern,
            'weekend_split' => $weekend,
            'yoy' => $yoy,
            'pickup' => array(
                'recent' => $pickup_recent,
                'prior' => $pickup_prior,
                'delta' => $pickup_delta,
                'trend' => $pickup_trend,
            ),
            'external' => array(
                'events' => false,
                'comp_set' => false,
                'market_data' => false,
                'message' => 'Local events, comp-set rates, and market benchmarks require an external data feed. Signals below use your property booking and occupancy data.',
            ),
        );
    }

    private function get_room_type_for_bar($company_id, $bar_rate_plan_id)
    {
        $this->load->model('Room_type_model');
        $room_types = $this->Room_type_model->get_room_types($company_id);
        if (!$room_types) {
            return 0;
        }

        foreach ($room_types as $room_type) {
            if ((int) $room_type['default_room_charge'] === (int) $bar_rate_plan_id) {
                return (int) $room_type['id'];
            }
        }

        return 0;
    }

    private function infer_channel_label($rate_plan_name, $channel_type = 'ota')
    {
        $name = strtolower((string) $rate_plan_name);
        $map = array(
            'booking.com' => 'Booking.com',
            'booking_com' => 'Booking.com',
            'expedia' => 'Expedia',
            'airbnb' => 'Airbnb',
            'agoda' => 'Agoda',
            'hostelworld' => 'Hostelworld',
            'trip' => 'Trip.com',
            'siteminder' => 'Siteminder',
            'channex' => 'Channex',
        );

        foreach ($map as $needle => $label) {
            if (strpos($name, $needle) !== false) {
                return $label;
            }
        }

        if ($channel_type === 'direct') {
            return 'Direct (OBE)';
        }

        return $rate_plan_name;
    }

    private function parity_status($bar_rate, $channel_rate)
    {
        if ($bar_rate === null || $channel_rate === null || $bar_rate <= 0) {
            return 'unknown';
        }

        $delta_pct = (($channel_rate - $bar_rate) / $bar_rate) * 100;

        if ($delta_pct <= -2) {
            return 'undercut';
        }
        if (abs($delta_pct) <= 2) {
            return 'aligned';
        }

        return 'mismatch';
    }

    private function get_forecast_source_mix($company_id, $start_date, $end_date)
    {
        $company_id = (int) $company_id;
        $active_states = RESERVATION . ',' . INHOUSE . ',' . UNCONFIRMED_RESERVATION;

        $rows = $this->db->query("
            SELECT
                b.source AS source_id,
                COUNT(*) AS room_nights
            FROM date_interval AS di
            INNER JOIN booking_block AS brh ON
                di.date >= DATE(brh.check_in_date)
                AND di.date < DATE(brh.check_out_date)
            INNER JOIN booking AS b ON
                b.booking_id = brh.booking_id
                AND b.company_id = {$company_id}
                AND b.is_deleted != '1'
                AND b.state IN ({$active_states})
            WHERE di.date >= '{$start_date}'
              AND di.date <= '{$end_date}'
            GROUP BY b.source
            ORDER BY room_nights DESC
        ")->result_array();

        $total = 0;
        foreach ($rows as $row) {
            $total += (int) $row['room_nights'];
        }

        if ($total < 1) {
            return array();
        }

        $mix = array();
        foreach ($rows as $row) {
            $count = (int) $row['room_nights'];
            $mix[] = array(
                'source' => $this->resolve_booking_source_label($row['source_id']),
                'room_nights' => $count,
                'pct' => round(($count / $total) * 100),
            );
        }

        return $mix;
    }

    private function resolve_booking_source_label($source)
    {
        $source = (string) $source;
        $common = json_decode(COMMON_BOOKING_SOURCES, true);
        if (is_array($common) && isset($common[$source])) {
            return $common[$source];
        }

        if ((int) $source > 20) {
            $row = $this->db->query(
                'SELECT name FROM booking_source WHERE id = ' . (int) $source . ' LIMIT 1'
            )->row_array();
            if (!empty($row['name'])) {
                return $row['name'];
            }
        }

        return $source !== '' ? $source : 'Unknown';
    }

    private function get_day_of_week_pattern($forecast)
    {
        $rows = isset($forecast['rows']) ? $forecast['rows'] : array();
        $buckets = array(
            'Mon' => array('sum' => 0, 'count' => 0),
            'Tue' => array('sum' => 0, 'count' => 0),
            'Wed' => array('sum' => 0, 'count' => 0),
            'Thu' => array('sum' => 0, 'count' => 0),
            'Fri' => array('sum' => 0, 'count' => 0),
            'Sat' => array('sum' => 0, 'count' => 0),
            'Sun' => array('sum' => 0, 'count' => 0),
        );

        foreach ($rows as $row) {
            if (empty($row['date'])) {
                continue;
            }
            $label = date('D', strtotime($row['date']));
            if (!isset($buckets[$label])) {
                continue;
            }
            $buckets[$label]['sum'] += (int) $row['occupancy_pct'];
            $buckets[$label]['count']++;
        }

        $pattern = array();
        foreach ($buckets as $day => $bucket) {
            $pattern[] = array(
                'day' => $day,
                'avg_occupancy' => $bucket['count'] > 0 ? round($bucket['sum'] / $bucket['count']) : 0,
            );
        }

        return $pattern;
    }

    private function get_weekend_weekday_split($forecast)
    {
        $rows = isset($forecast['rows']) ? $forecast['rows'] : array();
        $weekend = array('sum' => 0, 'count' => 0);
        $weekday = array('sum' => 0, 'count' => 0);

        foreach ($rows as $row) {
            if (empty($row['date'])) {
                continue;
            }
            $dow = (int) date('N', strtotime($row['date']));
            $occ = (int) $row['occupancy_pct'];
            if ($dow >= 6) {
                $weekend['sum'] += $occ;
                $weekend['count']++;
            } else {
                $weekday['sum'] += $occ;
                $weekday['count']++;
            }
        }

        return array(
            'weekend_avg' => $weekend['count'] > 0 ? round($weekend['sum'] / $weekend['count']) : 0,
            'weekday_avg' => $weekday['count'] > 0 ? round($weekday['sum'] / $weekday['count']) : 0,
        );
    }
}
