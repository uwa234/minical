<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/public/application/libraries/Revenue_ai.php';

class RevenueAiTest extends TestCase
{
    public function testRuleBasedInsightsFromContext()
    {
        $ai = new Revenue_ai();
        $result = $ai->rule_based_insights(array(
            'forecast_days' => 28,
            'forecast' => array(
                'summary' => array(
                    'avg_occupancy' => 42,
                    'forecast_total' => 10000,
                    'on_books_revenue' => 4500,
                ),
            ),
            'yoy' => array(
                'occupancy_change_pct' => -8,
                'prior_avg_occupancy' => 50,
            ),
            'pickup' => array(
                'pickup_days' => 7,
                'room_nights' => 0,
            ),
            'rule_suggestions' => array(
                array(
                    'type' => 'rate_decrease',
                    'date' => '2026-06-12',
                    'title' => 'Low occupancy on Jun 12',
                    'detail' => 'Consider a promotion.',
                    'priority' => 'high',
                ),
            ),
        ));

        $this->assertTrue($result['success']);
        $this->assertSame('rules', $result['source']);
        $this->assertNotEmpty($result['summary']);
        $this->assertNotEmpty($result['insights']);
        $this->assertCount(1, $result['suggestions']);
    }

    public function testEnrichSuggestionsWithActions()
    {
        if (!class_exists('Revenue_management_model', false)) {
            require_once APPPATH . 'models/revenue_management_model.php';
        }

        $model = new Revenue_management_model();
        $suggestions = $model->enrich_suggestions_with_actions(array(
            array('type' => 'rate_decrease', 'date' => '2026-06-10'),
            array('type' => 'strategy', 'date' => ''),
        ));

        $this->assertTrue($suggestions[0]['applicable']);
        $this->assertSame('rate_adjust', $suggestions[0]['action']);
        $this->assertSame(-8, $suggestions[0]['adjustment_pct']);
        $this->assertFalse($suggestions[1]['applicable']);
    }

    public function testEvaluatePricingRulesStructure()
    {
        if (!class_exists('Revenue_management_model', false)) {
            require_once APPPATH . 'models/revenue_management_model.php';
        }

        $model = $this->getMockBuilder('Revenue_management_model')
            ->disableOriginalConstructor()
            ->setMethods(array('get_daily_pace_report'))
            ->getMock();

        $model->method('get_daily_pace_report')->willReturn(array(
            'rows' => array(
                array(
                    'date' => '2026-06-10',
                    'occupancy_pct' => 30,
                    'pace_delta' => -12,
                    'pickup_room_nights' => 0,
                ),
                array(
                    'date' => '2026-06-11',
                    'occupancy_pct' => 90,
                    'pace_delta' => 5,
                    'pickup_room_nights' => 4,
                ),
            ),
            'summary' => array('avg_pace_delta' => -3),
        ));

        $evaluation = $model->evaluate_pricing_rules(1, '2026-06-01', '2026-06-30');
        $this->assertNotEmpty($evaluation['actions']);
        $this->assertSame('rate_adjust', $evaluation['actions'][0]['action']);
        $this->assertSame(-5, $evaluation['actions'][0]['adjustment_pct']);
    }

    public function testBuildRuleBasedSuggestionsFlagsLowOccupancy()
    {
        if (!class_exists('Revenue_management_model', false)) {
            require_once APPPATH . 'models/revenue_management_model.php';
        }

        $model = new Revenue_management_model();
        $suggestions = $model->build_rule_based_suggestions(array(
            'summary' => array('avg_occupancy' => 60),
            'rows' => array(
                array('date' => '2026-06-10', 'occupancy_pct' => 25, 'available' => 8),
                array('date' => '2026-06-11', 'occupancy_pct' => 90, 'available' => 1),
            ),
        ));

        $this->assertNotEmpty($suggestions);
        $this->assertSame('rate_decrease', $suggestions[0]['type']);
    }
}
