<?php
/**
 * Garp_Util_FullUrl
 * Represents a full URL to this application.
 *
 * @package Garp_Util
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Util_FullUrl implements JsonSerializable {
    const INVALID_ROUTE = 'Given route is invalid. Please provide an array with valid keys 0 and 1.';
    const CANNOT_RESOLVE_HTTP_HOST = 'Unable to resolve host. Please configure app.domain.';

    /**
     * The URL
     *
     * @var string
     */
    protected $_url;

    /**
     * Wether to omit protocol
     *
     * @var bool
     */
    protected $_omitProtocol;

    /**
     * Wether to omit baseUrl
     *
     * @var bool
     */
    protected $_omitBaseUrl;

    /**
     * Class constructor
     *
     * @param string|array $route String containing the path or Array containing route properties
     *                            (@see Zend_View_Helper_Url for the format)
     * @param bool $omitProtocol Whether the protocol should be omitted,
     *                           resulting in //www.example.com urls.
     * @param bool $omitBaseUrl Wether the baseUrl should be omitted,
     *                          for strings that already contain that.
     * @return void
     */
    public function __construct($route, $omitProtocol = false, $omitBaseUrl = false) {
        $this->_omitProtocol = $omitProtocol;
        $this->_omitBaseUrl = $omitBaseUrl;
        $this->_url = $this->_createFullUrl($route);
    }

    /**
     * Get the value
     *
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

    /**
     * Create full URL
     *
     * @param string $route
     * @return string
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
            $baseUrlHelper = new Zend_View_Helper_BaseUrl();
            return $baseUrlHelper->baseUrl($route);
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

        throw new Garp_Exception(self::CANNOT_RESOLVE_HTTP_HOST);
    }

    protected function _getScheme() {
        if (isset(Zend_Registry::get('config')->app->protocol)) {
            return Zend_Registry::get('config')->app->protocol;
        }
        if (!$request = Zend_Controller_Front::getInstance()->getRequest()) {
            return 'http';
        }
        /**
         * When using CloudFront scheme will be reported as "http" even when it's "https".
         * When configured correctly, the HTTP_CLOUDFRONT_FORWARDED_PROTO will contain the right
         * value.
         */
        if ($request->getHeader('CLOUDFRONT_FORWARDED_PROTO')) {
            return $request->getHeader('CLOUDFRONT_FORWARDED_PROTO');
        }
        return $request->getScheme();
    }

    /**
     * Check if given route array is valid.
     *
     * @param array $route
     * @return bool
     */
    protected function _validateRouteArray(array $route) {
        if (!array_key_exists(0, $route) || !array_key_exists(1, $route)) {
            throw new Garp_Exception(self::INVALID_ROUTE);
        }
    }

    /**
     * Get omitBaseUrl
     *
     * @return bool
     */
    public function getOmitBaseUrl() {
        return $this->_omitBaseUrl;
    }

    /**
     * Get omitProtocol
     *
     * @return Boolean
     */
    public function getOmitProtocol() {
        return $this->_omitProtocol;
    }

}
