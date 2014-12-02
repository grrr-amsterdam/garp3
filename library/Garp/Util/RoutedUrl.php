<?php
/**
 * Garp_Util_RoutedUrl
 * Represents a URL, assembled from a route definition.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp_Util
 */
class Garp_Util_RoutedUrl {
	/**
 	 * @var String
 	 */
	protected $_url;

	/**
 	 * Class constructor
 	 * @param String $routeName
 	 * @param Array $params
 	 * @param Zend_Controller_Router_Interface $router
 	 * @return Void
 	 */
	public function __construct($routeName, $params = array(), Zend_Controller_Router_Interface $router = null) {
		$router = $router ?: Zend_Controller_Front::getInstance()->getRouter();
		if (!$router) {
			throw new Exception('Router not found.');
		}

		$this->_url = $router->assemble($params, $routeName);
	}

	/**
 	 * @return String
 	 */
	public function __toString() {
		return $this->_url;
	}
}
