<?php
/**
 * Garp_Controller_Helper_Cache
 * class description
 *
 * @package Garp_Controller_Cache
 * @author  David Spreekmeester <david@grrr.nl>
 * @author  Ramiro Hammen <ramiro@grrr.nl>
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Controller_Helper_Cache extends Zend_Controller_Action_Helper_Cache {
    /**
     * Maximum amount of characters in a request
     *
     * @var int
     */
    const MAX_REQUEST_LENGTH = 255;

    /**
     * Wether caching is enabled
     *
     * @var Boolean
     */
    protected $_enabled = true;

    /**
     * Sets the required HTTP headers to specify an expiration time.
     *
     * @param int $expirationTimeInSeconds
     * @return void
     */
    public function setCacheHeaders($expirationTimeInSeconds = 300) {
        $expirationString = strtotime("+{$expirationTimeInSeconds} seconds");
        $gmtDate = gmdate(DATE_RFC1123, $expirationString);

        $this->getResponse()
            ->setHeader('Cache-Control', 'public', true)
            ->setHeader('Pragma', 'cache', true)
            ->setHeader('Expires', $gmtDate, true);
    }

    /**
     * Sets the required HTTP headers to prevent this request from being cached by the browser.
     *
     * @param Zend_Controller_Response_Http $response The HTTP response object.
     *                                                Use $this->getResponse() from a controller.
     * @return void
     */
    public function setNoCacheHeaders(Zend_Controller_Response_Http $response) {
        $this->getResponse()
            ->setHeader('Cache-Control', 'no-cache', true)
            ->setHeader('Pragma', 'no-cache', true)
            ->setHeader('Expires', date(DATE_RFC1123, strtotime('-1 year')), true);
    }

    /**
     * Sets cache headers with a default expiration time of 5 minute (300 seconds)
     *
     * @param int $expirationTimeInSeconds
     * @return void
     */
    public function setExpiresHeader($expirationTimeInSeconds = 300) {
        $this->getResponse()
            ->setHeader(
                'Expires',
                gmdate('D, d M Y H:i:s \G\M\T', time() + $expirationTimeInSeconds)
            );
    }

    /**
     * Commence page caching for any cacheable actions
     *
     * @return void
     */
    public function preDispatch() {
        if ($this->getResponse()->isRedirect() || !$this->isEnabled() || $this->_requestUriTooLong()
        ) {
            return true;
        }
        return parent::preDispatch();
    }

    /**
     * Check wether (static) caching is disabled
     *
     * @return bool
     */
    public function isEnabled() {
        return $this->_enabled;
    }

    /**
     * Enable caching
     *
     * @return void
     */
    public function enable() {
        $this->_enabled = true;
    }

    /**
     * Disable caching
     *
     * @return void
     */
    public function disable() {
        $this->_enabled = false;
    }

    /**
     * Most *nix systems have a 255-byte filename limit.
     * Creating a static cache file for a request uri over this limit will throw an exception.
     *
     * @return bool
     */
    protected function _requestUriTooLong() {
        $reqUri = basename($this->getRequest()->getRequestUri());
        return strlen($reqUri) > self::MAX_REQUEST_LENGTH;
    }
}
