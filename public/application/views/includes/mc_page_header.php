<?php
$title = isset($title) ? $title : '';
$subtitle = isset($subtitle) ? $subtitle : '';
$icon = isset($icon) ? $icon : 'fa-file-text-o';
$extra_html = isset($extra_html) ? $extra_html : '';
$actions_html = isset($actions_html) ? $actions_html : '';
?>
<header class="mc-page-header<?php echo !empty($hidden_print) ? ' hidden-print' : ''; ?>">
    <div class="mc-page-header-inner">
        <div class="mc-page-header-text">
            <div class="mc-page-icon" aria-hidden="true">
                <i class="fa <?php echo htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'); ?>"></i>
            </div>
            <div>
                <h1 class="mc-page-title"><?php echo $title; ?></h1>
                <?php if ($subtitle !== '') : ?>
                    <p class="mc-page-subtitle"><?php echo $subtitle; ?></p>
                <?php endif; ?>
                <?php if ($extra_html !== '') : ?>
                    <div class="mc-page-extra"><?php echo $extra_html; ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($actions_html !== '') : ?>
            <div class="mc-page-header-actions"><?php echo $actions_html; ?></div>
        <?php endif; ?>
    </div>
</header>
