<?php
/**
 * Garp_Service_Elasticsearch_Request
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Elasticsearch
 * @lastmodified $Date: $
 */
class Garp_Service_Elasticsearch_Request {
	const GET 		= 'GET';
	const PUT 		= 'PUT';
	const DELETE 	= 'DELETE';

	const ERROR_DATA_HAS_INVALID_TYPE =
		'Data passed along to an Elasticsearch request can be either an Array or a Json string, not %.';
	const ERROR_INVALID_METHOD =
		'This method is not valid as a Elasticsearch_Request method.';

	/**
	 * @var String $_method
	 */
	protected $_method;

	/**
	 * @var String $_path
	 */
	protected $_path;

	/**
	 * @var Zend_Http_Client $_client
	 */
	protected $_client;

	/**
	 * @var Garp_Service_Elasticsearch_Configuration $_config
	 */
	protected $_config;
	
	/**
	 * @var String $_data Optional Json string of data
	 */
	protected $_data;
	

	/**
	 * @param String 									$method 	One of these class' constants
	 * @param String 									$path 		Relative path, excluding index, preceded by a slash
	 * @param Mixed 									[$data]		List of parameters and their values.
	 * 																Can be an Array or a Json string.
	 */
	public function __construct($method, $path, $data = null) {
		$config = new Garp_Service_Elasticsearch_Configuration();
		$this->setConfig($config);

		$this->_validateMethod($method);
		$this->setMethod($method);
		
		$this->setPath($path);
		$this->setClient(new Zend_Http_Client());

		$this->setData($data);
	}

	/**
	 * Executes the request and returns a response.
	 * @return Garp_Service_Elasticsearch_Response
	 */
	public function execute() {
		$method = constant('Zend_Http_Client::' . $this->getMethod());
		$client = $this->getClient();
		$url 	= $this->getUrl();
		$data 	= $this->getData();

		$client
			->setMethod($method)
			->setUri($url)
		;

		if ($data) {
			$client->setRawData($data);
		}

		$response = $client->request();

		return new Garp_Service_Elasticsearch_Response($response);
	}
	
	/**
	 * Creates a full url out of a relative path.
	 * @param String $path A relative path without index, preceded by a slash.
	 */
	public function getUrl() {
		$config 	= $this->getConfig();
		$baseUrl 	= $this->isReadOnly()
			? $config->getReadBaseUrl()
			: $config->getWriteBaseUrl()
		;
		$index 		= $config->getIndex();
		$path 		= $this->getPath();

		$url 		= $baseUrl . '/' . $index . $path;

		return $url;
	}

	/**
	 * @return Garp_Service_Elasticsearch_Configuration
	 */
	public function getConfig() {
		return $this->_config;
	}
	
	/**
	 * @param Garp_Service_Elasticsearch_Configuration $config
	 */
	public function setConfig(Garp_Service_Elasticsearch_Configuration $config) {
		$this->_config = $config;
		return $this;
	}
	
	/**
	 * @return Zend_Http_Client
	 */
	public function getClient() {
		return $this->_client;
	}
	
	/**
	 * @param Zend_Http_Client $client
	 */
	public function setClient($client) {
		$this->_client = $client;
		return $this;
	}

	/**
	 * @return String
	 */
	public function getPath() {
		return $this->_path;
	}
	
	/**
	 * @param String $path
	 */
	public function setPath($path) {
		$this->_path = $path;
		return $this;
	}
	
	/**
	 * @return String
	 */
	public function getMethod() {
		return $this->_method;
	}
	
	/**
	 * @param String $method
	 */
	public function setMethod($method) {
		$this->_method = $method;
		return $this;
	}

	/**
	 * @return Array
	 */
	public function getData() {
		return $this->_data;
	}
	
	/**
	 * @param Mixed $data Array or Json string
	 */
	public function setData($data) {
		if (is_null($data)) {
			return;
		}

		if (is_array($data)) {
			$data = json_encode($data);
		}

		if (!is_string($data)) {
			$error = sprintf(self::ERROR_DATA_HAS_INVALID_TYPE, gettype($data));
			throw new Exception($error);
		}

		$this->_data = $data;
	}

	public function isReadOnly() {
		$method = $this->getMethod();
		return $method === self::GET;
	}

	protected function _validateMethod($method) {
		$validMethods = array(
			self::GET,
			self::PUT,
			self::DELETE
		);
		
		if (!in_array($method, $validMethods)) {
			throw new Exception(self::ERROR_INVALID_METHOD);
		}
	}
}
