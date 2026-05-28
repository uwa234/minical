<?php
$current = $this->uri->segment(2) ?: 'dashboard';
$nav_items = array(
    'dashboard' => array('label' => 'Dashboard', 'icon' => 'fa-dashboard', 'url' => 'admin/dashboard'),
    'property_list' => array('label' => 'Properties', 'icon' => 'fa-building', 'url' => 'admin/property_list'),
    'trial_registrations' => array('label' => 'Trial sign-ups', 'icon' => 'fa-envelope', 'url' => 'admin/trial_registrations'),
    'revenue' => array('label' => 'Revenue', 'icon' => 'fa-line-chart', 'url' => 'admin/revenue'),
    'settings' => array('label' => 'Settings', 'icon' => 'fa-cog', 'url' => 'admin/settings'),
);
$page_titles = array(
    'dashboard' => 'Dashboard',
    'property_list' => 'Properties',
    'trial_registrations' => 'Trial sign-ups',
    'revenue' => 'Revenue',
    'settings' => 'Settings',
);
$pa_page_title = isset($page_titles[$current]) ? $page_titles[$current] : 'Platform Admin';
?>
<aside class="pa-sidebar" id="pa-sidebar" aria-label="Platform admin navigation">
    <a class="pa-sidebar-brand" href="<?php echo base_url('admin/dashboard'); ?>">
        <span class="pa-brand-mark" aria-hidden="true"></span>
        <span>
            <span class="pa-brand-text">Veurion</span>
            <span class="pa-brand-sub">Platform Admin</span>
        </span>
    </a>

    <nav class="pa-nav">
        <div class="pa-nav-section">Overview</div>
        <?php foreach ($nav_items as $key => $item) {
            $active = ($current === $key) ? ' is-active' : '';
            ?>
            <a class="pa-nav-link<?php echo $active; ?>" href="<?php echo base_url($item['url']); ?>">
                <i class="fa <?php echo $item['icon']; ?>" aria-hidden="true"></i>
                <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php } ?>
    </nav>

    <div class="pa-sidebar-footer">
        <a class="pa-nav-link pa-nav-logout" href="<?php echo base_url('auth/logout'); ?>">
            <i class="fa fa-sign-out" aria-hidden="true"></i>
            Sign out
        </a>
    </div>
</aside>

<div class="pa-sidebar-overlay" id="pa-sidebar-overlay" aria-hidden="true"></div>

<div class="pa-main">
    <header class="pa-topbar">
        <button type="button" class="pa-topbar-toggle" id="pa-sidebar-toggle" aria-label="Open menu">
            <span></span><span></span><span></span>
        </button>
        <h1 class="pa-topbar-title"><?php echo htmlspecialchars($pa_page_title, ENT_QUOTES, 'UTF-8'); ?></h1>
        <form class="pa-topbar-search" role="search" method="GET" action="<?php echo base_url('admin/property_list'); ?>">
            <div class="input-group">
                <input class="form-control" type="text" name="search_query" placeholder="Search properties…"
                       value="<?php echo htmlspecialchars($this->input->get('search_query'), ENT_QUOTES, 'UTF-8'); ?>" />
                <span class="input-group-btn">
                    <button class="btn" type="submit"><i class="fa fa-search" aria-hidden="true"></i></button>
                </span>
            </div>
        </form>
    </header>
    <main class="pa-content admin-main">
