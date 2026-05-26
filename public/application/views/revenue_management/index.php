<?php
$rate_plan_options = isset($rate_plan_options) ? $rate_plan_options : array();
$forecast_payload = isset($forecast_payload) ? $forecast_payload : array();
$forecast_days = isset($forecast_days) ? (int) $forecast_days : 28;
?>

<div class="rms-page mc-page">
    <div class="rms-header">
        <div class="rms-header-text">
            <h1 class="rms-title"><?php echo l('rms_title', true); ?></h1>
            <p class="rms-subtitle">
                <?php echo l('rms_subtitle', true); ?>
                <strong><?php echo htmlspecialchars(date('l, F j, Y', strtotime($selling_date))); ?></strong>
            </p>
        </div>
        <div class="rms-header-actions">
            <a class="btn btn-default" href="<?php echo base_url('settings/rates/rate_plans'); ?>">
                <?php echo l('rms_manage_rate_plans', true); ?>
            </a>
        </div>
    </div>

    <ul class="nav nav-tabs rms-tabs" role="tablist">
        <li role="presentation" class="active">
            <a href="#rms-rates-tab" aria-controls="rms-rates-tab" role="tab" data-toggle="tab">
                <?php echo l('rms_tab_rates', true); ?>
            </a>
        </li>
        <li role="presentation">
            <a href="#rms-restrictions-tab" aria-controls="rms-restrictions-tab" role="tab" data-toggle="tab">
                <?php echo l('rms_tab_restrictions', true); ?>
            </a>
        </li>
        <li role="presentation">
            <a href="#rms-forecast-tab" aria-controls="rms-forecast-tab" role="tab" data-toggle="tab">
                <?php echo l('rms_tab_forecast', true); ?>
            </a>
        </li>
    </ul>

    <div class="tab-content rms-tab-content">
        <div role="tabpanel" class="tab-pane active" id="rms-rates-tab">
            <div class="rms-toolbar">
                <div class="form-inline">
                    <div class="form-group">
                        <label for="rms-rate-plan-select"><?php echo l('rms_rate_plan', true); ?></label>
                        <select id="rms-rate-plan-select" class="form-control">
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
                        <button type="button" class="btn btn-default" id="rms-prev-week" title="<?php echo l('rms_previous_week', true); ?>">
                            <span class="glyphicon glyphicon-chevron-left"></span>
                        </button>
                        <button type="button" class="btn btn-default" id="rms-today"><?php echo l('rms_today', true); ?></button>
                        <button type="button" class="btn btn-default" id="rms-next-week" title="<?php echo l('rms_next_week', true); ?>">
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
            <div id="rms-rates-grid" class="rms-grid-wrap" data-view="rates"></div>
        </div>

        <div role="tabpanel" class="tab-pane" id="rms-restrictions-tab">
            <div class="rms-toolbar">
                <p class="rms-hint text-muted"><?php echo l('rms_restrictions_hint', true); ?></p>
            </div>
            <div id="rms-restrictions-grid" class="rms-grid-wrap" data-view="restrictions"></div>
        </div>

        <div role="tabpanel" class="tab-pane" id="rms-forecast-tab">
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
                </div>
                <div class="rms-stat-card">
                    <span class="rms-stat-label"><?php echo l('rms_avg_occupancy', true); ?></span>
                    <span class="rms-stat-value" id="rms-stat-occupancy">
                        <?php echo (int) (isset($forecast_payload['summary']['avgOccupancy']) ? $forecast_payload['summary']['avgOccupancy'] : 0); ?>%
                    </span>
                </div>
            </div>

            <div class="rms-forecast-chart" id="rms-forecast-chart"></div>
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
    </div>
</div>

<script type="application/json" id="rms-config"><?php
echo json_encode(array(
    'sellingDate' => $selling_date,
    'defaultRatePlanId' => $default_rate_plan_id,
    'defaultRoomTypeId' => $default_room_type_id,
    'ratesUrl' => base_url('revenue_management/get_rates_AJAX'),
    'forecastUrl' => base_url('revenue_management/get_forecast_AJAX'),
    'editRatesBaseUrl' => base_url('settings/rates/edit_rates/'),
    'forecastDays' => $forecast_days,
    'currencySymbol' => $currency_symbol,
), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?></script>

<script type="application/json" id="rms-forecast-data"><?php
echo json_encode($forecast_payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?></script>
