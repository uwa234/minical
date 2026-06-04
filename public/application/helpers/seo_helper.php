<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * SEO helpers for public marketing and auth pages (hosted SaaS).
 */

function seo_brand_name()
{
    $CI =& get_instance();
    return $CI->config->item('branding_name') ?: 'Veurion';
}

/**
 * Canonical site origin (no trailing slash), e.g. https://app.travelhub247.com/public
 */
function seo_canonical_origin()
{
    $explicit = getenv('SEO_CANONICAL_URL');
    $url = $explicit;
    if (!$url) {
        $url = getenv('PROJECT_URL');
    }
    if (!$url) {
        $CI =& get_instance();
        $url = $CI->config->item('base_url');
    }
    $url = rtrim(trim((string) $url), '/');
    if ($url === '') {
        return '';
    }
    if (!$explicit) {
        $url = preg_replace('#/public$#', '', $url);
    }
    return $url;
}

function seo_absolute_url($path = '')
{
    $origin = seo_canonical_origin();
    if ($origin === '') {
        return ltrim($path, '/');
    }
    if ($path === '' || $path === '/') {
        return $origin . '/';
    }
    return $origin . '/' . ltrim($path, '/');
}

function seo_default_description()
{
    return 'Veurion is cloud hotel PMS software: reservations, night audit, housekeeping, channel manager, booking engine, payments, revenue management, reporting, and integrations — with SaaS plans from trial to enterprise.';
}

function seo_default_keywords()
{
    return 'hotel PMS, property management software, hotel reservations, channel manager, booking engine, hotel software';
}

function seo_og_image_url($override = '')
{
    if ($override !== '') {
        return $override;
    }
    $env = getenv('SEO_OG_IMAGE_URL');
    if ($env) {
        return $env;
    }
    $logo = platform_website_logo_url();
    if ($logo !== '') {
        if (strpos($logo, 'http') === 0) {
            return $logo;
        }
        return seo_absolute_url(ltrim($logo, '/'));
    }
    return seo_absolute_url('images/minical-logo.jpeg');
}

function seo_same_as_profiles()
{
    $raw = getenv('SEO_SAME_AS_URLS');
    if (!$raw) {
        return array();
    }
    $urls = array_filter(array_map('trim', explode(',', $raw)));
    return array_values($urls);
}

function seo_build_page($options = array())
{
    $brand = seo_brand_name();
    $defaults = array(
        'title' => $brand . ' — Hotel property management software',
        'description' => seo_default_description(),
        'keywords' => seo_default_keywords(),
        'canonical' => seo_absolute_url(),
        'robots' => 'index, follow',
        'og_type' => 'website',
        'og_image' => seo_og_image_url(),
        'twitter_card' => 'summary_large_image',
        'json_ld' => array(),
    );
    $page = array_merge($defaults, $options);
    if (empty($page['og_title'])) {
        $page['og_title'] = $page['title'];
    }
    if (empty($page['og_description'])) {
        $page['og_description'] = $page['description'];
    }
    if (empty($page['twitter_title'])) {
        $page['twitter_title'] = $page['title'];
    }
    if (empty($page['twitter_description'])) {
        $page['twitter_description'] = $page['description'];
    }
    $twitter = getenv('SEO_TWITTER_SITE');
    if ($twitter) {
        $page['twitter_site'] = $twitter;
    }
    return $page;
}

function seo_json_ld_organization($extra = array())
{
    $brand = seo_brand_name();
    $data = array(
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $brand,
        'url' => seo_absolute_url(),
        'logo' => seo_og_image_url(),
    );
    $same_as = seo_same_as_profiles();
    if ($same_as) {
        $data['sameAs'] = $same_as;
    }
    $phone = getenv('SEO_ORG_PHONE');
    if ($phone) {
        $data['telephone'] = $phone;
    }
    $email = getenv('SEO_ORG_EMAIL');
    if ($email) {
        $data['email'] = $email;
    }
    $address = getenv('SEO_ORG_ADDRESS');
    if ($address) {
        $data['address'] = array(
            '@type' => 'PostalAddress',
            'streetAddress' => $address,
        );
    }
    return array_merge($data, $extra);
}

function seo_json_ld_website()
{
    return array(
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => seo_brand_name(),
        'url' => seo_absolute_url(),
        'description' => seo_default_description(),
    );
}

function seo_json_ld_software_application()
{
    return array(
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => seo_brand_name(),
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web',
        'url' => seo_absolute_url(),
        'description' => seo_default_description(),
        'offers' => array(
            '@type' => 'Offer',
            'price' => '0',
            'priceCurrency' => 'USD',
            'description' => 'Free trial available',
        ),
    );
}

function seo_json_ld_webpage($title, $description, $url)
{
    return array(
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => $title,
        'description' => $description,
        'url' => $url,
        'isPartOf' => array(
            '@type' => 'WebSite',
            'name' => seo_brand_name(),
            'url' => seo_absolute_url(),
        ),
    );
}

function seo_encode_json_ld($blocks)
{
    if (empty($blocks)) {
        return '';
    }
    if (isset($blocks['@context'])) {
        $blocks = array($blocks);
    }
    $out = '';
    foreach ($blocks as $block) {
        if (empty($block)) {
            continue;
        }
        $out .= '<script type="application/ld+json">' . "\n"
            . json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            . "\n</script>\n";
    }
    return $out;
}

function seo_sitemap_entries()
{
    if (!is_hosted_prod_service()) {
        return array();
    }
    $now = date('c');
    return array(
        array('loc' => seo_absolute_url(), 'priority' => '1.0', 'changefreq' => 'weekly', 'lastmod' => $now),
        array('loc' => seo_absolute_url('auth/register'), 'priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => $now),
        array('loc' => seo_absolute_url('auth/login'), 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => $now),
        array('loc' => seo_absolute_url('auth/show_terms_of_service'), 'priority' => '0.3', 'changefreq' => 'yearly', 'lastmod' => $now),
        array('loc' => seo_absolute_url('auth/show_privacy_policy'), 'priority' => '0.3', 'changefreq' => 'yearly', 'lastmod' => $now),
    );
}

function seo_auth_page($page)
{
    $brand = seo_brand_name();
    switch ($page) {
        case 'login':
            $title = $brand . ' — Hotel staff sign in';
            $description = 'Sign in to ' . $brand . ' to manage reservations, housekeeping, night audit, reporting, and guest communication from one workspace.';
            $canonical = seo_absolute_url('auth/login');
            break;
        case 'register':
            $title = $brand . ' — Start your free trial';
            $description = 'Create your free ' . $brand . ' trial. Cloud hotel PMS with reservations, channel manager, booking engine, and payments.';
            $canonical = seo_absolute_url('auth/register');
            break;
        case 'terms':
            $title = $brand . ' — Terms of Service';
            $description = 'Terms of Service for using ' . $brand . ' hotel property management software and related web services.';
            $canonical = seo_absolute_url('auth/show_terms_of_service');
            break;
        case 'privacy':
            $title = $brand . ' — Privacy Policy';
            $description = 'How ' . $brand . ' collects, uses, and protects personal information when you use our hotel management platform.';
            $canonical = seo_absolute_url('auth/show_privacy_policy');
            break;
        default:
            return array();
    }
    return seo_build_page(array(
        'title' => $title,
        'description' => $description,
        'canonical' => $canonical,
        'robots' => 'index, follow',
        'json_ld' => seo_json_ld_webpage($title, $description, $canonical),
    ));
}

function seo_robots_txt_body()
{
    $sitemap = seo_absolute_url('sitemap.xml');
    $lines = array(
        'User-agent: *',
        'Allow: /',
        'Disallow: /admin/',
        'Disallow: /admin',
        'Disallow: /booking/',
        'Disallow: /cron/',
        'Disallow: /api/',
        '',
        'Sitemap: ' . $sitemap,
    );
    return implode("\n", $lines) . "\n";
}
