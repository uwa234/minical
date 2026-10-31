<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Revenue management insights via OpenAI (optional) or deterministic rules.
 */
class Revenue_ai
{
    public function generate_insights(array $context)
    {
        $api_key = function_exists('minical_env')
            ? trim((string) minical_env('OPENAI_API_KEY', ''))
            : trim((string) getenv('OPENAI_API_KEY'));

        if ($api_key !== '') {
            $ai_result = $this->call_openai($api_key, $context);
            if ($ai_result !== null) {
                return $ai_result;
            }
        }

        return $this->rule_based_insights($context);
    }

    public function rule_based_insights(array $context)
    {
        $summary = isset($context['forecast']['summary']) ? $context['forecast']['summary'] : array();
        $yoy = isset($context['yoy']) ? $context['yoy'] : array();
        $pickup = isset($context['pickup']) ? $context['pickup'] : array();
        $avg_occ = isset($summary['avg_occupancy']) ? (int) $summary['avg_occupancy'] : 0;
        $forecast_total = isset($summary['forecast_total']) ? (float) $summary['forecast_total'] : 0;
        $on_books = isset($summary['on_books_revenue']) ? (float) $summary['on_books_revenue'] : 0;

        $insights = array();

        if (isset($yoy['occupancy_change_pct'])) {
            $delta = (int) $yoy['occupancy_change_pct'];
            if ($delta >= 5) {
                $insights[] = array(
                    'type' => 'positive',
                    'title' => 'Occupancy pacing ahead of last year',
                    'detail' => 'Average occupancy is ' . $delta . '% higher than the same period last year (' . $avg_occ . '% vs ' . (int) $yoy['prior_avg_occupancy'] . '%).',
                );
            } elseif ($delta <= -5) {
                $insights[] = array(
                    'type' => 'warning',
                    'title' => 'Occupancy trailing last year',
                    'detail' => 'Average occupancy is ' . abs($delta) . '% below the same period last year. Review BAR and channel exposure on soft dates.',
                );
            }
        }

        if (!empty($pickup['room_nights'])) {
            $insights[] = array(
                'type' => 'neutral',
                'title' => 'Recent booking pickup',
                'detail' => (int) $pickup['room_nights'] . ' room-nights were booked in the last ' . (int) $pickup['pickup_days'] . ' days for this forecast window.',
            );
        } else {
            $insights[] = array(
                'type' => 'warning',
                'title' => 'Limited recent pickup',
                'detail' => 'No new room-nights were added in the last ' . (int) $pickup['pickup_days'] . ' days for upcoming dates. Consider a targeted promotion.',
            );
        }

        if ($forecast_total > 0) {
            $on_books_pct = round(($on_books / $forecast_total) * 100);
            $insights[] = array(
                'type' => $on_books_pct >= 55 ? 'positive' : 'neutral',
                'title' => 'Revenue composition',
                'detail' => round($on_books_pct) . '% of forecast revenue is already on the books; the remainder is potential from open inventory at BAR.',
            );
        }

        $suggestions = isset($context['rule_suggestions']) ? $context['rule_suggestions'] : array();
        $headline = 'Average occupancy is ' . $avg_occ . '% over the next '
            . (int) $context['forecast_days'] . ' days.';

        if ($avg_occ < 45) {
            $headline .= ' Demand looks soft — focus on rate positioning and promotions.';
        } elseif ($avg_occ >= 75) {
            $headline .= ' Strong demand — protect rate on peak dates.';
        } else {
            $headline .= ' Monitor pickup weekly and adjust BAR on low-occupancy weekends.';
        }

        return array(
            'success' => true,
            'source' => 'rules',
            'summary' => $headline,
            'insights' => $insights,
            'suggestions' => $suggestions,
        );
    }

    private function call_openai($api_key, array $context)
    {
        if (!function_exists('apply_curl_ssl_options')) {
            $helper = APPPATH . 'helpers/curl_helper.php';
            if (is_file($helper)) {
                require_once $helper;
            }
        }

        $model = function_exists('minical_env')
            ? minical_env('OPENAI_MODEL', 'gpt-4o-mini')
            : (getenv('OPENAI_MODEL') ?: 'gpt-4o-mini');

        $system = 'You are a hotel revenue management advisor. Respond ONLY with valid JSON using this schema: '
            . '{"summary":"string","insights":[{"type":"positive|warning|neutral","title":"string","detail":"string"}],'
            . '"suggestions":[{"type":"rate_decrease|rate_increase|restriction|strategy","date":"YYYY-MM-DD or empty",'
            . '"title":"string","detail":"string","priority":"high|medium|low"}]}. '
            . 'Base every statement on the provided metrics. Be concise and actionable. Max 4 insights and 5 suggestions.';

        $payload = json_encode(array(
            'model' => $model,
            'temperature' => 0.3,
            'response_format' => array('type' => 'json_object'),
            'messages' => array(
                array('role' => 'system', 'content' => $system),
                array(
                    'role' => 'user',
                    'content' => 'Analyze this hotel revenue data and return JSON only:\n'
                        . json_encode($context),
                ),
            ),
        ));

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $api_key,
            ),
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 45,
        ));

        if (function_exists('apply_curl_ssl_options')) {
            apply_curl_ssl_options($ch);
        }

        $response = curl_exec($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200 || !$response) {
            return null;
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded) || empty($decoded['choices'][0]['message']['content'])) {
            return null;
        }

        $content = json_decode($decoded['choices'][0]['message']['content'], true);
        if (!is_array($content)) {
            return null;
        }

        return array(
            'success' => true,
            'source' => 'ai',
            'summary' => isset($content['summary']) ? (string) $content['summary'] : '',
            'insights' => isset($content['insights']) && is_array($content['insights'])
                ? $content['insights']
                : array(),
            'suggestions' => isset($content['suggestions']) && is_array($content['suggestions'])
                ? $content['suggestions']
                : array(),
        );
    }
}
