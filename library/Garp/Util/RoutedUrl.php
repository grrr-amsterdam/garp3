<?php
/**
 * Garp_Util_RoutedUrl
 * Represents a URL, assembled from a route definition.
 *
 * @package Garp_Util
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Util_RoutedUrl implements JsonSerializable {
    /**
     * @var string
     */
    protected $_url;

    /**
     * Class constructor
     *
     * @param string $routeName
     * @param array $params
     * @param Zend_Controller_Router_Interface $router
     * @return void
     */
    public function __construct(
        $routeName, $params = array(), Zend_Controller_Router_Interface $router = null
    ) {
        $router = $router ?: Zend_Controller_Front::getInstance()->getRouter();
        if (!$router) {
            throw new Exception('Router not found.');
        }

        $this->_url = $router->assemble($params, $routeName);
    }

    /**
     * @return string
     */
    public function __toString(): string {
        return $this->_url;
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string {
        return $this->__toString();
    }
}
