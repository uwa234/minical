<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Public SEO endpoints: sitemap.xml and robots.txt
 */
class Seo extends CI_Controller {

    function __construct()
    {
        parent::__construct();
        $this->load->helper('seo');
    }

    function sitemap()
    {
        $entries = seo_sitemap_entries();
        header('Content-Type: application/xml; charset=UTF-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($entries as $entry) {
            echo "  <url>\n";
            echo '    <loc>' . htmlspecialchars($entry['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
            if (!empty($entry['lastmod'])) {
                echo '    <lastmod>' . htmlspecialchars($entry['lastmod'], ENT_XML1, 'UTF-8') . "</lastmod>\n";
            }
            if (!empty($entry['changefreq'])) {
                echo '    <changefreq>' . htmlspecialchars($entry['changefreq'], ENT_XML1, 'UTF-8') . "</changefreq>\n";
            }
            if (!empty($entry['priority'])) {
                echo '    <priority>' . htmlspecialchars($entry['priority'], ENT_XML1, 'UTF-8') . "</priority>\n";
            }
            echo "  </url>\n";
        }
        echo "</urlset>\n";
    }

    function robots()
    {
        header('Content-Type: text/plain; charset=UTF-8');
        echo seo_robots_txt_body();
    }
}
