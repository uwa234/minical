<div class="pa-page-header">
    <div>
        <h1>Trial registrations</h1>
        <p>Free trial sign-ups from the marketing homepage.</p>
    </div>
</div>

<form method="get" action="<?php echo base_url('admin/trial_registrations'); ?>" class="pa-filters">
    <input type="text" name="search_query" class="form-control input-sm" placeholder="Search property, name, email, or phone"
           value="<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>" />
    <button type="submit" class="pa-btn pa-btn-secondary pa-btn-sm">Search</button>
    <?php if ($search_query !== '') { ?>
        <a class="pa-btn pa-btn-sm" href="<?php echo base_url('admin/trial_registrations'); ?>">Clear</a>
    <?php } ?>
</form>

<div class="pa-card">
    <div class="pa-card-body pa-table-wrap">
        <table class="pa-table" id="trial-registrations-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Property</th>
                    <th>Rooms</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Submitted</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registrations)) { ?>
                    <tr>
                        <td colspan="8" class="text-muted">No trial registrations yet.</td>
                    </tr>
                <?php } else {
                    foreach ($registrations as $row) {
                        $contact = trim($row['first_name'] . ' ' . $row['last_name']);
                        ?>
                        <tr data-request-id="<?php echo (int) $row['trial_registration_request_id']; ?>">
                            <td><?php echo (int) $row['trial_registration_request_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['property_name'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td><?php echo (int) $row['number_of_rooms']; ?></td>
                            <td><?php echo htmlspecialchars($contact, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <a href="mailto:<?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </td>
                            <td>
                                <?php if (!empty($row['phone'])) { ?>
                                    <a href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $row['phone']), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($row['phone'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                <?php } else { ?>
                                    <span class="text-muted">—</span>
                                <?php } ?>
                            </td>
                            <td>
                                <select class="form-control input-sm trial-registration-status"
                                        data-request-id="<?php echo (int) $row['trial_registration_request_id']; ?>">
                                    <?php
                                    $statuses = array(
                                        'new' => 'New',
                                        'contacted' => 'Contacted',
                                        'converted' => 'Converted',
                                        'declined' => 'Declined',
                                    );
                                    foreach ($statuses as $value => $label) {
                                        $sel = (isset($row['status']) && $row['status'] === $value) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '" ' . $sel . '>'
                                            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                            <td><?php echo htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php }
                } ?>
            </tbody>
        </table>
    </div>
</div>
