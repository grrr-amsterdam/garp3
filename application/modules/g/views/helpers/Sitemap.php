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
 	 */
	public function url($url, $change_freq, $priority) {
		$tag = "<url>\n\t".
			"<loc>{$url}</loc>\n\t".
			"<changefreq>{$change_freq}</changefreq>\n\t".
			"<priority>{$priority}</priority>\n".
			"</url>\n";
		return $tag;
	}

}
