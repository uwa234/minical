<?php
$website_enabled = !empty($website_enabled);
$ical_channels = isset($ical_channels) ? $ical_channels : array();
$mappings_by_channel = isset($mappings_by_channel) ? $mappings_by_channel : array();
?>

<div class="channels-page mc-page">
    <div class="channels-header">
        <h1 class="channels-title"><?php echo l('channels', true); ?></h1>
        <p class="channels-subtitle"><?php echo l('channels_subtitle', true); ?></p>
    </div>

    <div class="channel-card channel-card--website">
        <div class="channel-card-head">
            <div>
                <h2><?php echo l('channel_website', true); ?></h2>
                <span class="channel-badge channel-badge--native"><?php echo l('channel_native', true); ?></span>
                <span id="website-channel-status-badge"
                      class="channel-badge channel-badge--<?php echo $website_enabled ? 'ok' : 'muted'; ?>">
                    <?php echo $website_enabled ? l('channel_status_connected', true) : l('channel_status_not_connected', true); ?>
                </span>
            </div>
        </div>
        <p class="channel-card-desc"><?php echo l('channel_website_desc', true); ?></p>
        <div class="channel-online-toggle">
            <div class="channel-toggle-row">
                <div class="checkbox checbox-switch switch-primary">
                    <label>
                        <input type="checkbox" id="website-online-reservations-toggle"
                            data-label-on="<?php echo htmlspecialchars(l('channel_status_connected', true), ENT_QUOTES, 'UTF-8'); ?>"
                            data-label-off="<?php echo htmlspecialchars(l('channel_status_not_connected', true), ENT_QUOTES, 'UTF-8'); ?>"
                            <?php echo $website_enabled ? 'checked' : ''; ?>>
                        <span></span>
                    </label>
                </div>
                <div class="channel-toggle-text">
                    <strong><?php echo l('accept_online_reservations', true); ?></strong>
                    <p class="channel-toggle-help"><?php echo l('accept_online_reservations_help', true); ?></p>
                </div>
            </div>
        </div>
        <div class="channel-field">
            <label><?php echo l('booking_engine_url', true); ?></label>
            <div class="channel-url-row">
                <input type="text" class="form-control" id="booking-engine-url" readonly
                       value="<?php echo htmlspecialchars($booking_engine_url); ?>">
                <button type="button" class="btn btn-default" id="copy-booking-engine-url">
                    <?php echo l('copy_url', true); ?>
                </button>
            </div>
        </div>
        <p class="channel-hint">
            <a href="<?php echo base_url('extensions'); ?>"><?php echo l('website_settings_hint', true); ?></a>
        </p>
    </div>

    <?php foreach ($ical_channels as $channel_key => $channel_ui) :
        $this->load->view('channels/_ical_channel_card', array(
            'channel_key' => $channel_key,
            'channel_ui' => $channel_ui,
            'room_types' => $room_types,
            'mappings_by_room' => isset($mappings_by_channel[$channel_key]) ? $mappings_by_channel[$channel_key] : array(),
        ));
    endforeach; ?>
</div>
