<?php
/*
 * Veurion page header.
 * Vars: $title, $subtitle (HTML-safe), $icon (fa-*), $eyebrow, $actions_html
 */
?>
<header class="vx-header">
    <div class="vx-header-main">
        <div class="vx-header-icon" aria-hidden="true"><i class="fa <?php echo htmlspecialchars(isset($icon) ? $icon : 'fa-star', ENT_QUOTES, 'UTF-8'); ?>"></i></div>
        <div>
            <?php if (!empty($eyebrow)) : ?><div class="vx-eyebrow"><?php echo $eyebrow; ?></div><?php endif; ?>
            <h1 class="vx-title"><?php echo isset($title) ? $title : ''; ?></h1>
            <?php if (!empty($subtitle)) : ?><p class="vx-subtitle"><?php echo $subtitle; ?></p><?php endif; ?>
        </div>
    </div>
    <?php if (!empty($actions_html)) : ?><div class="vx-header-actions"><?php echo $actions_html; ?></div><?php endif; ?>
</header>
