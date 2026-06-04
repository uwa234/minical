<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Shared metadata for Terms of Service and Privacy Policy pages.
 */
function legal_meta()
{
    $brand = seo_brand_name();
    $support = getenv('SEO_ORG_EMAIL');
    if (!$support) {
        $support = getenv('MAIL_REPLY_TO');
    }
    if (!$support) {
        $support = 'support@veurion.com';
    }
    $privacy = getenv('LEGAL_PRIVACY_EMAIL');
    if (!$privacy) {
        $privacy = 'privacy@veurion.com';
    }

    return array(
        'company' => 'Veurion Inc.',
        'brand' => $brand,
        'product' => $brand . ' hotel property management software',
        'support_email' => $support,
        'privacy_email' => $privacy,
        'phone' => getenv('SEO_ORG_PHONE') ?: '',
        'address' => getenv('SEO_ORG_ADDRESS') ?: 'PO Box 57347 Blue Mountain PO, Coquitlam, BC, Canada',
        'governing_law' => 'Province of Alberta, Canada',
        'courts' => 'Calgary, Alberta',
        'effective_date' => 'June 4, 2026',
        'terms_url' => base_url('auth/show_terms_of_service'),
        'privacy_url' => base_url('auth/show_privacy_policy'),
        'site_url' => seo_absolute_url() ?: base_url(),
    );
}

function legal_h($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}
