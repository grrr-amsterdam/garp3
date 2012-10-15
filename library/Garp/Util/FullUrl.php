<?php
/**
 * Garp_Util_FullUrl
 * Represents a full URL to this application.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Util
 * @lastmodified $Date: $
 */
class Garp_Util_FullUrl {
	/**
 	 * The URL
 	 * @var String
 	 */
	protected $_url;


	/**
 	 * Class constructor
 	 * @param String $baseUrl
 	 * @param Boolean $omitProtocol Whether the protocol should be omitted, resulting in //www.example.com urls.
 	 * @return Void
 	 */
	public function __construct($baseUrl, $omitProtocol = false) {
		$this->_url = $this->_createFullUrl($baseUrl, $omitProtocol);
	}


	/**
 	 * Get the value
 	 * @return String
 	 */
	public function __toString() {
		return $this->_url;
	}


	/**
 	 * Create full URL
 	 * @param String $baseUrl
 	 * @return String
 	 */
	protected function _createFullUrl($baseUrl, $omitProtocol) {
		$application = Zend_Registry::get('application');
		$bootstrap = $application->getBootstrap();

		if ($request = Zend_Controller_Front::getInstance()->getRequest()) {
			$httpHost = $request->getHttpHost();
			$url = $omitProtocol ? '' : $request->getScheme() . ':';
		} else {
			$ini = Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/application.ini');
			if (isset($ini->cdn->domain) && $ini->cdn->domain) {
				$httpHost = $ini->cdn->domain;
			} else throw new Exception('ini.cdn.domain is not defined in application.ini.');
			$url = $omitProtocol ? '' : 'http:';
		}

		$viewObj = $bootstrap->getResource('view');
		$url .= '//' . $httpHost . $viewObj->baseUrl($baseUrl);
		return $url;
	}
}
