<div class="rooms-page mc-page">
<header class="rooms-header">
   <div class="rooms-header-text">
      <div class="rooms-header-icon rooms-header-icon--inventory" aria-hidden="true">
         <i class="fa fa-calendar"></i>
      </div>
      <div>
         <h1 class="rooms-title"><?php echo l($this->default_room_singular).' '.l('Inventory',true); ?></h1>
         <p class="rooms-subtitle">View and adjust availability across channels by room type and date.</p>
      </div>
   </div>
</header>

<div class="rooms-card">
<div class="rooms-card-body">
   <div class="modal fade m-012" id="modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
         <div class="modal-content">
            <div class="modal-header">
               <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
               <h4 class="modal-title"><?php echo l('Manually update availabilities for all channels', true); ?></h4>
            </div>
            <div class="modal-body form-inline">
               <div class="form-group">
                  <?php echo l('Update availabilities between', true); ?>
                  <input class="form-control" name="start_date" placeholder="Start Date" value="<?php echo date("Y-m-d"); ?>">
                  <?php echo l('and', true); ?>
               </div>
               <div class="form-group">
                  <input class="form-control" name="end_date" placeholder="End Date" value="<?php echo date('Y-m-d',strtotime(date("Y-m-d", time()) . " + 100 days")); ?>">
               </div>
               (<?php echo l('Maximum once in every 5 minutes', true); ?>)
            </div>
            <div class="modal-footer">
               <button type="button" class="btn btn-success" id="update" data-dismiss="modal">
               <?php echo l('Update', true); ?>
               </button>
               <button type="button" class="btn btn-default" data-dismiss="modal">
               <?php echo l('Close', true); ?>
               </button>
            </div>
         </div>
      </div>
   </div>

   <div class="rooms-inventory-toolbar">
      <ul class="nav nav-tabs channels">
         <?php foreach($channels as $channel): ?>
         <li class="channel <?php if($channel['id'] == -1) { echo 'active'; } ?>" data-id="<?php echo $channel['id'];?>" data-key="<?php echo isset($channel['key']) && $channel['key'] ? $channel['key'] : ''; ?>">
            <a href="#"><?php echo $channel['name'];?></a>
         </li>
         <?php endforeach; ?>
      </ul>
      <div class="rooms-inventory-controls">
         <button disabled class="btn btn-primary modify-availabilities m-011">
            <?php echo l('Modify Maximum Availabilities (Select', true).' '.l($this->default_room_singular).' '.l('Types below)', true); ?>
         </button>
         <span class="btn btn-default change-dates" data-date-diff="-7"><?php echo l('previous_week'); ?></span>
         <span class="btn btn-default change-dates" data-date-diff="7"><?php echo l('next_week'); ?></span>
         <span id="loading_img" style="display:none;">
            <img src="<?php echo base_url().'images/loading.gif' ?>" alt="" style="width: 24px;"/>
         </span>
         <div class="datepicker-wrap">
            <label for="dateStart"><?php echo l('start_date'); ?>:</label>
            <input type="text" class="form-control datepicker" id="dateStart" value="" />
         </div>
      </div>
   </div>

   <div class="rooms-inventory-calendar">
      <div class="calendar" channel_id="0"></div>
   </div>

   <style>
      .inv_hide {
         display: none;
      }
   </style>
   <div id="edit_availabilities_dialog"></div>
   <script id="calendar-temp" type="x-tmpl-mustache">
   <div class="table-responsive">
      <table class="table" >
          <thead>
              <tr>
                  <th></th>
                  <th><?php echo l($this->default_room_singular).' '.l('Type', true); ?></th>
                  {{#is_ota}}
                      <th><?php echo l('OTA close out threshold', true); ?></th>
                  {{/is_ota}}
                  {{#dates}}
                      <th class="date {{current_date}}" >
                          <span class="date-value">{{date}}</span>
                          <br/>
                          {{day}}
                      </th>
                  {{/dates}}
              </tr>
          </thead>

          <tbody>
              {{#rows}}
                  <tr data-id="{{id}}" data-selected="false">
                      <td><input type="checkbox"></td>
                      {{#inventory}}
                          {{#inventory_name}}
                              <td>{{.}}</td>
                          {{/inventory_name}}
                          {{#is_ota}}
                              <td><select value="{{ota_threshold_val}}" style="width: 170px;" class="ota-threshold-dropdown" name="ota-treshold">
                              {{#inventory_threshold}}
                                 <option value = "{{.}}">{{.}}</option>
                              {{/inventory_threshold}}
                              </select></td>
                          {{/is_ota}}
                          {{#inventory_avail}}
                              <td class="availability_{{inventory_avail}} status_{{inventory_closeout_status.status}} {{colortest_class.color_cla}}" key="{{key}}">
                                  <div class="inventory">
                                  {{#inventory_max_avail}}
                                      <?php echo l('Total', true); ?>: <span class="max_avail_val max_avail_val_{{id}}" key="{{id}}">{{.}}</span>
                                      <input type="text" name="inventory_max_avail" size="3" key="{{id}}" class="inv_hide inventory_input_{{id}} form-control" value={{.}} style="padding: 2px;max-width: 50px;" />
                                      <button type="button" key="{{id}}" avail_val="{{.}}" class="inv_hide inv_ok inventory_btn_{{id}} btn btn-success" style="padding: 6px 4px;">Save</button>
                                      <br/>
                                  {{/inventory_max_avail}}
                                  </div>
                                  {{#inventory_sold}}
                                      <?php echo l('Sold', true); ?>: <span class="sold_val sold_val_{{id}}" key="{{id}}">{{.}}</span>
                                      <br/>
                                  {{/inventory_sold}}
                                  {{#inventory_avail}}
                                      <strong><?php echo l('Available', true); ?>: <span class="avail_val avail_val_{{id}}" key="{{id}}">{{.}}</span></strong>
                                  {{/inventory_avail}}
                                  <div class="inven_status_div">
                                   {{#inventory_closeout_status}}
                                      <?php echo l('Status', true); ?>: <span class="inven_status inven_status_{{id}}" key="{{id}}">{{status}}</span>
                                      <select style="max-width: 60px;padding: 0;" name="inventory_status" class="inv_status_select inv_status_select_{{id}} inv_hide form-control" key="{{id}}"><option value="1" {{open_selected}}>Open</option><option value="0" {{close_selected}}>Close</option></select>
                                     <br/>
                                  {{/inventory_closeout_status}}
                                  </div>
                              </td>
                          {{/inventory_avail}}

                      {{/inventory}}
                  </tr>
              {{/rows}}
              <tr style="background-color: #f0f9ff;">
                  <td>
                  </td>
                  <td><b><?php echo l('Total Availability', true); ?></b></td>
                  {{#rows.0.inventory}}
                      {{#is_ota}}
                          <td></td>
                      {{/is_ota}}

                      {{#inventory_avail}}
                          <td>
                              <?php echo l('Total', true); ?>: {{total_max_available}}<br/>
                              <?php echo l('Sold', true); ?>: {{total_sold}}<br/>
                              <strong><?php echo l('Available', true); ?>: {{total_availibility}}</strong>
                          </td>
                      {{/inventory_avail}}
                  {{/rows.0.inventory}}

              </tr>
          </tbody>
      </table>
    </div>
   </script>
</div>
</div>
</div>
