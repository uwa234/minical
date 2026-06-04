<?php
if (empty($seo) || !is_array($seo)) {
    $seo = seo_build_page(array(
        'title' => seo_brand_name() . ' — Hotel property management software',
        'canonical' => seo_absolute_url(),
    ));
}
$css_files = isset($css_files) ? $css_files : array();
$js_files = isset($js_files) ? $js_files : array();
$marketing_home_url = isset($marketing_home_url) ? $marketing_home_url : base_url();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($seo['title'], ENT_QUOTES, 'UTF-8'); ?></title>
    <?php $this->load->view('includes/seo_head', array('seo' => $seo)); ?>
    <?php echo platform_branding_favicon_link_html(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <?php foreach ($css_files as $css) { ?>
        <link rel="stylesheet" href="<?php echo $css; ?>">
    <?php } ?>
</head>
<body class="mc-marketing">
<a class="mc-skip" href="#main">Skip to content</a>

<header class="mc-nav" id="mc-nav">
    <div class="mc-nav-inner">
        <a class="mc-logo" href="<?php echo $marketing_home_url; ?>">
            <?php if (!empty($website_logo_url)) { ?>
                <img class="mc-logo-img" src="<?php echo htmlspecialchars($website_logo_url, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars(seo_brand_name(), ENT_QUOTES, 'UTF-8'); ?>" />
            <?php } else { ?>
                <span class="mc-logo-mark" aria-hidden="true"></span>
                <?php echo htmlspecialchars(seo_brand_name(), ENT_QUOTES, 'UTF-8'); ?>
            <?php } ?>
        </a>
        <button type="button" class="mc-nav-toggle" id="mc-nav-toggle" aria-label="Open menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <nav class="mc-nav-links" id="mc-nav-links">
            <a href="<?php echo $marketing_home_url; ?>#features">Features</a>
            <a href="<?php echo $marketing_home_url; ?>#saas">SaaS</a>
            <a href="<?php echo $marketing_home_url; ?>#extensions">Extensions</a>
            <a href="<?php echo $marketing_home_url; ?>#benefits">Benefits</a>
            <a href="<?php echo $marketing_home_url; ?>#pricing">Pricing</a>
            <a href="<?php echo base_url('auth/login'); ?>" class="mc-nav-ghost">Sign in</a>
            <a href="<?php echo base_url('auth/register'); ?>" class="mc-btn mc-btn-sm mc-btn-solid">Start free trial</a>
        </nav>
    </div>
</header>
