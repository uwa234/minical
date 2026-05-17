<?php
$group_id = (int) $group['id'];
$billing_mode = !empty($group['billing_mode']) ? $group['billing_mode'] : 'room';
?>
<div class="groups-page groups-detail" data-group-id="<?php echo $group_id; ?>">
    <div class="groups-header">
        <a href="<?php echo base_url('groups'); ?>" class="btn btn-link">&larr; <?php echo l('groups', true); ?></a>
        <h1 class="groups-title"><?php echo htmlspecialchars($group['name']); ?> <small>#<?php echo $group_id; ?></small></h1>
        <p class="groups-subtitle">
            <?php if (!empty($group['check_in_date'])): ?>
                <?php echo htmlspecialchars($group['check_in_date']); ?> &ndash; <?php echo htmlspecialchars($group['check_out_date']); ?>
            <?php endif; ?>
            &middot; <?php echo (int) $group['booking_count']; ?> <?php echo l('bookings', true); ?>
        </p>
        <div class="groups-header-actions">
            <a class="btn btn-primary" href="<?php echo base_url('invoice/show_master_invoice/' . $group_id); ?>"><?php echo l('master_folio', true); ?></a>
            <a class="btn btn-default" href="<?php echo base_url('booking'); ?>"><?php echo l('open_calendar', true); ?></a>
            <a class="btn btn-default" href="<?php echo base_url('groups/export_rooming_csv/' . $group_id); ?>"><?php echo l('export_rooming_csv', true); ?></a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading"><?php echo l('billing_settings', true); ?></div>
                <div class="panel-body">
                    <div class="form-group">
                        <label><?php echo l('billing_mode', true); ?></label>
                        <select id="group-billing-mode" class="form-control">
                            <option value="room" <?php echo $billing_mode === 'room' ? 'selected' : ''; ?>><?php echo l('bill_per_room', true); ?></option>
                            <option value="master" <?php echo $billing_mode === 'master' ? 'selected' : ''; ?>><?php echo l('bill_to_master', true); ?></option>
                        </select>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" id="route-existing-charges" value="1">
                            <?php echo l('route_existing_charges', true); ?>
                        </label>
                    </div>
                    <button type="button" class="btn btn-sm btn-success" id="save-billing-settings"><?php echo l('save', true); ?></button>
                </div>
            </div>

            <?php if (!empty($linked_blocks)): ?>
            <div class="panel panel-default">
                <div class="panel-heading"><?php echo l('linked_inventory_blocks', true); ?></div>
                <ul class="list-group">
                    <?php foreach ($linked_blocks as $block): ?>
                    <li class="list-group-item">
                        <strong><?php echo htmlspecialchars($block['name']); ?></strong><br>
                        <small><?php echo htmlspecialchars($block['check_in_date']); ?> &ndash; <?php echo htmlspecialchars($block['check_out_date']); ?></small>
                        <span class="label label-<?php echo $block['status'] === 'active' ? 'success' : 'default'; ?> pull-right"><?php echo htmlspecialchars($block['status']); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading clearfix">
                    <span><?php echo l('rooming_list', true); ?></span>
                    <div class="pull-right rooming-toolbar">
                        <select id="unassigned-room-type" class="form-control input-sm" style="display:inline-block;width:auto;">
                            <?php foreach ($room_types as $rt): ?>
                                <option value="<?php echo (int) $rt['id']; ?>"><?php echo htmlspecialchars($rt['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" id="unassigned-count" class="form-control input-sm" value="1" min="1" max="20" style="width:60px;display:inline-block;">
                        <button type="button" class="btn btn-sm btn-default" id="add-unassigned-slots"><?php echo l('add_unassigned', true); ?></button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover" id="rooming-table">
                        <thead>
                            <tr>
                                <th><?php echo l('room', true); ?></th>
                                <th><?php echo l('room_type', true); ?></th>
                                <th><?php echo l('guest_name', true); ?></th>
                                <th><?php echo l('dates', true); ?></th>
                                <th><?php echo l('status', true); ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rooming as $row):
                            $is_unassigned = empty($row['booking_id']) || empty($row['room_name']);
                            $status = l('unassigned', true);
                            if (!empty($row['room_cancelled'])) {
                                $status = l('cancelled', true);
                            } elseif (!empty($row['booking_id']) && !empty($row['room_name'])) {
                                $status = l('assigned', true);
                            } elseif (!empty($row['booking_id'])) {
                                $status = l('reserved', true);
                            }
                        ?>
                            <tr class="rooming-row <?php echo $is_unassigned ? 'rooming-row--unassigned' : ''; ?>"
                                data-entry-id="<?php echo isset($row['id']) ? (int) $row['id'] : ''; ?>"
                                data-booking-id="<?php echo !empty($row['booking_id']) ? (int) $row['booking_id'] : ''; ?>">
                                <td>
                                    <?php if (!empty($row['booking_id'])): ?>
                                        <a href="#" class="open-booking" data-booking-id="<?php echo (int) $row['booking_id']; ?>">
                                            <?php echo htmlspecialchars(isset($row['room_name']) ? $row['room_name'] : '—'); ?>
                                        </a>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars(isset($row['room_type_name']) ? $row['room_type_name'] : ''); ?></td>
                                <td>
                                    <input type="text" class="form-control input-sm rooming-guest"
                                           value="<?php echo htmlspecialchars(isset($row['customer_name']) ? $row['customer_name'] : (isset($row['guest_name']) ? $row['guest_name'] : '')); ?>">
                                </td>
                                <td>
                                    <?php if (!empty($row['check_in_date'])): ?>
                                        <?php echo htmlspecialchars($row['check_in_date']); ?><br>
                                        <?php echo htmlspecialchars($row['check_out_date']); ?>
                                    <?php endif; ?>
                                </td>
                                <td><span class="label label-<?php echo $is_unassigned ? 'warning' : 'success'; ?>"><?php echo $status; ?></span></td>
                                <td>
                                    <button type="button" class="btn btn-xs btn-primary btn-save-rooming"><?php echo l('save_guest', true); ?></button>
                                    <?php if (!empty($row['id']) && empty($row['booking_id'])): ?>
                                    <button type="button" class="btn btn-xs btn-danger btn-delete-slot"><?php echo l('delete_slot', true); ?></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
