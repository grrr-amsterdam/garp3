<?php
/**
 * G_View_Helper_FullUrl
 * Returns the full URL to a page within this website.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class G_View_Helper_FullUrl extends Zend_View_Helper_Abstract {
	/**
	 * Create full URL from relative URL.
	 * @param String $url Relative URL (will be passed thru baseUrl() if $omitBaseUrl = false)
	 * @return String Full URL
	 */
	public function fullUrl($url = false, $omitProtocol = false, $omitBaseUrl = false) {
		if (!$url) {
			$url = $this->view->url();
		}
		$fullUrl = new Garp_Util_FullUrl($url, $omitProtocol, $omitBaseUrl);
		return $fullUrl->__toString();
	}
}
