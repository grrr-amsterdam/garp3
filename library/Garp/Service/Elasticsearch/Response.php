<?php
/**
 * Garp_Service_Elasticsearch_Response
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage CinemaNl
 * @lastmodified $Date: $
 */
class Garp_Service_Elasticsearch_Response {

	/**
	 * @var Zend_Http_Response $_httpResponse
	 */
	protected $_httpResponse;
	
	/**
	 * @param Zend_Http_Response $response The response returned by a Garp_Service_Elasticsearch_Request
	 */
	public function __construct(Zend_Http_Response $httpResponse) {
		$this->setHttpResponse($httpResponse);
	}

	public function isOk() {
		$httpResponse = $this->getHttpResponse();
		return $httpResponse->isSuccessful();
	}

	public function getBody() {
		$httpResponse = $this->getHttpResponse();
		return $httpResponse->getBody();
	}

	/**
	 * @return Zend_Http_Response
	 */
	public function getHttpResponse() {
		return $this->_httpResponse;
	}
	
	/**
	 * @param Zend_Http_Response $httpResponse
	 */
	public function setHttpResponse($httpResponse) {
		$this->_httpResponse = $httpResponse;
		return $this;
	}

}
