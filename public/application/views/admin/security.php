<?php if ($this->session->flashdata('admin_message')) { ?>
    <div class="pa-alert pa-alert-success"><i class="fa fa-check-circle" aria-hidden="true"></i><?php echo htmlspecialchars($this->session->flashdata('admin_message'), ENT_QUOTES, 'UTF-8'); ?></div>
<?php } ?>
<?php if ($this->session->flashdata('admin_error')) { ?>
    <div class="pa-alert pa-alert-danger"><i class="fa fa-exclamation-circle" aria-hidden="true"></i><?php echo htmlspecialchars($this->session->flashdata('admin_error'), ENT_QUOTES, 'UTF-8'); ?></div>
<?php } ?>

<div class="pa-page-header">
    <h1>Security &amp; governance</h1>
    <p>Audit platform admin actions, manage who has access, and protect accounts with two-factor authentication.</p>
</div>

<div class="pa-comm-nav">
    <a href="#audit-log" class="pa-comm-nav-link">Audit log</a>
    <a href="#admins" class="pa-comm-nav-link">Platform admins</a>
    <a href="#two-factor" class="pa-comm-nav-link">Two-factor auth</a>
</div>

<section id="audit-log" class="pa-comm-section">
    <div class="pa-card">
        <div class="pa-card-header"><h2>Admin audit log</h2></div>
        <div class="pa-card-body">
            <p class="pa-meta-note">Records who changed subscriptions, deleted or restored properties, updated settings, and other platform admin actions.</p>
            <form method="get" action="<?php echo base_url('admin/security'); ?>" class="pa-filters" style="margin-bottom: 16px;">
                <label for="audit_action">Action</label>
                <select name="audit_action" id="audit_action" class="form-control input-sm">
                    <option value="">All actions</option>
                    <?php foreach ($audit_action_labels as $action_key => $action_label) {
                        $sel = (isset($audit_filters['action']) && $audit_filters['action'] === $action_key) ? 'selected' : '';
                        ?>
                        <option value="<?php echo htmlspecialchars($action_key, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $sel; ?>>
                            <?php echo htmlspecialchars($action_label, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php } ?>
                </select>
                <input type="text" name="audit_search" class="form-control input-sm" placeholder="Search actor, target, details"
                       value="<?php echo htmlspecialchars(isset($audit_filters['search']) ? $audit_filters['search'] : '', ENT_QUOTES, 'UTF-8'); ?>" />
                <button type="submit" class="pa-btn pa-btn-secondary pa-btn-sm">Filter</button>
            </form>

            <?php if (empty($audit_entries)) { ?>
                <div class="pa-empty"><i class="fa fa-history" aria-hidden="true"></i> No audit entries yet.</div>
            <?php } else { ?>
                <div class="pa-table-wrap">
                    <table class="pa-table">
                        <thead>
                            <tr>
                                <th>When</th>
                                <th>Actor</th>
                                <th>Action</th>
                                <th>Target</th>
                                <th>Details</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($audit_entries as $entry) {
                                $action_label = platform_audit_action_label($entry['action']);
                                $target = '—';
                                if (!empty($entry['target_label'])) {
                                    $target = $entry['target_label'];
                                } elseif (!empty($entry['target_type']) && !empty($entry['target_id'])) {
                                    $target = ucfirst($entry['target_type']) . ' #' . (int) $entry['target_id'];
                                }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($entry['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(isset($entry['actor_email']) ? $entry['actor_email'] : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($action_label, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($target, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(isset($entry['details']) ? $entry['details'] : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(isset($entry['ip_address']) ? $entry['ip_address'] : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <?php
                $total_pages = max(1, (int) ceil($audit_total / $audit_per_page));
                if ($total_pages > 1) {
                    $query = array();
                    if (!empty($audit_filters['action'])) {
                        $query['audit_action'] = $audit_filters['action'];
                    }
                    if (!empty($audit_filters['search'])) {
                        $query['audit_search'] = $audit_filters['search'];
                    }
                    ?>
                    <div class="pa-pagination" style="margin-top: 16px;">
                        <?php for ($p = 1; $p <= $total_pages; $p++) {
                            $query['audit_page'] = $p;
                            $url = base_url('admin/security?' . http_build_query($query) . '#audit-log');
                            $active = ($p === (int) $audit_page) ? ' pa-btn-primary' : ' pa-btn-secondary';
                            ?>
                            <a class="pa-btn pa-btn-sm<?php echo $active; ?>" href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $p; ?></a>
                        <?php } ?>
                    </div>
                <?php } ?>
            <?php } ?>
        </div>
    </div>
</section>

<section id="admins" class="pa-comm-section">
    <div class="pa-settings-grid">
        <div class="pa-card">
            <div class="pa-card-header"><h2>Add platform admin</h2></div>
            <div class="pa-card-body">
                <p class="pa-meta-note">Grant platform admin access by email. The user must already have a Veurion account with this email, or create one before signing in.</p>
                <form method="post" action="<?php echo base_url('admin/security'); ?>" class="form-horizontal">
                    <input type="hidden" name="add_platform_admin" value="1" />
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Email</label>
                        <div class="col-sm-9">
                            <input type="email" class="form-control" name="admin_email" required placeholder="admin@company.com" />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-3 col-sm-9">
                            <button type="submit" class="pa-btn pa-btn-primary">Add admin</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="pa-card">
            <div class="pa-card-header"><h2>Active platform admins</h2></div>
            <div class="pa-card-body pa-table-wrap">
                <?php if (empty($platform_admins)) { ?>
                    <div class="pa-empty"><i class="fa fa-users" aria-hidden="true"></i> No platform admins configured.</div>
                <?php } else { ?>
                    <table class="pa-table">
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Role</th>
                                <th>2FA</th>
                                <th>Status</th>
                                <th>Added</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $current_email = strtolower((string) $this->session->userdata('email'));
                            foreach ($platform_admins as $admin) {
                                $is_current = strtolower((string) $admin['email']) === $current_email;
                                ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($admin['email'], ENT_QUOTES, 'UTF-8'); ?>
                                        <?php if ($is_current) { ?>
                                            <span class="pa-badge pa-badge-info">You</span>
                                        <?php } ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $admin['role'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php if (!empty($admin['totp_enabled'])) { ?>
                                            <span class="pa-badge pa-badge-success">Enabled</span>
                                        <?php } else { ?>
                                            <span class="pa-badge pa-badge-muted">Off</span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($admin['is_active'])) { ?>
                                            <span class="pa-badge pa-badge-success">Active</span>
                                        <?php } else { ?>
                                            <span class="pa-badge pa-badge-muted">Revoked</span>
                                        <?php } ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($admin['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php if (!empty($admin['is_active']) && !$is_current) { ?>
                                            <button type="button" class="pa-btn pa-btn-danger pa-btn-sm pa-revoke-admin-btn"
                                                    data-platform-admin-id="<?php echo (int) $admin['platform_admin_id']; ?>"
                                                    data-admin-email="<?php echo htmlspecialchars($admin['email'], ENT_QUOTES, 'UTF-8'); ?>">
                                                Revoke
                                            </button>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } ?>
            </div>
        </div>
    </div>
</section>

<section id="two-factor" class="pa-comm-section">
    <div class="pa-card">
        <div class="pa-card-header"><h2>Two-factor authentication (2FA)</h2></div>
        <div class="pa-card-body">
            <?php if (!empty($current_admin_2fa_enabled)) { ?>
                <p class="pa-meta-note">Two-factor authentication is enabled for your account. You will be asked for a code each time you sign in to Platform Admin.</p>
                <div class="form-inline" style="margin-top: 16px;">
                    <input type="text" class="form-control" id="pa-disable-2fa-code" maxlength="6" inputmode="numeric"
                           placeholder="Enter code to disable" style="max-width: 220px;" />
                    <button type="button" class="pa-btn pa-btn-danger" id="pa-disable-2fa-btn">Disable 2FA</button>
                </div>
            <?php } elseif ($totp_setup_secret) { ?>
                <p class="pa-meta-note">Scan this secret in Google Authenticator, Authy, or another TOTP app, then enter the 6-digit code to confirm.</p>
                <div class="pa-2fa-setup">
                    <p><strong>Manual entry key:</strong> <code id="pa-2fa-secret"><?php echo htmlspecialchars($totp_setup_secret, ENT_QUOTES, 'UTF-8'); ?></code></p>
                    <?php if ($totp_setup_uri) { ?>
                        <p class="text-muted" style="font-size: 12px; word-break: break-all;">URI: <?php echo htmlspecialchars($totp_setup_uri, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php } ?>
                    <div class="form-inline" style="margin-top: 12px;">
                        <input type="text" class="form-control" id="pa-confirm-2fa-code" maxlength="6" inputmode="numeric"
                               placeholder="6-digit code" style="max-width: 220px;" />
                        <button type="button" class="pa-btn pa-btn-primary" id="pa-confirm-2fa-btn">Confirm &amp; enable</button>
                    </div>
                </div>
            <?php } else { ?>
                <p class="pa-meta-note">Protect your platform admin account with a time-based one-time password (TOTP) from an authenticator app.</p>
                <button type="button" class="pa-btn pa-btn-primary" id="pa-start-2fa-btn">Set up two-factor authentication</button>
            <?php } ?>
        </div>
    </div>
</section>
