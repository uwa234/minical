<div class="offline-sync-page">
    <header class="offline-sync-header">
        <h1 class="offline-sync-title"><?php echo l('offline_sync_status_title', true); ?></h1>
        <p class="offline-sync-subtitle"><?php echo l('offline_sync_status_subtitle', true); ?></p>
    </header>

    <div class="offline-sync-toolbar">
        <span id="offline-sync-connection" class="offline-sync-badge offline-sync-badge--unknown">
            <?php echo l('offline_sync_checking', true); ?>
        </span>
        <button type="button" id="offline-sync-refresh" class="btn btn-default btn-sm">
            <i class="fa fa-refresh"></i> <?php echo l('offline_sync_refresh', true); ?>
        </button>
        <button type="button" id="offline-sync-run" class="btn btn-primary btn-sm">
            <i class="fa fa-cloud-upload"></i> <?php echo l('offline_sync_now', true); ?>
        </button>
    </div>

    <section class="offline-sync-panel">
        <h2><?php echo l('offline_sync_pending_heading', true); ?></h2>
        <p class="offline-sync-panel-hint"><?php echo l('offline_sync_pending_hint', true); ?></p>
        <p id="offline-sync-pending-empty" class="offline-sync-empty hidden">
            <?php echo l('offline_sync_pending_empty', true); ?>
        </p>
        <div class="table-responsive">
            <table class="table table-striped offline-sync-table" id="offline-sync-pending-table">
                <thead>
                    <tr>
                        <th><?php echo l('offline_sync_col_action', true); ?></th>
                        <th><?php echo l('offline_sync_col_details', true); ?></th>
                        <th><?php echo l('offline_sync_col_queued', true); ?></th>
                        <th><?php echo l('offline_sync_col_actions', true); ?></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </section>

    <section class="offline-sync-panel offline-sync-panel--conflicts">
        <h2><?php echo l('offline_sync_conflicts_heading', true); ?></h2>
        <p class="offline-sync-panel-hint"><?php echo l('offline_sync_conflicts_hint', true); ?></p>
        <p id="offline-sync-conflicts-empty" class="offline-sync-empty hidden">
            <?php echo l('offline_sync_conflicts_empty', true); ?>
        </p>
        <div class="table-responsive">
            <table class="table table-striped offline-sync-table" id="offline-sync-conflicts-table">
                <thead>
                    <tr>
                        <th><?php echo l('offline_sync_col_action', true); ?></th>
                        <th><?php echo l('offline_sync_col_error', true); ?></th>
                        <th><?php echo l('offline_sync_col_failed', true); ?></th>
                        <th><?php echo l('offline_sync_col_actions', true); ?></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </section>
</div>
