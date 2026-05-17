<?php
$website_enabled = !empty($website_enabled);
?>

<div class="channels-page">
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

    <div class="channel-card channel-card--booking">
        <div class="channel-card-head">
            <div>
                <h2><?php echo l('channel_booking_com', true); ?></h2>
                <span class="channel-badge channel-badge--limited"><?php echo l('channel_status_limited', true); ?></span>
            </div>
            <button type="button" class="btn btn-default" id="sync-booking-com-ical">
                <?php echo l('sync_now', true); ?>
            </button>
        </div>
        <p class="channel-card-desc"><?php echo l('channel_booking_com_desc', true); ?></p>

        <div class="alert alert-warning channel-limitations">
            <strong><?php echo l('ical_limitations_title', true); ?></strong>
            <p><?php echo l('ical_limitations_body', true); ?></p>
        </div>

        <?php if (empty($room_types)) : ?>
            <p class="text-muted"><?php echo l('no_room_types', true); ?></p>
        <?php else : ?>
            <form id="booking-com-ical-form">
                <div class="table-responsive">
                    <table class="table table-striped channel-ical-table">
                        <thead>
                            <tr>
                                <th><?php echo l('room_type', true); ?></th>
                                <th><?php echo l('ical_import_url', true); ?></th>
                                <th><?php echo l('ical_export_url', true); ?></th>
                                <th class="channel-flags-col"><?php echo l('enable_import', true); ?> / <?php echo l('enable_export', true); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($room_types as $room_type) :
                                $rt_id = $room_type['id'];
                                $mapping = isset($mappings_by_room[$rt_id]) ? $mappings_by_room[$rt_id] : null;
                                $export_token = $mapping && !empty($mapping['export_token']) ? $mapping['export_token'] : '';
                                $export_url = $export_token
                                    ? base_url('channels/ical_export/' . $mapping['export_token'] . '/' . $rt_id)
                                    : '';
                                ?>
                                <tr data-room-type-id="<?php echo (int) $rt_id; ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($room_type['name']); ?></strong>
                                        <?php if (!empty($mapping['last_import_at'])) : ?>
                                            <br><small class="text-muted">Last sync: <?php echo htmlspecialchars($mapping['last_import_at']); ?>
                                            <?php if (!empty($mapping['last_import_message'])) : ?>
                                                — <?php echo htmlspecialchars($mapping['last_import_message']); ?>
                                            <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="url" class="form-control input-sm"
                                               name="mappings[<?php echo (int) $rt_id; ?>][import_url]"
                                               placeholder="https://admin.booking.com/..."
                                               value="<?php echo $mapping && !empty($mapping['import_url']) ? htmlspecialchars($mapping['import_url']) : ''; ?>">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control input-sm export-url-field" readonly
                                               value="<?php echo htmlspecialchars($export_url); ?>">
                                        <button type="button" class="btn btn-link btn-xs regenerate-export-token"
                                                data-room-type-id="<?php echo (int) $rt_id; ?>">
                                            <?php echo l('regenerate_export_link', true); ?>
                                        </button>
                                    </td>
                                    <td class="channel-flags-col">
                                        <label class="channel-check">
                                            <input type="checkbox"
                                                   name="mappings[<?php echo (int) $rt_id; ?>][import_enabled]" value="1"
                                                <?php echo ($mapping && !empty($mapping['import_enabled'])) ? 'checked' : ''; ?>>
                                            <?php echo l('enable_import', true); ?>
                                        </label>
                                        <label class="channel-check">
                                            <input type="checkbox"
                                                   name="mappings[<?php echo (int) $rt_id; ?>][export_enabled]" value="1"
                                                <?php echo ($mapping && !empty($mapping['export_enabled'])) ? 'checked' : ''; ?>>
                                            <?php echo l('enable_export', true); ?>
                                        </label>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-primary"><?php echo l('save_channels', true); ?></button>
            </form>
        <?php endif; ?>
    </div>

    <div class="channel-card channel-card--disabled">
        <h2><?php echo l('channel_expedia', true); ?></h2>
        <span class="channel-badge channel-badge--muted"><?php echo l('channel_expedia_coming', true); ?></span>
    </div>
</div>
