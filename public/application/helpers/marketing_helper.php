<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Shared layout data for public marketing-style pages (homepage, legal, etc.).
 */
function marketing_page_layout($seo)
{
    return array(
        'seo' => $seo,
        'website_logo_url' => platform_website_logo_url(),
        'css_files' => array(base_url() . auto_version('css/marketing/home.css')),
        'js_files' => array(base_url() . auto_version('js/marketing/home.js')),
        'marketing_home_url' => base_url(),
    );
}

/**
 * Safe subset of view variables for marketing header/footer partials.
 * Do not pass get_defined_vars() directly — it includes $this and breaks nested views.
 */
function marketing_layout_view_vars($source = array())
{
    $keys = array('seo', 'website_logo_url', 'css_files', 'js_files', 'marketing_home_url');
    $out = array();
    foreach ($keys as $key) {
        if (array_key_exists($key, $source)) {
            $out[$key] = $source[$key];
        }
    }
    return $out;
}

/**
 * Core PMS capabilities shown on the public marketing homepage (grouped).
 */
function marketing_platform_feature_groups()
{
    return array(
        array(
            'id' => 'operations',
            'title' => 'Front desk & operations',
            'summary' => 'Run daily hotel operations from one calendar and folio workspace.',
            'features' => array(
                array('icon' => '&#128197;', 'title' => 'Reservation calendar', 'description' => 'Drag-and-drop calendar for arrivals, departures, room moves, and in-house guests.'),
                array('icon' => '&#127769;', 'title' => 'Night audit', 'description' => 'Automated selling-date rollover, stay charges, scheduled check-outs, and undo support.'),
                array('icon' => '&#129529;', 'title' => 'Housekeeping', 'description' => 'Room status, housekeeping notes, and operational visibility for your team.'),
                array('icon' => '&#128101;', 'title' => 'Groups & blocks', 'description' => 'Manage group bookings, blocks, and linked reservations from one place.'),
                array('icon' => '&#128451;', 'title' => 'Room inventory', 'description' => 'Room types, floors, locations, amenities, and live availability control.'),
            ),
        ),
        array(
            'id' => 'guests',
            'title' => 'Guests & CRM',
            'summary' => 'Know every guest and keep folios accurate.',
            'features' => array(
                array('icon' => '&#128100;', 'title' => 'Guest profiles & folios', 'description' => 'Customer records, stay history, balances, and notes across every booking.'),
                array('icon' => '&#127991;', 'title' => 'Booking discounts', 'description' => 'Apply percentage or fixed discounts to stays with flexible scope rules.'),
                array('icon' => '&#128221;', 'title' => 'Custom fields', 'description' => 'Capture the guest and booking data your property needs.'),
            ),
        ),
        array(
            'id' => 'distribution',
            'title' => 'Distribution & direct revenue',
            'summary' => 'Sync OTAs and grow commission-free bookings.',
            'features' => array(
                array('icon' => '&#128225;', 'title' => 'Channel manager', 'description' => 'Sync rates and availability with OTAs — fewer overbookings and manual updates.'),
                array('icon' => '&#127760;', 'title' => 'Online booking engine', 'description' => 'Embeddable direct booking widget for your website to capture more direct revenue.'),
                array('icon' => '&#128279;', 'title' => 'Booking sources', 'description' => 'Track performance by OTA, walk-in, phone, corporate, and custom sources.'),
                array('icon' => '&#128200;', 'title' => 'Rate plans & extras', 'description' => 'Flexible rate plans, packages, and add-on charges for upsells.'),
            ),
        ),
        array(
            'id' => 'finance',
            'title' => 'Payments & accounting',
            'summary' => 'Collect payments, invoice guests, and close the books.',
            'features' => array(
                array('icon' => '&#128179;', 'title' => 'Payments & gateways', 'description' => 'Deposits, card tokenization, Paystack checkout, and integrated payment gateways.'),
                array('icon' => '&#128196;', 'title' => 'Invoicing & PDF', 'description' => 'Professional guest invoices with email delivery and printable PDF export.'),
                array('icon' => '&#128176;', 'title' => 'Charges & taxes', 'description' => 'Charge types, tax rules, advance deposits, and payment reconciliation.'),
                array('icon' => '&#128209;', 'title' => 'Accounting ledger', 'description' => 'Ledger, statements, and financial summaries tied to reservations.'),
            ),
        ),
        array(
            'id' => 'insights',
            'title' => 'Revenue & reporting',
            'summary' => 'Understand performance and optimize pricing.',
            'features' => array(
                array('icon' => '&#128200;', 'title' => 'Revenue management', 'description' => 'Forecast demand, manage rates, and optimize revenue from built-in tools.'),
                array('icon' => '&#128202;', 'title' => 'Operational reports', 'description' => 'Occupancy, sales, reservations, market segment, geographical, and staff reports.'),
                array('icon' => '&#128188;', 'title' => 'Owner dashboard', 'description' => 'Portfolio-level visibility for property owners and multi-site operators.'),
            ),
        ),
        array(
            'id' => 'platform',
            'title' => 'Cloud platform',
            'summary' => 'Secure, multi-property SaaS built for growing hotel brands.',
            'features' => array(
                array('icon' => '&#127970;', 'title' => 'Multi-property', 'description' => 'Switch between properties and manage a portfolio from one login.'),
                array('icon' => '&#128274;', 'title' => 'Roles & permissions', 'description' => 'Front desk, housekeeping, management, and custom user access controls.'),
                array('icon' => '&#128241;', 'title' => 'Offline mode', 'description' => 'Keep working when connectivity drops — changes sync when you are back online.'),
                array('icon' => '&#128640;', 'title' => 'API & extensions', 'description' => 'Extend Veurion with integrations, marketplace add-ons, and API access.'),
            ),
        ),
    );
}

/**
 * Hosted SaaS offering highlights for the marketing homepage.
 */
function marketing_saas_highlights()
{
    return array(
        array(
            'icon' => '&#127873;',
            'title' => 'Free trial',
            'description' => 'Start with full platform access — no credit card required during trial.',
        ),
        array(
            'icon' => '&#128176;',
            'title' => 'Room-based pricing',
            'description' => 'Transparent monthly plans scaled to your room count (Starter through Enterprise).',
        ),
        array(
            'icon' => '&#128179;',
            'title' => 'Paystack billing',
            'description' => 'Subscribe and renew online with secure Paystack checkout and receipts.',
        ),
        array(
            'icon' => '&#128200;',
            'title' => 'Plan-matched features',
            'description' => 'Channel manager, payments, revenue tools, analytics, and support tiers by plan.',
        ),
        array(
            'icon' => '&#127760;',
            'title' => 'Cloud-hosted PMS',
            'description' => 'Always up to date — no servers to maintain, access from any modern browser.',
        ),
        array(
            'icon' => '&#128101;',
            'title' => 'Team-ready',
            'description' => 'Invite staff, manage subscriptions per property, and scale from inn to portfolio.',
        ),
    );
}

/**
 * Normalize installed extension modules for marketing display.
 */
function marketing_extensions_from_modules($all_active_modules)
{
    $extensions = array();
    if (!is_array($all_active_modules)) {
        return $extensions;
    }

    $category_labels = array(
        'channel_manager' => 'Channel manager',
        'online_booking_engine' => 'Direct bookings',
        'integrations' => 'Integrations',
    );

    foreach ($all_active_modules as $module) {
        if (empty($module['name'])) {
            continue;
        }
        $categories = array();
        if (!empty($module['categories']) && is_array($module['categories'])) {
            foreach ($module['categories'] as $cat) {
                $categories[] = isset($category_labels[$cat]) ? $category_labels[$cat] : ucwords(str_replace('_', ' ', $cat));
            }
        }
        $extensions[] = array(
            'name' => $module['name'],
            'description' => isset($module['description']) ? $module['description'] : '',
            'categories' => array_values(array_unique($categories)),
            'marketplace_link' => isset($module['marketplace_product_link']) ? $module['marketplace_product_link'] : '',
        );
    }

    return $extensions;
}
