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
	const ERROR_NOT_FOUND =
		"I can't find what you were looking for.";
	const ERROR_UNKNOWN_ERROR = 
		'Could not retrieve error message.';

	/**
	 * @var Zend_Http_Response $_httpResponse
	 */
	protected $_httpResponse;
	
	/**
	 * @param Zend_Http_Response $response The response returned by a Garp_Service_Elasticsearch_Request
	 */
	public function __construct(Zend_Http_Response $httpResponse) {
		$this->setHttpResponse($httpResponse);

		if (!$this->isOk()) {
			// Zend_Debug::dump($httpResponse); exit;
			throw new Exception($this->getError());
		}
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
	 * @return String The error message. Throws Exception if message cannot be retrieved.
	 */
	public function getError() {
		$bodyJson 	= $this->getBody();
		$body 		= json_decode($bodyJson, true);
		
		if (array_key_exists('error', $body)) {
			return $body['error'];
		}

		if ($this->getHttpResponse()->getStatus() === 404) {
			throw new Exception(self::ERROR_NOT_FOUND . ' ' . $this->getBody());
		}

		throw new Exception(self::ERROR_UNKNOWN_ERROR);
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
