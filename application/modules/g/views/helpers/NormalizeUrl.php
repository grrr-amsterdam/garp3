<?php
/**
 * G_View_Helper_NormalizeUrl
 * Make sure a URL string has a protocol
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      G_View_Helper
 */
class G_View_Helper_NormalizeUrl extends Zend_View_Helper_Abstract {

	public function normalizeUrl($str) {
		$scheme = parse_url($str, PHP_URL_SCHEME);
		if (!$scheme) {
			return 'http://' . $str;
		}
		return $str;
	}

}
