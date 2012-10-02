<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Controller_Helper_Cache extends Zend_Controller_Action_Helper_Cache {
	/**
	 * Sets the required HTTP headers to prevent this request from being cached by the browser.
	 * @param Zend_Controller_Response_Http $response The HTTP response object. Use $this->getResponse() from a controller.
	 */
	public function setNoCacheHeaders(Zend_Controller_Response_Http $response) {
		$this->getResponse()
			->setHeader('Cache-Control', 'no-cache', true)
			->setHeader('Pragma', 'no-cache', true)
			->setHeader('Expires', date(DATE_RFC1123, strtotime('-1 year')), true)
		;
	}
}