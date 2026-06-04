<?php
if (empty($seo) || !is_array($seo)) {
    return;
}
$title = isset($seo['title']) ? $seo['title'] : '';
$description = isset($seo['description']) ? $seo['description'] : '';
$canonical = isset($seo['canonical']) ? $seo['canonical'] : '';
$robots = isset($seo['robots']) ? $seo['robots'] : 'index, follow';
$keywords = isset($seo['keywords']) ? $seo['keywords'] : '';
$og_type = isset($seo['og_type']) ? $seo['og_type'] : 'website';
$og_image = isset($seo['og_image']) ? $seo['og_image'] : '';
$og_title = isset($seo['og_title']) ? $seo['og_title'] : $title;
$og_description = isset($seo['og_description']) ? $seo['og_description'] : $description;
$twitter_card = isset($seo['twitter_card']) ? $seo['twitter_card'] : 'summary_large_image';
$twitter_site = isset($seo['twitter_site']) ? $seo['twitter_site'] : '';
$twitter_title = isset($seo['twitter_title']) ? $seo['twitter_title'] : $title;
$twitter_description = isset($seo['twitter_description']) ? $seo['twitter_description'] : $description;
$json_ld = isset($seo['json_ld']) ? $seo['json_ld'] : array();
?>
<?php if ($description !== '') { ?>
<meta name="description" content="<?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?>">
<?php } ?>
<?php if ($keywords !== '') { ?>
<meta name="keywords" content="<?php echo htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8'); ?>">
<?php } ?>
<meta name="robots" content="<?php echo htmlspecialchars($robots, ENT_QUOTES, 'UTF-8'); ?>">
<?php if ($canonical !== '') { ?>
<link rel="canonical" href="<?php echo htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8'); ?>">
<?php } ?>
<meta property="og:locale" content="en_US">
<meta property="og:site_name" content="<?php echo htmlspecialchars(seo_brand_name(), ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:type" content="<?php echo htmlspecialchars($og_type, ENT_QUOTES, 'UTF-8'); ?>">
<?php if ($og_title !== '') { ?>
<meta property="og:title" content="<?php echo htmlspecialchars($og_title, ENT_QUOTES, 'UTF-8'); ?>">
<?php } ?>
<?php if ($og_description !== '') { ?>
<meta property="og:description" content="<?php echo htmlspecialchars($og_description, ENT_QUOTES, 'UTF-8'); ?>">
<?php } ?>
<?php if ($canonical !== '') { ?>
<meta property="og:url" content="<?php echo htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8'); ?>">
<?php } ?>
<?php if ($og_image !== '') { ?>
<meta property="og:image" content="<?php echo htmlspecialchars($og_image, ENT_QUOTES, 'UTF-8'); ?>">
<?php } ?>
<meta name="twitter:card" content="<?php echo htmlspecialchars($twitter_card, ENT_QUOTES, 'UTF-8'); ?>">
<?php if ($twitter_site !== '') { ?>
<meta name="twitter:site" content="<?php echo htmlspecialchars($twitter_site, ENT_QUOTES, 'UTF-8'); ?>">
<?php } ?>
<?php if ($twitter_title !== '') { ?>
<meta name="twitter:title" content="<?php echo htmlspecialchars($twitter_title, ENT_QUOTES, 'UTF-8'); ?>">
<?php } ?>
<?php if ($twitter_description !== '') { ?>
<meta name="twitter:description" content="<?php echo htmlspecialchars($twitter_description, ENT_QUOTES, 'UTF-8'); ?>">
<?php } ?>
<?php if ($og_image !== '') { ?>
<meta name="twitter:image" content="<?php echo htmlspecialchars($og_image, ENT_QUOTES, 'UTF-8'); ?>">
<?php } ?>
<?php echo seo_encode_json_ld($json_ld); ?>
