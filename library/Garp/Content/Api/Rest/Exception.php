<?php
/**
 * Garp_Content_Api_Rest_Exception
 * class description
 *
 * @package Garp_Content_Api_Rest_Exception
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Content_Api_Rest_Exception extends Exception {

    protected $_httpStatusCode = 400;

    /**
     * Communicate an HTTP status code
     *
     * @param int $code
     * @return void
     */
    public function setHttpStatusCode($code) {
        $this->_httpStatusCode = $code;
    }

    public function getHttpStatusCode() {
        return $this->_httpStatusCode;
    }
}
