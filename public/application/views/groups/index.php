<div class="groups-page">
    <div class="groups-header">
        <h1 class="groups-title"><?php echo l('groups_blocks_hub', true); ?></h1>
        <p class="groups-subtitle"><?php echo l('linked_groups', true); ?> &amp; <?php echo l('inventory_blocks', true); ?></p>
    </div>

    <ul class="nav nav-tabs groups-tabs" role="tablist">
        <li role="presentation" class="active"><a href="#tab-groups" data-toggle="tab"><?php echo l('linked_groups', true); ?></a></li>
        <li role="presentation"><a href="#tab-blocks" data-toggle="tab"><?php echo l('inventory_blocks', true); ?></a></li>
    </ul>

    <div class="tab-content groups-tab-content">
        <div role="tabpanel" class="tab-pane active" id="tab-groups">
            <form class="form-inline groups-filters" method="get" action="<?php echo base_url('groups'); ?>">
                <input type="text" name="q" class="form-control" placeholder="<?php echo l('search_groups', true); ?>"
                       value="<?php echo htmlspecialchars($this->input->get('q')); ?>">
                <input type="text" name="group_id" class="form-control" placeholder="<?php echo l('group_id', true); ?>"
                       value="<?php echo htmlspecialchars($this->input->get('group_id')); ?>">
                <button type="submit" class="btn btn-primary"><?php echo l('search', true); ?></button>
            </form>

            <div class="table-responsive">
                <table class="table table-striped groups-table">
                    <thead>
                        <tr>
                            <th><?php echo l('group_id', true); ?></th>
                            <th><?php echo l('group_name', true); ?></th>
                            <th><?php echo l('guest', true); ?></th>
                            <th><?php echo l('dates', true); ?></th>
                            <th><?php echo l('actions', true); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($groups)): foreach ($groups as $g): ?>
                        <tr>
                            <td><?php echo (int) $g['id']; ?></td>
                            <td><?php echo htmlspecialchars($g['name']); ?></td>
                            <td><?php echo htmlspecialchars(isset($g['customer_name']) ? $g['customer_name'] : ''); ?></td>
                            <td>
                                <?php if (!empty($g['check_in_date'])): ?>
                                    <?php echo htmlspecialchars($g['check_in_date']); ?> &ndash; <?php echo htmlspecialchars($g['check_out_date']); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a class="btn btn-sm btn-default" href="<?php echo base_url('groups/view/' . $g['id']); ?>"><?php echo l('view_group', true); ?></a>
                                <a class="btn btn-sm btn-primary" href="<?php echo base_url('invoice/show_master_invoice/' . $g['id']); ?>"><?php echo l('master_folio', true); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="5"><?php echo l('no_groups_found', true); ?></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div role="tabpanel" class="tab-pane" id="tab-blocks">
            <div class="row">
                <div class="col-md-5">
                    <h3><?php echo l('create_block', true); ?></h3>
                    <form id="create-block-form" class="groups-block-form">
                        <div class="form-group">
                            <label><?php echo l('block_name', true); ?></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-sm-6 form-group">
                                <label><?php echo l('check_in', true); ?></label>
                                <input type="date" name="check_in_date" class="form-control" required>
                            </div>
                            <div class="col-sm-6 form-group">
                                <label><?php echo l('check_out', true); ?></label>
                                <input type="date" name="check_out_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6 form-group">
                                <label><?php echo l('cutoff_date', true); ?></label>
                                <input type="date" name="cutoff_date" class="form-control">
                            </div>
                            <div class="col-sm-6 form-group">
                                <label><?php echo l('release_date', true); ?></label>
                                <input type="date" name="release_date" class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <label><?php echo l('notes', true); ?></label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                        <hr>
                        <div id="block-lines">
                            <div class="block-line row">
                                <div class="col-sm-7 form-group">
                                    <label><?php echo l('room_type', true); ?></label>
                                    <select name="room_type_id[]" class="form-control block-room-type">
                                        <?php foreach ($room_types as $rt): ?>
                                            <option value="<?php echo (int) $rt['id']; ?>"><?php echo htmlspecialchars($rt['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-sm-5 form-group">
                                    <label><?php echo l('quantity', true); ?></label>
                                    <input type="number" name="quantity[]" class="form-control" min="1" value="1">
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-link" id="add-block-line"><?php echo l('add_line', true); ?></button>
                        <button type="submit" class="btn btn-success"><?php echo l('create_block', true); ?></button>
                    </form>
                </div>
                <div class="col-md-7">
                    <form class="form-inline groups-filters" method="get" action="<?php echo base_url('groups'); ?>#tab-blocks">
                        <input type="text" name="block_q" class="form-control" placeholder="<?php echo l('search_blocks', true); ?>"
                               value="<?php echo htmlspecialchars($this->input->get('block_q')); ?>">
                        <select name="block_status" class="form-control">
                            <option value=""><?php echo l('all_statuses', true); ?></option>
                            <option value="active" <?php echo $this->input->get('block_status') === 'active' ? 'selected' : ''; ?>><?php echo l('block_status_active', true); ?></option>
                            <option value="released" <?php echo $this->input->get('block_status') === 'released' ? 'selected' : ''; ?>><?php echo l('block_status_released', true); ?></option>
                        </select>
                        <button type="submit" class="btn btn-default"><?php echo l('search', true); ?></button>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-striped" id="blocks-table">
                            <thead>
                                <tr>
                                    <th><?php echo l('block_name', true); ?></th>
                                    <th><?php echo l('dates', true); ?></th>
                                    <th><?php echo l('status', true); ?></th>
                                    <th><?php echo l('actions', true); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($blocks as $block): ?>
                                <tr data-block-id="<?php echo (int) $block['id']; ?>">
                                    <td><?php echo htmlspecialchars($block['name']); ?></td>
                                    <td><?php echo htmlspecialchars($block['check_in_date']); ?> &ndash; <?php echo htmlspecialchars($block['check_out_date']); ?></td>
                                    <td><span class="label label-<?php echo $block['status'] === 'active' ? 'success' : 'default'; ?>"><?php echo htmlspecialchars($block['status']); ?></span></td>
                                    <td>
                                        <button type="button" class="btn btn-xs btn-info btn-view-block"><?php echo l('view_group', true); ?></button>
                                        <?php if ($block['status'] === 'active'): ?>
                                        <button type="button" class="btn btn-xs btn-warning btn-release-block"><?php echo l('release_block', true); ?></button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div id="block-detail-panel" class="well hidden"></div>
                </div>
            </div>
        </div>
    </div>
</div>
