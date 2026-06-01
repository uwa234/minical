<?php if ($this->session->flashdata('admin_message')) { ?>
    <div class="pa-alert pa-alert-success"><i class="fa fa-check-circle" aria-hidden="true"></i><?php echo htmlspecialchars($this->session->flashdata('admin_message'), ENT_QUOTES, 'UTF-8'); ?></div>
<?php } ?>
<?php if ($this->session->flashdata('admin_error')) { ?>
    <div class="pa-alert pa-alert-danger"><i class="fa fa-exclamation-circle" aria-hidden="true"></i><?php echo htmlspecialchars($this->session->flashdata('admin_error'), ENT_QUOTES, 'UTF-8'); ?></div>
<?php } ?>

<div class="pa-page-header">
    <h1>Growth &amp; communication</h1>
    <p>Email templates for outreach, in-app announcements for all tenants, and Slack/webhook alerts for key platform events.</p>
</div>

<div class="pa-comm-nav">
    <a href="#templates" class="pa-comm-nav-link">Email templates</a>
    <a href="#announcements" class="pa-comm-nav-link">In-app announcements</a>
    <a href="#webhooks" class="pa-comm-nav-link">Webhook / Slack</a>
</div>

<section id="templates" class="pa-comm-section">
    <div class="pa-card">
        <div class="pa-card-header"><h2>Email templates</h2></div>
        <div class="pa-card-body">
            <p class="pa-meta-note">Customize outreach to trial leads, payment reminders, and win-back emails for churned tenants. Placeholders are replaced when sending.</p>
            <?php if (empty($email_templates)) { ?>
                <div class="pa-empty"><i class="fa fa-envelope-o" aria-hidden="true"></i> Run database migrations to create email templates.</div>
            <?php } else {
                foreach ($email_templates as $template) {
                    $slug = (string) $template['slug'];
                    $placeholders = isset($template_placeholders[$slug]) ? $template_placeholders[$slug] : '';
                    ?>
                    <form method="post" action="<?php echo base_url('admin/communication'); ?>" class="pa-template-form">
                        <input type="hidden" name="save_email_template" value="1" />
                        <input type="hidden" name="template_slug" value="<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>" />
                        <h3><?php echo htmlspecialchars($template['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <?php if (!empty($template['description'])) { ?>
                            <p class="text-muted"><?php echo htmlspecialchars($template['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php } ?>
                        <?php if ($placeholders !== '') { ?>
                            <p class="pa-meta-note">Placeholders: <?php echo htmlspecialchars($placeholders, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php } ?>
                        <div class="form-group">
                            <label>Subject</label>
                            <input type="text" class="form-control" name="subject" required
                                   value="<?php echo htmlspecialchars($template['subject'], ENT_QUOTES, 'UTF-8'); ?>" />
                        </div>
                        <div class="form-group">
                            <label>Body (HTML)</label>
                            <textarea class="form-control" name="body_html" rows="6" required><?php echo htmlspecialchars($template['body_html'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label><input type="checkbox" name="is_active" value="1" <?php echo !empty($template['is_active']) ? 'checked' : ''; ?> /> Active</label>
                        </div>
                        <button type="submit" class="pa-btn pa-btn-primary pa-btn-sm">Save template</button>
                    </form>
                <?php }
            } ?>
        </div>
    </div>
</section>

<section id="announcements" class="pa-comm-section">
    <div class="pa-settings-grid">
        <div class="pa-card">
            <div class="pa-card-header"><h2><?php echo $edit_announcement ? 'Edit announcement' : 'New announcement'; ?></h2></div>
            <div class="pa-card-body">
                <form method="post" action="<?php echo base_url('admin/communication'); ?>" class="form-horizontal">
                    <input type="hidden" name="save_announcement" value="1" />
                    <?php if ($edit_announcement) { ?>
                        <input type="hidden" name="announcement_id" value="<?php echo (int) $edit_announcement['id']; ?>" />
                    <?php } ?>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Title</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="title" required
                                   value="<?php echo $edit_announcement ? htmlspecialchars($edit_announcement['title'], ENT_QUOTES, 'UTF-8') : ''; ?>" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Message</label>
                        <div class="col-sm-8">
                            <textarea class="form-control" name="body" rows="3" required><?php echo $edit_announcement ? htmlspecialchars($edit_announcement['body'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                            <p class="help-block">Shown as a banner to all logged-in tenants (maintenance, new features, etc.).</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Severity</label>
                        <div class="col-sm-8">
                            <select name="severity" class="form-control">
                                <option value="info" <?php echo ($edit_announcement && $edit_announcement['severity'] === 'info') ? 'selected' : ''; ?>>Info</option>
                                <option value="warning" <?php echo ($edit_announcement && $edit_announcement['severity'] === 'warning') ? 'selected' : ''; ?>>Warning</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Starts at</label>
                        <div class="col-sm-8">
                            <input type="datetime-local" class="form-control" name="starts_at"
                                   value="<?php echo ($edit_announcement && !empty($edit_announcement['starts_at'])) ? htmlspecialchars(str_replace(' ', 'T', substr($edit_announcement['starts_at'], 0, 16)), ENT_QUOTES, 'UTF-8') : ''; ?>" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Ends at</label>
                        <div class="col-sm-8">
                            <input type="datetime-local" class="form-control" name="ends_at"
                                   value="<?php echo ($edit_announcement && !empty($edit_announcement['ends_at'])) ? htmlspecialchars(str_replace(' ', 'T', substr($edit_announcement['ends_at'], 0, 16)), ENT_QUOTES, 'UTF-8') : ''; ?>" />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-4 col-sm-8">
                            <label><input type="checkbox" name="is_active" value="1" <?php echo (!$edit_announcement || !empty($edit_announcement['is_active'])) ? 'checked' : ''; ?> /> Active</label>
                            &nbsp;
                            <label><input type="checkbox" name="dismissible" value="1" <?php echo (!$edit_announcement || !empty($edit_announcement['dismissible'])) ? 'checked' : ''; ?> /> Dismissible by users</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-4 col-sm-8">
                            <button type="submit" class="pa-btn pa-btn-primary"><?php echo $edit_announcement ? 'Update' : 'Create'; ?> announcement</button>
                            <?php if ($edit_announcement) { ?>
                                <a class="pa-btn pa-btn-secondary" href="<?php echo base_url('admin/communication#announcements'); ?>">Cancel</a>
                            <?php } ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="pa-card">
            <div class="pa-card-header"><h2>Scheduled announcements</h2></div>
            <div class="pa-card-body pa-table-wrap">
                <table class="pa-table">
                    <thead>
                        <tr><th>Title</th><th>Severity</th><th>Schedule</th><th>Active</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($announcements)) { ?>
                        <tr><td colspan="5" class="text-muted">No announcements yet</td></tr>
                    <?php } else {
                        foreach ($announcements as $ann) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ann['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="pa-badge pa-badge-<?php echo $ann['severity'] === 'warning' ? 'unpaid' : 'info'; ?>"><?php echo ucfirst(htmlspecialchars($ann['severity'], ENT_QUOTES, 'UTF-8')); ?></span></td>
                                <td class="text-muted">
                                    <?php
                                    $schedule = array();
                                    if (!empty($ann['starts_at'])) {
                                        $schedule[] = 'from ' . $ann['starts_at'];
                                    }
                                    if (!empty($ann['ends_at'])) {
                                        $schedule[] = 'until ' . $ann['ends_at'];
                                    }
                                    echo $schedule ? htmlspecialchars(implode(' ', $schedule), ENT_QUOTES, 'UTF-8') : 'Always';
                                    ?>
                                </td>
                                <td><?php echo !empty($ann['is_active']) ? 'Yes' : 'No'; ?></td>
                                <td>
                                    <a class="pa-btn pa-btn-secondary pa-btn-sm" href="<?php echo base_url('admin/communication?edit_announcement=' . (int) $ann['id']); ?>#announcements">Edit</a>
                                    <form method="post" action="<?php echo base_url('admin/communication'); ?>" style="display:inline;" onsubmit="return confirm('Delete this announcement?');">
                                        <input type="hidden" name="delete_announcement" value="1" />
                                        <input type="hidden" name="announcement_id" value="<?php echo (int) $ann['id']; ?>" />
                                        <button type="submit" class="pa-btn pa-btn-sm pa-btn-danger-soft">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php }
                    } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<section id="webhooks" class="pa-comm-section">
    <div class="pa-card">
        <div class="pa-card-header"><h2>Webhook / Slack alerts</h2></div>
        <div class="pa-card-body">
            <p class="pa-meta-note">Notify your team on new trial sign-ups, payments received, and debtor overdue actions.</p>
            <form method="post" action="<?php echo base_url('admin/communication'); ?>" class="form-horizontal">
                <input type="hidden" name="save_webhooks" value="1" />
                <div class="form-group">
                    <label class="col-sm-4 control-label">Slack webhook URL</label>
                    <div class="col-sm-8">
                        <input type="url" class="form-control" name="slack_webhook_url" placeholder="https://hooks.slack.com/services/..."
                               value="<?php echo htmlspecialchars($webhook_settings['slack_webhook_url'], ENT_QUOTES, 'UTF-8'); ?>" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label">Generic webhook URL</label>
                    <div class="col-sm-8">
                        <input type="url" class="form-control" name="webhook_url" placeholder="https://..."
                               value="<?php echo htmlspecialchars($webhook_settings['webhook_url'], ENT_QUOTES, 'UTF-8'); ?>" />
                        <p class="help-block">Receives JSON: <code>event</code>, <code>title</code>, <code>payload</code>, <code>text</code>, <code>timestamp</code>.</p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label">Notify on</label>
                    <div class="col-sm-8">
                        <?php
                        $enabled = isset($webhook_settings['webhook_enabled_events']) ? $webhook_settings['webhook_enabled_events'] : array();
                        foreach ($webhook_event_labels as $event_key => $event_label) {
                            $checked = in_array($event_key, $enabled, true) ? 'checked' : '';
                            ?>
                            <label style="display:block;margin-bottom:6px;">
                                <input type="checkbox" name="webhook_enabled_events[]" value="<?php echo htmlspecialchars($event_key, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $checked; ?> />
                                <?php echo htmlspecialchars($event_label, ENT_QUOTES, 'UTF-8'); ?>
                            </label>
                        <?php } ?>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-offset-4 col-sm-8">
                        <button type="submit" class="pa-btn pa-btn-primary">Save webhook settings</button>
                        <button type="button" class="pa-btn pa-btn-secondary" id="pa-test-webhook">Send test notification</button>
                        <span id="pa-webhook-test-result" class="pa-inline-result" aria-live="polite"></span>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
