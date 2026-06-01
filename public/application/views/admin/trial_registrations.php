<div class="pa-page-header">
    <div>
        <h1>Trial registrations</h1>
        <p>Free trial sign-ups from the marketing homepage. Convert a lead into a trialing property in one step.</p>
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
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registrations)) { ?>
                    <tr>
                        <td colspan="9" class="text-muted">No trial registrations yet.</td>
                    </tr>
                <?php } else {
                    foreach ($registrations as $row) {
                        $contact = trim($row['first_name'] . ' ' . $row['last_name']);
                        $lead_id = (int) $row['trial_registration_request_id'];
                        $linked_company_id = !empty($row['company_id']) ? (int) $row['company_id'] : 0;
                        ?>
                        <tr data-request-id="<?php echo $lead_id; ?>"
                            data-company-id="<?php echo $linked_company_id; ?>">
                            <td><?php echo $lead_id; ?></td>
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
                                        data-request-id="<?php echo $lead_id; ?>"
                                        <?php echo $linked_company_id ? 'disabled title="Linked to property #' . $linked_company_id . '"' : ''; ?>>
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
                            <td class="pa-actions-cell">
                                <?php if ($linked_company_id) { ?>
                                    <a class="pa-btn pa-btn-primary pa-btn-sm"
                                       href="<?php echo base_url('admin/property_detail/' . $linked_company_id); ?>">
                                        View property
                                    </a>
                                <?php } else { ?>
                                    <button type="button"
                                            class="pa-btn pa-btn-secondary pa-btn-sm trial-send-outreach-btn"
                                            data-request-id="<?php echo $lead_id; ?>">
                                        Send outreach
                                    </button>
                                    <button type="button"
                                            class="pa-btn pa-btn-primary pa-btn-sm trial-create-property-btn"
                                            data-request-id="<?php echo $lead_id; ?>"
                                            data-property-name="<?php echo htmlspecialchars($row['property_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-number-of-rooms="<?php echo (int) $row['number_of_rooms']; ?>"
                                            data-owner-email="<?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-owner-first-name="<?php echo htmlspecialchars($row['first_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-owner-last-name="<?php echo htmlspecialchars($row['last_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                        Create property
                                    </button>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php }
                } ?>
            </tbody>
        </table>
    </div>
</div>

<?php $this->load->view('admin/_add_property_modal', array(
    'default_trial_days' => $default_trial_days,
    'pricing_tiers' => isset($pricing_tiers) ? $pricing_tiers : array(),
)); ?>

<script type="text/javascript">
    window.saasDefaultTrialDays = <?php echo (int) $default_trial_days; ?>;
    <?php if (!empty($open_lead_id)) { ?>
    window.saasOpenTrialLeadId = <?php echo (int) $open_lead_id; ?>;
    <?php } ?>
</script>
