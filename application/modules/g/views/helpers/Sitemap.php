<?php
/**
 * Garp_View_Helper_Sitemap
 * Helper for Google sitemaps
 *
 * @package G_View_Helper
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class G_View_Helper_Sitemap extends Zend_View_Helper_Abstract {

    public function sitemap() {
        return $this;
    }

    /**
     * Render a <url> tag
     *
     * @param string $url
     * @param string $change_freq
     * @param string $priority
     * @param string $lastmod
     * @return string
     */
    public function url($url, $change_freq = null, $priority = null, $lastmod = null) {
        $tag = "<url>\n";
        $tag .= "\t<loc>{$url}</loc>\n";
        if ($change_freq) {
            $tag .= "\t<changefreq>{$change_freq}</changefreq>\n";
        }
        if ($priority) {
            $tag .= "\t<priority>{$priority}</priority>\n";
        }
        if ($lastmod) {
            $lastmod = date('Y-m-d', strtotime($lastmod));
            $tag .= "\t<lastmod>{$lastmod}</lastmod>\n";
        }

        $tag .= "</url>\n";
        return $tag;
    }

}
