<?php
/**
 * @var string $channel_key
 * @var array $channel_ui
 * @var array $room_types
 * @var array $mappings_by_room
 */
$channel_key = isset($channel_key) ? $channel_key : '';
$card_class = isset($channel_ui['card_class']) ? $channel_ui['card_class'] : '';
$import_placeholder = isset($channel_ui['import_placeholder']) ? $channel_ui['import_placeholder'] : 'https://...';
$mappings_by_room = isset($mappings_by_room) ? $mappings_by_room : array();

$lang_keys = isset($channel_ui['lang']) ? $channel_ui['lang'] : array();
$title_key = isset($lang_keys['title']) ? $lang_keys['title'] : 'channels';
$desc_key = isset($lang_keys['desc']) ? $lang_keys['desc'] : 'channels_subtitle';
$limitations_key = isset($lang_keys['limitations']) ? $lang_keys['limitations'] : 'ical_limitations_body';
$import_url_key = isset($lang_keys['import_url']) ? $lang_keys['import_url'] : 'ical_import_url';
$export_url_key = isset($lang_keys['export_url']) ? $lang_keys['export_url'] : 'ical_export_url';
$enable_import_key = isset($lang_keys['enable_import']) ? $lang_keys['enable_import'] : 'enable_import';
$enable_export_key = isset($lang_keys['enable_export']) ? $lang_keys['enable_export'] : 'enable_export';
$regenerate_hint_key = isset($lang_keys['regenerate_hint']) ? $lang_keys['regenerate_hint'] : 'regenerate_export_link';
?>

<div class="channel-card <?php echo htmlspecialchars($card_class); ?>" data-channel-key="<?php echo htmlspecialchars($channel_key); ?>">
    <div class="channel-card-head">
        <div>
            <h2><?php echo l($title_key, true); ?></h2>
            <span class="channel-badge channel-badge--limited"><?php echo l('channel_status_limited', true); ?></span>
        </div>
        <button type="button" class="btn btn-default sync-ical-channel"
                data-channel-key="<?php echo htmlspecialchars($channel_key); ?>">
            <?php echo l('sync_now', true); ?>
        </button>
    </div>
    <p class="channel-card-desc"><?php echo l($desc_key, true); ?></p>

    <div class="alert alert-warning channel-limitations">
        <strong><?php echo l('ical_limitations_title', true); ?></strong>
        <p><?php echo l($limitations_key, true); ?></p>
    </div>

    <?php if (empty($room_types)) : ?>
        <p class="text-muted"><?php echo l('no_room_types_ical', true); ?></p>
    <?php else : ?>
        <form class="channel-ical-form" data-channel-key="<?php echo htmlspecialchars($channel_key); ?>">
            <input type="hidden" name="channel_key" value="<?php echo htmlspecialchars($channel_key); ?>">
            <div class="table-responsive">
                <table class="table table-striped channel-ical-table">
                    <thead>
                        <tr>
                            <th><?php echo l('room_type', true); ?></th>
                            <th><?php echo l($import_url_key, true); ?></th>
                            <th><?php echo l($export_url_key, true); ?></th>
                            <th class="channel-flags-col"><?php echo l('enable_import_short', true); ?> / <?php echo l('enable_export_short', true); ?></th>
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
                                        <br><small class="text-muted"><?php echo l('last_sync', true); ?>: <?php echo htmlspecialchars($mapping['last_import_at']); ?>
                                        <?php if (!empty($mapping['last_import_message'])) : ?>
                                            — <?php echo htmlspecialchars($mapping['last_import_message']); ?>
                                        <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <input type="url" class="form-control input-sm"
                                           name="mappings[<?php echo (int) $rt_id; ?>][import_url]"
                                           placeholder="<?php echo htmlspecialchars($import_placeholder); ?>"
                                           value="<?php echo $mapping && !empty($mapping['import_url']) ? htmlspecialchars($mapping['import_url']) : ''; ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control input-sm export-url-field" readonly
                                           value="<?php echo htmlspecialchars($export_url); ?>">
                                    <button type="button" class="btn btn-link btn-xs regenerate-export-token"
                                            data-room-type-id="<?php echo (int) $rt_id; ?>"
                                            data-channel-key="<?php echo htmlspecialchars($channel_key); ?>"
                                            title="<?php echo htmlspecialchars(l($regenerate_hint_key, true)); ?>">
                                        <?php echo l('regenerate_export_link', true); ?>
                                    </button>
                                </td>
                                <td class="channel-flags-col">
                                    <label class="channel-check">
                                        <input type="checkbox"
                                               name="mappings[<?php echo (int) $rt_id; ?>][import_enabled]" value="1"
                                            <?php echo ($mapping && !empty($mapping['import_enabled'])) ? 'checked' : ''; ?>>
                                        <?php echo l($enable_import_key, true); ?>
                                    </label>
                                    <label class="channel-check">
                                        <input type="checkbox"
                                               name="mappings[<?php echo (int) $rt_id; ?>][export_enabled]" value="1"
                                            <?php echo ($mapping && !empty($mapping['export_enabled'])) ? 'checked' : ''; ?>>
                                        <?php echo l($enable_export_key, true); ?>
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
