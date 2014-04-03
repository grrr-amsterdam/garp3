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
	 * @param String $url Relative URL (will be passed thru baseUrl())
	 * @return String Full URL
	 */
	public function fullUrl($url) {
		$request = Zend_Controller_Front::getInstance()->getRequest();
		$url = $request->getScheme().'://'.$request->getHttpHost().
				$this->view->baseUrl($url);
		return $url;
	}
}