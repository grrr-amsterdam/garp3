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
	const INVALID_ROUTE = 'Given route is invalid. Please provide an array with valid keys 0 and 1.';
	const CANNOT_RESOLVE_HTTP_HOST = 'Unable to resolve host. Please configure app.domain.';

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
		$route = $this->_resolveRoute($route);
		$httpHost = $this->_getHttpHost();

		$url = $this->_omitProtocol ? '' : $this->_getScheme() . ':';
		$url .= '//' . $httpHost . $route;
		return $url;
	}

	protected function _resolveRoute($route) {
		if (is_array($route)) {
			$this->_validateRouteArray($route);
			$router = Zend_Controller_Front::getInstance()->getRouter();
			return $router->assemble($route[0], $route[1]);
		} 
		if (!$this->_omitBaseUrl) {
			$application = Zend_Registry::get('application');
			$bootstrap = $application->getBootstrap();
			$viewObj = $bootstrap->getResource('view');
			return $viewObj->baseUrl($route);
		}
		return $route;
	}		

	protected function _getHttpHost() {
		// Check what the developer has configured.
		$config = Zend_Registry::get('config');
		if (isset($config->app->domain)) {
			return $config->app->domain;
		}

		// Check what Zend has picked up from the request.
		if ($request = Zend_Controller_Front::getInstance()->getRequest()) {
			$httpHost = $request->getHttpHost();
			return $httpHost;
		}

		// If all else fails, use cdn domain... but you probably don't want that.
		if (isset($config->cdn->domain)) {
			$httpHost = $config->cdn->domain;
			return $httpHost;
		}
		
		throw new Garp_Exception(CANNOT_RESOLVE_HTTP_HOST);
	}

	protected function _getScheme() {
		if ($request = Zend_Controller_Front::getInstance()->getRequest()) {
			return $request->getScheme();
		}
		return 'http';
	}

	/**
 	 * Check if given route array is valid.
 	 * @param Array $route
 	 * @return Boolean
 	 */
	protected function _validateRouteArray(array $route) {
		if (!array_key_exists(0, $route) || !array_key_exists(1, $route)) {
			throw new Garp_Exception(self::INVALID_ROUTE);
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
