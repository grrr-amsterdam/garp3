<?php
/**
 * Garp_View_Helper_Sitemap
 * Helper for Google sitemaps
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp_View_Helper
 */
class G_View_Helper_Sitemap extends Zend_View_Helper_Abstract {

	public function sitemap() {
		return $this;
	}

	/**
 	 * Render a <url> tag
 	 * @param String $url
 	 * @param String $change_freq
 	 * @param String $priority
 	 * @param String $lastmod
 	 * @return String
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
