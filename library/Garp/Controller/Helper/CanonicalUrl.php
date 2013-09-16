<?php
/**
 * Garp_Controller_Helper_CanonicalUrl
 * Validates the canonical URL of a page
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp_Controller_Helper
 */
class Garp_Controller_Helper_CanonicalUrl extends Zend_Controller_Action_Helper_Abstract {
	/**
 	 * Helper method that combines validating and redirecting
 	 * @param String $canonical
 	 * @return Boolean
 	 */
	public function validateOrRedirect($canonical) {
		if (!$this->validate($canonical)) {
			$this->redirect($canonical);
			return false;
		}
		return true;
	}

	/**
 	 * Validate wether the supposed canonical url is actually the current request url
 	 * @param String $canonical
 	 * @return Boolean
 	 */
	public function validate($canonical) {
		$currUrl = $this->getRequest()->getRequestUri();
		// match using a full url if the canonical starts with a protocol
		if (preg_match('~^[a-z]+://~', $canonical)) {
			$isObj = $canonical instanceof Garp_Util_FullUrl;
			$omitProtocol = $isObj ? $canonical->getOmitProtocol() : false;
			$omitBaseUrl = $isObj ? $canonical->getOmitBaseUrl() : false;
			$currUrl = new Garp_Util_FullUrl($currUrl, $omitProtocol, $omitBaseUrl);
		}
		return (string)$currUrl == (string)$canonical;
	}

	/**
 	 * Redirect to the canonical URL
 	 * @param String $canonical
 	 * @return Void
 	 */
	public function redirect($canonical) {
		if (!preg_match('~^[a-z]+://~', $canonical)) {
			$canonical = new Garp_Util_FullUrl($canonical);
		}
		$controller = $this->getActionController();
		$controller->getHelper('redirector')->gotoUrl((string)$canonical, array('code' => 301));
		$controller->getHelper('cache')->disable();
		$controller->getHelper('viewRenderer')->setNoRender(true);
		$controller->getHelper('layout')->disableLayout();
	}
}	
