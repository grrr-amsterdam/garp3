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
	 * @var Array $_data
	 */
	protected $_data;
	

	/**
	 * @param String 									$method 	One of these class' constants
	 * @param String 									$path 		Relative path, excluding index, preceded by a slash
	 * @param Array 									[$data]		List of parameters and their values
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
			->setUri($url);

		if ($data) {
			$client->setRawData(json_encode($data));
		}

		$response = $client->request();

		// $client->getLastRequest()

		return new Garp_Service_Elasticsearch_Response($response);
	}
	
	/**
	 * Creates a full url out of a relative path.
	 * @param String $path A relative path without index, preceded by a slash.
	 */
	public function getUrl() {
		$config 	= $this->getConfig();
		$baseUrl 	= $config->getBaseUrl();
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
	 * @param Array $data
	 */
	public function setData($data) {
		$this->_data = $data;
		return $this;
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
