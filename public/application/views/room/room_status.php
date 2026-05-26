<!-- Modal -->
<div class="modal fade" id="room_notes_modal" tabindex="-1" role="dialog" aria-labelledby="label" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="myModalLabel"><?php echo l('Edit',true).' '.l($this->default_room_singular).' '.l('Notes',true) ; ?></h4>
			</div>
			<div class="modal-body">
				<textarea id="room-notes" class="form-control" rows=5>
				</textarea>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" id="save-room-notes-button"><?php echo l('save_changes'); ?></button>
				<button type="button" class="btn btn-light" data-dismiss="modal"><?php echo l('close'); ?></button>
			</div>
		</div>
	</div>
</div>

<!-- Modal -->
<div class="modal fade" id="room_instructions_modal" tabindex="-1" role="dialog" aria-labelledby="label" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="myModalLabel"><?php echo l('Edit',true).' '.l($this->default_room_singular).' '.l('Instructions',true) ; ?></h4>
			</div>
			<div class="modal-body">
				<textarea id="room-instructions" class="form-control" rows=5>
				</textarea>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" id="save-room-instructions-button"><?php echo l('save_changes'); ?></button>
				<button type="button" class="btn btn-light" data-dismiss="modal"><?php echo l('close'); ?></button>
			</div>
		</div>
	</div>
</div>

<!-- SHow Rating Modal -->
<div class="modal fade" id="room_rating_modal" tabindex="-1" role="dialog" aria-labelledby="label" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="myModalLabel"><?php echo l('Reviews'); ?></h4>
			</div>
			<div class="modal-body view-rating">
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-light" data-dismiss="modal"><?php echo l('close'); ?></button>
			</div>
		</div>
	</div>
</div>

<div class="rooms-page mc-page">
	<header class="rooms-header">
		<div class="rooms-header-text">
			<div class="rooms-header-icon" aria-hidden="true">
				<i class="fa fa-bed"></i>
			</div>
			<div>
				<h1 class="rooms-title"><?php echo l($this->default_room_singular).' '.l('Status',true); ?></h1>
				<p class="rooms-subtitle">Track cleanliness, guest assignments, notes, and check-in instructions.</p>
			</div>
		</div>
		<div class="rooms-header-actions">
			<button type="button" class="btn btn-success" id="set_rooms_clean">
				<i class="fa fa-check-circle" aria-hidden="true"></i>
				<?php echo l('Clean All',true).' '.l($this->default_room_plural); ?>
			</button>
			<a href="<?php echo base_url() . 'settings/room_inventory/rooms';?>" class="btn btn-default">
				<i class="fa fa-pencil" aria-hidden="true"></i>
				<?php echo l('Edit', true) .' '. l($this->default_room_plural); ?>
			</a>
		</div>
	</header>

	<div class="rooms-card">
		<div class="rooms-card-body">
			<?php if (isset($rows) && $rows) : ?>
			<div class="table-responsive">
				<table class="table table-hover table-rating rooms-status-table">
					<thead>
						<tr>
							<th class="td-room-name text-center"><?php echo l($this->default_room_plural); ?></th>
							<th class="td-room-type text-center"><?php echo l($this->default_room_singular).' '.l('Types',true); ?></th>
							<th class="td-customer-name text-left"><?php echo l('customer'); ?></th>
							<th class="td-room-status text-center"><?php echo l('status'); ?></th>
							<th class="text-left"><?php echo l($this->default_room_singular).' '.l('Notes',true); ?></th>
							<th class="text-left"><?php echo l('Check-In Instructions'); ?></th>
							<?php if ($this->review_management_settings) { ?>
							<th class="text-left" data-content="Score must be betweeen 0 to 10, a float number. 10 as the highest" rel="popover" data-placement="top" data-container="body" data-trigger="hover">
								<?php echo l('Score'); ?>
								&nbsp;<i class="fa fa-question-circle" aria-hidden="true"></i>
							</th>
							<th class="text-center"><?php echo l('Rating'); ?></th>
							<?php } ?>
							<th class="rooms-th-actions"></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($rows as $r) : ?>
						<tr class="room_tr" name="<?php echo $r->room_id; ?>">
							<td class="text-center room_name"><?php echo $r->room_name; ?></td>
							<td class="text-center td-room-type"><?php echo $r->acronym; ?></td>
							<td class="td-customer-name text-left">
								<div class="booking_tr" name="<?php echo $r->booking_id; ?>"><?php echo $r->customer_name; ?></div>
							</td>
							<td class="text-center">
								<select autocomplete="off" class="room_status form-control">
									<option <?php if ($r->status == 'Clean') {echo 'selected="selected"';}?>><?php echo l('Clean', true); ?></option>
									<option <?php if ($r->status == 'Dirty') {echo 'selected="selected"';}?>><?php echo l('Dirty', true); ?></option>
									<option <?php if ($r->status == 'Inspected') {echo 'selected="selected"';}?>><?php echo l('Inspected', true); ?></option>
								</select>
								<?php if (!empty($r->booking_status) && $r->booking_status == 3) : ?>
								<p class="out-of-order text-center"><?php echo l("OUT OF ORDER", true); ?></p>
								<?php endif; ?>
							</td>
							<td class="text-left room_notes-cell"><?php echo str_replace("\n", "<br/>", $r->notes); ?></td>
							<td class="room_instructions-cell"><?php echo str_replace("\n", "<br/>", $r->instructions); ?></td>
							<?php if ($this->review_management_settings) { ?>
							<td class="text-left">
								<input class="form-control room_score" type="number" min="0" max="10" name="room_score" value="<?php echo $r->score; ?>">
							</td>
							<td class="text-center">
								<input class="star-rating" name="star-rating" type="hidden" value="<?php echo isset($r->rating) && $r->rating ? $r->rating : 0; ?>" />
								<a href="javascript:" class="show_rating" data-room_id="<?php echo $r->room_id; ?>" data-room_rating="<?php echo isset($r->rating) && $r->rating ? $r->rating : 0; ?>">
									<div class="rateit stars" data-rateit-starwidth="16" data-rateit-starheight="16" style="pointer-events: none;"></div>
								</a>
								<br/>
								<?php echo isset($r->total_ratings) && $r->total_ratings ? $r->total_ratings > 1 ? '('.$r->total_ratings.' '.l("ratings", true).')' : '('.$r->total_ratings.' '.l("rating", true).')' : ''; ?>
							</td>
							<?php } ?>
							<td class="td-room-notes room-actions text-right">
								<button type="button" class="room-notes-button btn btn-default btn-sm">
									<?php echo l('Edit',true).' '.l($this->default_room_singular).' '.l('Note',true); ?>
								</button>
								<button type="button" class="room-instructions-button btn btn-default btn-sm">
									<?php echo l('Edit Instructions', true); ?>
								</button>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php else : ?>
			<div class="rooms-empty">
				<p><?php echo l('No',true).' '.l($this->default_room_singular).' '.l('types have been recorded.',true); ?></p>
				<a href="<?php echo base_url() . 'settings/room_inventory/rooms';?>" class="btn btn-primary">
					<?php echo l('Edit', true) .' '. l($this->default_room_plural); ?>
				</a>
			</div>
			<?php endif; ?>
		</div>
	</div>
</div>
