<?php
$rate_plan_options = isset($rate_plan_options) ? $rate_plan_options : array();
$forecast_payload = isset($forecast_payload) ? $forecast_payload : array();
$room_overview = isset($room_overview) ? $room_overview : array();
$forecast_days = isset($forecast_days) ? (int) $forecast_days : 28;
$pickup = isset($forecast_payload['pickup']) ? $forecast_payload['pickup'] : array();
$yoy = isset($forecast_payload['yoy']) ? $forecast_payload['yoy'] : array();
$demand_signals = isset($demand_signals) ? $demand_signals : array();
$connected_channels = isset($connected_channels) ? $connected_channels : array();

function rms_trend_badge($change) {
    $change = (int) $change;
    if ($change > 0) {
        return '<span class="rms-trend rms-trend--up"><i class="fa fa-arrow-up"></i> ' . abs($change) . '%</span>';
    }
    if ($change < 0) {
        return '<span class="rms-trend rms-trend--down"><i class="fa fa-arrow-down"></i> ' . abs($change) . '%</span>';
    }
    return '<span class="rms-trend rms-trend--flat">—</span>';
}
?>

<div class="rms-page mc-page">
    <div class="rms-header">
        <div class="rms-header-text">
            <h1 class="rms-title">
                <i class="fa fa-line-chart rms-title-icon"></i>
                <?php echo l('rms_title', true); ?>
            </h1>
            <p class="rms-subtitle">
                <?php echo l('rms_subtitle', true); ?>
                <strong><?php echo htmlspecialchars(date('l, F j, Y', strtotime($selling_date))); ?></strong>
            </p>
        </div>
        <div class="rms-header-actions">
            <a class="btn btn-default" href="<?php echo base_url('settings/rates/rate_plans'); ?>">
                <i class="fa fa-cog"></i> <?php echo l('rms_manage_rate_plans', true); ?>
            </a>
        </div>
    </div>

    <ul class="nav nav-tabs rms-tabs" role="tablist">
        <li role="presentation" class="active">
            <a href="#rms-overview-tab" aria-controls="rms-overview-tab" role="tab" data-toggle="tab">
                <i class="fa fa-th-large"></i> <?php echo l('rms_tab_overview', true); ?>
            </a>
        </li>
        <li role="presentation">
            <a href="#rms-property-rates-tab" aria-controls="rms-property-rates-tab" role="tab" data-toggle="tab">
                <i class="fa fa-building"></i> <?php echo l('rms_tab_property_rates', true); ?>
            </a>
        </li>
        <li role="presentation">
            <a href="#rms-pace-tab" aria-controls="rms-pace-tab" role="tab" data-toggle="tab">
                <i class="fa fa-tachometer"></i> <?php echo l('rms_tab_pace', true); ?>
            </a>
        </li>
        <li role="presentation">
            <a href="#rms-rates-tab" aria-controls="rms-rates-tab" role="tab" data-toggle="tab">
                <i class="fa fa-money"></i> <?php echo l('rms_tab_rates', true); ?>
            </a>
        </li>
        <li role="presentation">
            <a href="#rms-restrictions-tab" aria-controls="rms-restrictions-tab" role="tab" data-toggle="tab">
                <i class="fa fa-ban"></i> <?php echo l('rms_tab_restrictions', true); ?>
            </a>
        </li>
        <li role="presentation">
            <a href="#rms-forecast-tab" aria-controls="rms-forecast-tab" role="tab" data-toggle="tab">
                <i class="fa fa-area-chart"></i> <?php echo l('rms_tab_forecast', true); ?>
            </a>
        </li>
        <li role="presentation">
            <a href="#rms-channels-tab" aria-controls="rms-channels-tab" role="tab" data-toggle="tab">
                <i class="fa fa-exchange"></i> <?php echo l('rms_tab_channels', true); ?>
            </a>
        </li>
        <li role="presentation">
            <a href="#rms-demand-tab" aria-controls="rms-demand-tab" role="tab" data-toggle="tab">
                <i class="fa fa-bolt"></i> <?php echo l('rms_tab_demand', true); ?>
            </a>
        </li>
    </ul>

    <div class="tab-content rms-tab-content">
        <div role="tabpanel" class="tab-pane active" id="rms-overview-tab">
            <p class="rms-section-lead"><?php echo l('rms_overview_lead', true); ?></p>
            <div class="rms-room-grid" id="rms-room-grid">
                <?php if (!empty($room_overview)): ?>
                    <?php foreach ($room_overview as $room): ?>
                        <div class="rms-room-card">
                            <div class="rms-room-card__head">
                                <h3><?php echo htmlspecialchars($room['room_type_name']); ?></h3>
                                <span class="rms-room-card__rooms"><?php echo (int) $room['rooms_count']; ?> <?php echo l('rms_rooms', true); ?></span>
                            </div>
                            <div class="rms-room-card__metrics">
                                <div>
                                    <span class="rms-room-card__label"><?php echo l('rms_today_occupancy', true); ?></span>
                                    <span class="rms-room-card__value"><?php echo (int) $room['occupancy_pct']; ?>%</span>
                                    <span class="rms-room-card__sub"><?php echo (int) $room['occupied_today']; ?> / <?php echo (int) $room['rooms_count']; ?></span>
                                </div>
                                <div>
                                    <span class="rms-room-card__label"><?php echo l('rms_avg_occupancy', true); ?> (14d)</span>
                                    <span class="rms-room-card__value"><?php echo (int) $room['avg_occupancy_pct']; ?>%</span>
                                </div>
                                <div>
                                    <span class="rms-room-card__label"><?php echo l('rms_bar', true); ?></span>
                                    <span class="rms-room-card__value"><?php echo htmlspecialchars($currency_symbol) . number_format($room['bar_rate'], 2); ?></span>
                                </div>
                            </div>
                            <div class="rms-occ-bar" aria-hidden="true">
                                <span class="rms-occ-bar__fill" style="width: <?php echo min(100, (int) $room['occupancy_pct']); ?>%;"></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted rms-empty"><?php echo l('rms_no_room_types', true); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div role="tabpanel" class="tab-pane" id="rms-property-rates-tab">
            <div class="rms-toolbar">
                <div class="form-inline">
                    <div class="btn-group rms-date-nav" role="group">
                        <button type="button" class="btn btn-default" id="rms-property-prev-week" title="<?php echo l('rms_previous_week', true); ?>">
                            <span class="glyphicon glyphicon-chevron-left"></span>
                        </button>
                        <button type="button" class="btn btn-default" id="rms-property-today"><?php echo l('rms_today', true); ?></button>
                        <button type="button" class="btn btn-default" id="rms-property-next-week" title="<?php echo l('rms_next_week', true); ?>">
                            <span class="glyphicon glyphicon-chevron-right"></span>
                        </button>
                    </div>
                    <span class="rms-date-range-label" id="rms-property-date-range-label"></span>
                </div>
            </div>
            <p class="rms-hint text-muted"><i class="fa fa-building"></i> <?php echo l('rms_property_rates_lead', true); ?></p>
            <div id="rms-property-rates-grid" class="rms-grid-wrap"></div>
        </div>

        <div role="tabpanel" class="tab-pane" id="rms-pace-tab">
            <div class="rms-toolbar">
                <div class="rms-forecast-range rms-horizon-range" role="group" aria-label="<?php echo l('rms_forecast_period', true); ?>">
                    <?php foreach (array(14, 28, 60) as $range_days): ?>
                        <button
                            type="button"
                            class="btn btn-sm rms-pace-range-btn <?php echo $forecast_days === $range_days ? 'btn-primary active' : 'btn-default'; ?>"
                            data-days="<?php echo $range_days; ?>"
                        ><?php echo sprintf(l('rms_days', true), $range_days); ?></button>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-sm btn-default" id="rms-rules-preview">
                    <?php echo l('rms_rules_preview', true); ?>
                </button>
                <button type="button" class="btn btn-sm btn-primary" id="rms-rules-run">
                    <?php echo l('rms_rules_run', true); ?>
                </button>
            </div>
            <p class="rms-hint text-muted"><?php echo l('rms_pace_lead', true); ?></p>

            <div class="rms-forecast-stats" id="rms-pace-stats"></div>

            <div class="rms-chart-panel">
                <h3 class="rms-chart-title"><?php echo l('rms_pace_chart', true); ?></h3>
                <div class="rms-forecast-chart" id="rms-pace-chart">
                    <canvas id="rms-pace-canvas" height="220"></canvas>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-condensed rms-forecast-table" id="rms-pace-table">
                    <thead>
                        <tr>
                            <th><?php echo l('rms_date', true); ?></th>
                            <th><?php echo l('rms_occupied', true); ?></th>
                            <th><?php echo l('rms_pace_prior_occ', true); ?></th>
                            <th><?php echo l('rms_occupancy', true); ?></th>
                            <th><?php echo l('rms_pace_prior_pct', true); ?></th>
                            <th><?php echo l('rms_pace_delta', true); ?></th>
                            <th><?php echo l('rms_pickup', true); ?></th>
                            <th><?php echo l('rms_on_books', true); ?></th>
                            <th><?php echo l('rms_pace_prior_revenue', true); ?></th>
                        </tr>
                    </thead>
                    <tbody id="rms-pace-table-body"></tbody>
                </table>
            </div>

            <div class="rms-rules-panel" id="rms-rules-panel" style="display:none;">
                <h3><?php echo l('rms_rules_title', true); ?></h3>
                <p class="text-muted"><?php echo l('rms_rules_lead', true); ?></p>
                <ul id="rms-rules-list" class="rms-rules-list"></ul>
            </div>
        </div>

        <div role="tabpanel" class="tab-pane" id="rms-rates-tab">
            <div class="rms-toolbar">
                <div class="form-inline">
                    <div class="form-group">
                        <label for="rms-rate-plan-select"><?php echo l('rms_rate_plan', true); ?></label>
                        <select id="rms-rate-plan-select" class="form-control rms-sync-rate-plan">
                            <?php foreach ($rate_plan_options as $option): ?>
                                <option
                                    value="<?php echo (int) $option['rate_plan_id']; ?>"
                                    data-room-type-id="<?php echo (int) $option['room_type_id']; ?>"
                                    <?php echo ($option['rate_plan_id'] == $default_rate_plan_id) ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($option['rate_plan_name'] . ' (' . $option['room_type_name'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="btn-group rms-date-nav" role="group">
                        <button type="button" class="btn btn-default rms-sync-prev-week" id="rms-prev-week" title="<?php echo l('rms_previous_week', true); ?>">
                            <span class="glyphicon glyphicon-chevron-left"></span>
                        </button>
                        <button type="button" class="btn btn-default rms-sync-today" id="rms-today"><?php echo l('rms_today', true); ?></button>
                        <button type="button" class="btn btn-default rms-sync-next-week" id="rms-next-week" title="<?php echo l('rms_next_week', true); ?>">
                            <span class="glyphicon glyphicon-chevron-right"></span>
                        </button>
                    </div>
                    <span class="rms-date-range-label" id="rms-date-range-label"></span>
                </div>
                <a
                    class="btn btn-primary"
                    id="rms-edit-rates-link"
                    href="<?php echo $default_rate_plan_id ? base_url('settings/rates/edit_rates/' . $default_rate_plan_id) : '#'; ?>"
                >
                    <?php echo l('rms_bulk_edit_rates', true); ?>
                </a>
            </div>
            <p class="rms-hint text-muted"><i class="fa fa-pencil"></i> <?php echo l('rms_inline_edit_hint', true); ?></p>
            <div id="rms-rates-grid" class="rms-grid-wrap" data-view="rates"></div>
        </div>

        <div role="tabpanel" class="tab-pane" id="rms-restrictions-tab">
            <div class="rms-toolbar">
                <div class="form-inline">
                    <div class="form-group">
                        <label for="rms-restrictions-rate-plan-select"><?php echo l('rms_rate_plan', true); ?></label>
                        <select id="rms-restrictions-rate-plan-select" class="form-control rms-sync-rate-plan">
                            <?php foreach ($rate_plan_options as $option): ?>
                                <option
                                    value="<?php echo (int) $option['rate_plan_id']; ?>"
                                    data-room-type-id="<?php echo (int) $option['room_type_id']; ?>"
                                    <?php echo ($option['rate_plan_id'] == $default_rate_plan_id) ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($option['rate_plan_name'] . ' (' . $option['room_type_name'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="btn-group rms-date-nav" role="group">
                        <button type="button" class="btn btn-default rms-sync-prev-week" title="<?php echo l('rms_previous_week', true); ?>">
                            <span class="glyphicon glyphicon-chevron-left"></span>
                        </button>
                        <button type="button" class="btn btn-default rms-sync-today"><?php echo l('rms_today', true); ?></button>
                        <button type="button" class="btn btn-default rms-sync-next-week" title="<?php echo l('rms_next_week', true); ?>">
                            <span class="glyphicon glyphicon-chevron-right"></span>
                        </button>
                    </div>
                </div>
            </div>
            <p class="rms-hint text-muted"><i class="fa fa-toggle-on"></i> <?php echo l('rms_restrictions_toggle_hint', true); ?></p>
            <div id="rms-restrictions-grid" class="rms-grid-wrap" data-view="restrictions"></div>
        </div>

        <div role="tabpanel" class="tab-pane" id="rms-forecast-tab">
            <div class="rms-forecast-layout">
                <div class="rms-forecast-main">
                    <div class="rms-toolbar">
                        <div class="rms-forecast-range" role="group" aria-label="<?php echo l('rms_forecast_period', true); ?>">
                            <?php foreach (array(14, 28, 60) as $range_days): ?>
                                <button
                                    type="button"
                                    class="btn btn-sm rms-forecast-range-btn <?php echo $forecast_days === $range_days ? 'btn-primary active' : 'btn-default'; ?>"
                                    data-days="<?php echo $range_days; ?>"
                                ><?php echo sprintf(l('rms_days', true), $range_days); ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="rms-forecast-stats">
                        <div class="rms-stat-card">
                            <span class="rms-stat-label"><?php echo l('rms_on_books', true); ?></span>
                            <span class="rms-stat-value" id="rms-stat-on-books">
                                <?php echo htmlspecialchars($currency_symbol); ?><?php echo number_format(isset($forecast_payload['summary']['onBooksRevenue']) ? $forecast_payload['summary']['onBooksRevenue'] : 0, 2); ?>
                            </span>
                        </div>
                        <div class="rms-stat-card">
                            <span class="rms-stat-label"><?php echo l('rms_potential', true); ?></span>
                            <span class="rms-stat-value" id="rms-stat-potential">
                                <?php echo htmlspecialchars($currency_symbol); ?><?php echo number_format(isset($forecast_payload['summary']['potentialRevenue']) ? $forecast_payload['summary']['potentialRevenue'] : 0, 2); ?>
                            </span>
                        </div>
                        <div class="rms-stat-card rms-stat-card--highlight">
                            <span class="rms-stat-label"><?php echo l('rms_forecast_total', true); ?></span>
                            <span class="rms-stat-value" id="rms-stat-total">
                                <?php echo htmlspecialchars($currency_symbol); ?><?php echo number_format(isset($forecast_payload['summary']['forecastTotal']) ? $forecast_payload['summary']['forecastTotal'] : 0, 2); ?>
                            </span>
                            <span class="rms-stat-sub" id="rms-stat-yoy-rev">
                                <?php echo l('rms_vs_last_year', true); ?> <?php echo rms_trend_badge(isset($yoy['forecast_change_pct']) ? $yoy['forecast_change_pct'] : 0); ?>
                            </span>
                        </div>
                        <div class="rms-stat-card">
                            <span class="rms-stat-label"><?php echo l('rms_avg_occupancy', true); ?></span>
                            <span class="rms-stat-value" id="rms-stat-occupancy">
                                <?php echo (int) (isset($forecast_payload['summary']['avgOccupancy']) ? $forecast_payload['summary']['avgOccupancy'] : 0); ?>%
                            </span>
                            <span class="rms-stat-sub" id="rms-stat-yoy-occ">
                                <?php echo l('rms_vs_last_year', true); ?> <?php echo rms_trend_badge(isset($yoy['occupancy_change_pct']) ? $yoy['occupancy_change_pct'] : 0); ?>
                            </span>
                        </div>
                        <div class="rms-stat-card">
                            <span class="rms-stat-label"><?php echo l('rms_pickup', true); ?></span>
                            <span class="rms-stat-value" id="rms-stat-pickup">
                                <?php echo (int) (isset($pickup['room_nights']) ? $pickup['room_nights'] : 0); ?>
                            </span>
                            <span class="rms-stat-sub"><?php echo sprintf(l('rms_pickup_sub', true), isset($pickup['pickup_days']) ? (int) $pickup['pickup_days'] : 7); ?></span>
                        </div>
                    </div>

                    <div class="rms-chart-panel">
                        <h3 class="rms-chart-title"><?php echo l('rms_revenue_chart', true); ?></h3>
                        <div class="rms-forecast-chart" id="rms-forecast-chart">
                            <canvas id="rms-forecast-canvas" height="220"></canvas>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-condensed rms-forecast-table" id="rms-forecast-table">
                            <thead>
                                <tr>
                                    <th><?php echo l('rms_date', true); ?></th>
                                    <th><?php echo l('rms_occupied', true); ?></th>
                                    <th><?php echo l('rms_available', true); ?></th>
                                    <th><?php echo l('rms_occupancy', true); ?></th>
                                    <th><?php echo l('rms_on_books', true); ?></th>
                                    <th><?php echo l('rms_potential', true); ?></th>
                                    <th><?php echo l('rms_forecast_total', true); ?></th>
                                    <th><?php echo l('rms_adr', true); ?></th>
                                    <th><?php echo l('rms_revpar', true); ?></th>
                                </tr>
                            </thead>
                            <tbody id="rms-forecast-table-body">
                                <?php if (!empty($forecast_payload['rows'])): ?>
                                    <?php foreach ($forecast_payload['rows'] as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['date']); ?></td>
                                            <td><?php echo (int) $row['occupied']; ?></td>
                                            <td><?php echo (int) $row['available']; ?></td>
                                            <td><?php echo (int) $row['occupancy_pct']; ?>%</td>
                                            <td><?php echo htmlspecialchars($currency_symbol) . number_format($row['on_books_revenue'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($currency_symbol) . number_format($row['potential_revenue'], 2); ?></td>
                                            <td><strong><?php echo htmlspecialchars($currency_symbol) . number_format($row['forecast_total'], 2); ?></strong></td>
                                            <td><?php echo htmlspecialchars($currency_symbol) . number_format($row['adr'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($currency_symbol) . number_format($row['revpar'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <aside class="rms-ai-panel" id="rms-ai-panel">
                    <div class="rms-ai-panel__head">
                        <h2><i class="fa fa-magic"></i> <?php echo l('rms_ai_title', true); ?></h2>
                        <button type="button" class="btn btn-sm btn-primary" id="rms-ai-refresh">
                            <?php echo l('rms_ai_refresh', true); ?>
                        </button>
                    </div>
                    <p class="rms-ai-panel__lead"><?php echo l('rms_ai_lead', true); ?></p>
                    <div class="rms-ai-panel__body rms-loading" id="rms-ai-body">
                        <p class="text-muted"><?php echo l('rms_ai_loading', true); ?></p>
                    </div>
                    <p class="rms-ai-panel__footnote" id="rms-ai-source"></p>
                </aside>
            </div>
        </div>

        <div role="tabpanel" class="tab-pane" id="rms-channels-tab">
            <div class="rms-toolbar">
                <div class="form-inline">
                    <div class="btn-group rms-date-nav" role="group">
                        <button type="button" class="btn btn-default" id="rms-channel-prev-week" title="<?php echo l('rms_previous_week', true); ?>">
                            <span class="glyphicon glyphicon-chevron-left"></span>
                        </button>
                        <button type="button" class="btn btn-default" id="rms-channel-today"><?php echo l('rms_today', true); ?></button>
                        <button type="button" class="btn btn-default" id="rms-channel-next-week" title="<?php echo l('rms_next_week', true); ?>">
                            <span class="glyphicon glyphicon-chevron-right"></span>
                        </button>
                    </div>
                    <span class="rms-date-range-label" id="rms-channel-date-range-label"></span>
                </div>
                <button type="button" class="btn btn-sm btn-primary" id="rms-channel-sync" style="display:none;">
                    <?php echo l('rms_channel_sync', true); ?>
                </button>
            </div>
            <p class="rms-hint text-muted"><i class="fa fa-exchange"></i> <?php echo l('rms_channels_lead', true); ?></p>
            <div id="rms-channel-status" class="rms-channel-status"></div>
            <div id="rms-channel-parity-grid" class="rms-grid-wrap"></div>
        </div>

        <div role="tabpanel" class="tab-pane" id="rms-demand-tab">
            <div class="rms-toolbar">
                <div class="rms-forecast-range rms-horizon-range" role="group" aria-label="<?php echo l('rms_forecast_period', true); ?>">
                    <?php foreach (array(14, 28, 60) as $range_days): ?>
                        <button
                            type="button"
                            class="btn btn-sm rms-demand-range-btn <?php echo $forecast_days === $range_days ? 'btn-primary active' : 'btn-default'; ?>"
                            data-days="<?php echo $range_days; ?>"
                        ><?php echo sprintf(l('rms_days', true), $range_days); ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <p class="rms-hint text-muted"><i class="fa fa-bolt"></i> <?php echo l('rms_demand_lead', true); ?></p>

            <div class="rms-external-notice" id="rms-external-notice">
                <i class="fa fa-info-circle"></i>
                <span><?php echo htmlspecialchars(isset($demand_signals['external']['message']) ? $demand_signals['external']['message'] : l('rms_demand_external', true)); ?></span>
            </div>

            <div class="rms-demand-signals" id="rms-demand-signals">
                <?php if (!empty($demand_signals['signals'])): ?>
                    <?php foreach ($demand_signals['signals'] as $signal): ?>
                        <?php
                        $signal_class = 'rms-demand-card--neutral';
                        if (isset($signal['type']) && $signal['type'] === 'positive') {
                            $signal_class = 'rms-demand-card--positive';
                        } elseif (isset($signal['type']) && $signal['type'] === 'warning') {
                            $signal_class = 'rms-demand-card--warning';
                        }
                        ?>
                        <div class="rms-demand-card <?php echo $signal_class; ?>">
                            <span class="rms-demand-card__metric"><?php echo htmlspecialchars(isset($signal['metric']) ? $signal['metric'] : ''); ?></span>
                            <strong><?php echo htmlspecialchars($signal['title']); ?></strong>
                            <span><?php echo htmlspecialchars($signal['detail']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="rms-demand-panels">
                <div class="rms-chart-panel">
                    <h3 class="rms-chart-title"><?php echo l('rms_demand_source_mix', true); ?></h3>
                    <div id="rms-source-mix-chart" class="rms-source-mix">
                        <?php if (!empty($demand_signals['source_mix'])): ?>
                            <?php foreach ($demand_signals['source_mix'] as $source_row): ?>
                                <div class="rms-source-row">
                                    <span class="rms-source-label"><?php echo htmlspecialchars($source_row['source']); ?></span>
                                    <div class="rms-source-bar-wrap">
                                        <span class="rms-source-bar" style="width: <?php echo (int) $source_row['pct']; ?>%;"></span>
                                    </div>
                                    <span class="rms-source-pct"><?php echo (int) $source_row['pct']; ?>%</span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted rms-empty"><?php echo l('rms_demand_no_sources', true); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="rms-chart-panel">
                    <h3 class="rms-chart-title"><?php echo l('rms_demand_dow', true); ?></h3>
                    <div id="rms-dow-chart" class="rms-dow-chart">
                        <?php if (!empty($demand_signals['day_of_week'])): ?>
                            <?php foreach ($demand_signals['day_of_week'] as $dow_row): ?>
                                <div class="rms-dow-col">
                                    <span class="rms-dow-bar" style="height: <?php echo min(100, (int) $dow_row['avg_occupancy']); ?>%;"></span>
                                    <span class="rms-dow-label"><?php echo htmlspecialchars($dow_row['day']); ?></span>
                                    <span class="rms-dow-value"><?php echo (int) $dow_row['avg_occupancy']; ?>%</span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="rms-config"><?php
echo json_encode(array(
    'sellingDate' => $selling_date,
    'defaultRatePlanId' => $default_rate_plan_id,
    'defaultRoomTypeId' => $default_room_type_id,
    'ratesUrl' => base_url('revenue_management/get_rates_AJAX'),
    'updateRateUrl' => base_url('revenue_management/update_rate_cell_AJAX'),
    'applySuggestionUrl' => base_url('revenue_management/apply_suggestion_AJAX'),
    'forecastUrl' => base_url('revenue_management/get_forecast_AJAX'),
    'paceUrl' => base_url('revenue_management/get_pace_AJAX'),
    'propertyRatesUrl' => base_url('revenue_management/get_property_rates_AJAX'),
    'previewRulesUrl' => base_url('revenue_management/preview_pricing_rules_AJAX'),
    'runRulesUrl' => base_url('revenue_management/run_pricing_rules_AJAX'),
    'aiInsightsUrl' => base_url('revenue_management/get_ai_insights_AJAX'),
    'channelParityUrl' => base_url('revenue_management/get_channel_parity_AJAX'),
    'channelSyncUrl' => base_url('revenue_management/sync_channel_rates_AJAX'),
    'demandSignalsUrl' => base_url('revenue_management/get_demand_signals_AJAX'),
    'extensionsUrl' => base_url('extensions'),
    'ratePlansUrl' => base_url('settings/rates/rate_plans'),
    'editRatesBaseUrl' => base_url('settings/rates/edit_rates/'),
    'forecastDays' => $forecast_days,
    'currencySymbol' => $currency_symbol,
    'i18n' => array(
        'updateFailed' => l('rms_update_failed', true),
        'updateSaved' => l('rms_update_saved', true),
        'applySuccess' => l('rms_apply_success', true),
        'applyFailed' => l('rms_apply_failed', true),
        'applyConfirm' => l('rms_apply_confirm', true),
        'applyLabel' => l('rms_apply', true),
        'appliedLabel' => l('rms_applied', true),
        'aiLoading' => l('rms_ai_loading', true),
        'aiError' => l('rms_ai_error', true),
        'aiSourceAi' => l('rms_ai_source_ai', true),
        'aiSourceRules' => l('rms_ai_source_rules', true),
        'suggestions' => l('rms_ai_suggestions', true),
        'paceCurrent' => l('rms_pace_current', true),
        'pacePrior' => l('rms_pace_prior', true),
        'rulesConfirm' => l('rms_rules_confirm', true),
        'rulesNone' => l('rms_rules_none', true),
        'propertyRatesEmpty' => l('rms_property_rates_empty', true),
        'channelSync' => l('rms_channel_sync', true),
        'channelSyncConfirm' => l('rms_channel_sync_confirm', true),
        'channelSyncStarted' => l('rms_channel_sync_started', true),
        'channelSyncFailed' => l('rms_channel_sync_failed', true),
        'channelNoPlans' => l('rms_channel_no_plans', true),
        'channelNoConnections' => l('rms_channel_no_connections', true),
        'channelParityOk' => l('rms_channel_parity_ok', true),
        'channelParityIssue' => l('rms_channel_parity_issue', true),
        'demandNoSources' => l('rms_demand_no_sources', true),
        'demandSourceMix' => l('rms_demand_source_mix', true),
        'demandDow' => l('rms_demand_dow', true),
    ),
), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?></script>

<script type="application/json" id="rms-forecast-data"><?php
echo json_encode($forecast_payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?></script>

<script type="application/json" id="rms-demand-data"><?php
echo json_encode($demand_signals, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?></script>
