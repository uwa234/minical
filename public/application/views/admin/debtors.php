<?php
$currency_symbol = isset($saas_currency_symbol) ? $saas_currency_symbol : saas_currency_symbol();
$format_amount = function ($amount) {
    return format_saas_price_amount($amount);
};
$counts = isset($debtor_counts) && is_array($debtor_counts) ? $debtor_counts : array('overdue' => 0, 'due_soon' => 0, 'all' => 0);
$filter = isset($filter) ? $filter : 'overdue';
$default_grace_days = isset($default_grace_days) ? (int) $default_grace_days : saas_paid_plan_grace_days();
$pricing_tiers = isset($pricing_tiers) && is_array($pricing_tiers) ? $pricing_tiers : array();
?>
<div class="pa-page-header">
    <h1>Subscription debtors</h1>
    <p>Properties whose trial or paid plan has expired, or payment is due soon and has not been received.</p>
</div>

<div class="pa-stats" style="margin-bottom: 24px;">
    <div class="pa-stat-card pa-stat-card--warning">
        <div class="pa-stat-label">Overdue</div>
        <div class="pa-stat-value"><?php echo (int) $counts['overdue']; ?></div>
    </div>
    <div class="pa-stat-card pa-stat-card--info">
        <div class="pa-stat-label">Due within 7 days</div>
        <div class="pa-stat-value"><?php echo (int) $counts['due_soon']; ?></div>
    </div>
    <div class="pa-stat-card pa-stat-card--accent">
        <div class="pa-stat-label">All requiring attention</div>
        <div class="pa-stat-value"><?php echo (int) $counts['all']; ?></div>
    </div>
</div>

<form method="get" action="<?php echo base_url('admin/debtors'); ?>" class="pa-filters" style="margin-bottom: 24px;">
    <label for="filter">Show</label>
    <select name="filter" id="filter" class="form-control input-sm">
        <option value="overdue" <?php echo $filter === 'overdue' ? 'selected' : ''; ?>>Overdue only</option>
        <option value="due_soon" <?php echo $filter === 'due_soon' ? 'selected' : ''; ?>>Due within 7 days</option>
        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Overdue + due soon</option>
    </select>
    <input type="text" name="search_query" class="form-control input-sm" placeholder="Search property or owner"
           value="<?php echo htmlspecialchars((string) $search_query, ENT_QUOTES, 'UTF-8'); ?>" />
    <label class="pa-filter-checkbox">
        <input type="checkbox" name="include_deleted" value="1"
            <?php echo $this->input->get('include_deleted') === '1' ? 'checked' : ''; ?> />
        Include soft-deleted
    </label>
    <button type="submit" class="pa-btn pa-btn-secondary pa-btn-sm">Apply</button>
</form>

<div class="pa-card">
    <div class="pa-card-header">
        <h2>
            <?php
            if ($filter === 'due_soon') {
                echo 'Payments due soon';
            } elseif ($filter === 'all') {
                echo 'All properties needing payment';
            } else {
                echo 'Overdue properties';
            }
            ?>
        </h2>
    </div>
    <div class="pa-card-body pa-table-wrap">
        <table class="pa-table">
            <thead>
                <tr>
                    <th>Property</th>
                    <th>Owner</th>
                    <th>Status</th>
                    <th>Reason</th>
                    <th>Due date</th>
                    <th>Days overdue</th>
                    <th>Plan</th>
                    <th>Est. amount due</th>
                    <th>Balance</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($debtors)) { ?>
                    <tr>
                        <td colspan="10" class="pa-empty" style="padding: 32px;">No properties match this filter</td>
                    </tr>
                <?php } else {
                    foreach ($debtors as $debtor) {
                        $owner_label = '—';
                        if (!empty($debtor['owner_email'])) {
                            $owner_label = htmlspecialchars($debtor['owner_email'], ENT_QUOTES, 'UTF-8');
                            if (!empty($debtor['owner_first_name']) || !empty($debtor['owner_last_name'])) {
                                $owner_label = htmlspecialchars(trim($debtor['owner_first_name'] . ' ' . $debtor['owner_last_name']), ENT_QUOTES, 'UTF-8')
                                    . ' &lt;' . htmlspecialchars($debtor['owner_email'], ENT_QUOTES, 'UTF-8') . '&gt;';
                            }
                        }
                        $state = isset($debtor['subscription_state']) ? (string) $debtor['subscription_state'] : '';
                        $days_overdue = isset($debtor['days_overdue']) ? (int) $debtor['days_overdue'] : 0;
                        $due_date = !empty($debtor['due_date']) ? (string) $debtor['due_date'] : '—';
                        $amount_due = isset($debtor['amount_due']) ? (float) $debtor['amount_due'] : 0;
                        $tier_id = 0;
                        if (!empty($debtor['meta_data'])) {
                            $decoded = json_decode($debtor['meta_data'], true);
                            if (is_array($decoded) && !empty($decoded['tier_id'])) {
                                $tier_id = (int) $decoded['tier_id'];
                            }
                        }
                        if ($tier_id < 1) {
                            foreach ($pricing_tiers as $tier) {
                                if (isset($debtor['pricing_tier_name']) && (string) $tier['name'] === (string) $debtor['pricing_tier_name']) {
                                    $tier_id = (int) $tier['id'];
                                    break;
                                }
                            }
                        }
                        ?>
                        <tr>
                            <td>
                                <a href="<?php echo base_url('admin/property_detail/' . (int) $debtor['company_id']); ?>">
                                    <strong><?php echo htmlspecialchars((string) $debtor['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                </a>
                                <span style="display: block; font-size: 12px; color: var(--pa-muted, #64748b);">ID <?php echo (int) $debtor['company_id']; ?></span>
                                <?php if (!empty($debtor['is_deleted'])) { ?>
                                    <span class="pa-badge pa-badge-muted">Deleted</span>
                                <?php } ?>
                            </td>
                            <td><?php echo $owner_label; ?></td>
                            <td>
                                <span class="pa-badge pa-badge-<?php echo htmlspecialchars($state, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $state)), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars((string) $debtor['debtor_reason_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($due_date, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <?php if ($days_overdue > 0) { ?>
                                    <strong style="color: #b45309;"><?php echo $days_overdue; ?></strong>
                                <?php } else { ?>
                                    <span style="color: var(--pa-muted, #64748b);">—</span>
                                <?php } ?>
                            </td>
                            <td><?php echo htmlspecialchars(isset($debtor['pricing_tier_name']) ? (string) $debtor['pricing_tier_name'] : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <?php
                                if ($amount_due > 0) {
                                    echo htmlspecialchars($currency_symbol, ENT_QUOTES, 'UTF-8') . $format_amount($amount_due);
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                $balance = isset($debtor['balance']) ? (float) $debtor['balance'] : 0;
                                if ($balance > 0) {
                                    echo '<span style="color: #b45309;">' . htmlspecialchars($currency_symbol, ENT_QUOTES, 'UTF-8') . $format_amount($balance) . '</span>';
                                } else {
                                    echo htmlspecialchars($currency_symbol, ENT_QUOTES, 'UTF-8') . '0';
                                }
                                ?>
                            </td>
                            <td class="pa-actions-cell">
                                <button type="button"
                                        class="pa-btn pa-btn-primary pa-btn-sm debtor-record-payment"
                                        data-company-id="<?php echo (int) $debtor['company_id']; ?>"
                                        data-property-name="<?php echo htmlspecialchars((string) $debtor['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-amount="<?php echo htmlspecialchars((string) $amount_due, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-tier-id="<?php echo $tier_id; ?>">
                                    Record payment
                                </button>
                                <button type="button"
                                        class="pa-btn pa-btn-secondary pa-btn-sm debtor-extend-grace"
                                        data-company-id="<?php echo (int) $debtor['company_id']; ?>"
                                        data-property-name="<?php echo htmlspecialchars((string) $debtor['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-grace-days="<?php echo $default_grace_days; ?>">
                                    Extend grace
                                </button>
                                <button type="button"
                                        class="pa-btn pa-btn-secondary pa-btn-sm debtor-send-reminder"
                                        data-company-id="<?php echo (int) $debtor['company_id']; ?>"
                                        data-property-name="<?php echo htmlspecialchars((string) $debtor['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                    Send reminder
                                </button>
                            </td>
                        </tr>
                    <?php }
                } ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="debtor-record-payment-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">Record payment — <span id="debtor-payment-property-name"></span></h4>
            </div>
            <div class="modal-body form-horizontal">
                <input type="hidden" id="debtor-payment-company-id" value="" />
                <div class="form-group">
                    <label class="col-sm-4 control-label" for="debtor-payment-amount">Amount (<?php echo htmlspecialchars($currency_symbol, ENT_QUOTES, 'UTF-8'); ?>)</label>
                    <div class="col-sm-8">
                        <input type="number" min="0.01" step="0.01" class="form-control" id="debtor-payment-amount" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label" for="debtor-payment-period">Renewal period</label>
                    <div class="col-sm-8">
                        <select class="form-control" id="debtor-payment-period">
                            <option value="1 month">1 month</option>
                            <option value="1 year">1 year</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label" for="debtor-payment-date">Payment date</label>
                    <div class="col-sm-8">
                        <input type="date" class="form-control" id="debtor-payment-date"
                               value="<?php echo htmlspecialchars(gmdate('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label" for="debtor-payment-tier">Pricing tier</label>
                    <div class="col-sm-8">
                        <select class="form-control" id="debtor-payment-tier">
                            <?php foreach ($pricing_tiers as $tier) { ?>
                                <option value="<?php echo (int) $tier['id']; ?>">
                                    <?php echo htmlspecialchars((string) $tier['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label" for="debtor-payment-notes">Notes</label>
                    <div class="col-sm-8">
                        <textarea class="form-control" id="debtor-payment-notes" rows="2"
                                  placeholder="Bank transfer ref, invoice #, etc."></textarea>
                    </div>
                </div>
                <p class="text-muted" style="margin: 0;">
                    Records charge and payment, sets subscription to <strong>active</strong>, and extends plan expiry automatically.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="debtor-payment-submit">Record payment</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
