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
 	 * Wether to omit protocol
 	 * @var Boolean
 	 */
	protected $_omitProtocol;

	/**
 	 * Wether to omit baseUrl
 	 * @var Boolean
 	 */
	protected $_omitBaseUrl;

	/**
 	 * Class constructor
 	 * @param String|Array $route String containing the path or Array containing route properties
 	 *                            (@see Zend_View_Helper_Url for the format)
 	 * @param Boolean $omitProtocol Whether the protocol should be omitted, resulting in //www.example.com urls.
 	 * @param Boolean $omitBaseUrl Wether the baseUrl should be omitted, for strings that already contain that.
 	 * @return Void
 	 */
	public function __construct($route, $omitProtocol = false, $omitBaseUrl = false) {
		$this->_omitProtocol = $omitProtocol;
		$this->_omitBaseUrl = $omitBaseUrl;
		$this->_url = $this->_createFullUrl($route);
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
 	 * @param String $route
 	 * @return String
 	 */
	protected function _createFullUrl($route) {
		$application = Zend_Registry::get('application');
		$bootstrap = $application->getBootstrap();
		$viewObj = $bootstrap->getResource('view');
		$omitProtocol = $this->_omitProtocol;
		$omitBaseUrl = $this->_omitBaseUrl;

		if (is_array($route)) {
			$this->_validateRouteArray($route);
			$router = Zend_Controller_Front::getInstance()->getRouter();
			$route = $router->assemble($route[0], $route[1]);
		} elseif (!$omitBaseUrl) {
			$route = $viewObj->baseUrl($route);
		}

		if ($request = Zend_Controller_Front::getInstance()->getRequest()) {
			$httpHost = $request->getHttpHost();
			$url = $omitProtocol ? '' : $request->getScheme() . ':';
		} else {
			$ini = Zend_Registry::get('config');
			$this->_validateIniConfig($ini);
			$httpHost = $ini->cdn->domain;
			$url = $omitProtocol ? '' : 'http:';
		}

		$url .= '//' . $httpHost . $route;
		return $url;
	}

	/**
 	 * Check if given route array is valid.
 	 * @param Array $route
 	 * @return Boolean
 	 */
	protected function _validateRouteArray(array $route) {
		if (!array_key_exists(0, $route) || !array_key_exists(1, $route)) {
			throw new Garp_Exception('Given route is invalid. Please provide an array '.
				'with valid keys 0 and 1.');
		}
	}		

	/**
 	 * Check ini config for required keys
 	 * @param Zend_Config $ini
 	 * @return Boolean
 	 */
	protected function _validateIniConfig(Zend_Config $ini) {
		if (!isset($ini->cdn->domain) || !$ini->cdn->domain) {
			throw new Exception('ini.cdn.domain is not defined in application.ini.');
		}
	}

	/**
	 * Get omitBaseUrl
	 * @return Boolean
	 */
	public function getOmitBaseUrl() {
		return $this->_omitBaseUrl;
	}

	/**
	 * Get omitProtocol
	 * @return Boolean
	 */
	public function getOmitProtocol() {
		return $this->_omitProtocol;
	}
	
}
