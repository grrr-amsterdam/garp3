<?php
/**
 * Garp_Service_Elasticsearch_Model
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Elasticsearch
 * @lastmodified $Date: $
 */
class Garp_Service_Elasticsearch_Model {
	const ERROR_NO_ID =
		'An id should be present in the provided data.';

	/**
	 * @var String $_modelName
	 */
	protected $_modelName;

	/**
	 * @param String									$modelName
	 */
	public function __construct($modelName) {
		$this->setModelName($modelName);
	}

	public function save(array $data) {
		if (!array_key_exists('id', $data)) {
			throw new Exception(self::ERROR_NO_ID);
		}

		$url = $this->getUrl($data['id']);

		unset($data['id']);

		$request 	= new Garp_Service_Elasticsearch_Request('PUT', '/');


		Zend_Debug::dump($url);

		Zend_Debug::dump($data); exit;
	}

	public function getPath($id) {
		$modelName 	= $this->getModelName();

		$urlParts = array(
			$baseUrl,
			$index,
			$modelName,
			$id
		);

		$url = implode('/', $urlParts);
		return $url;
	}
	
	/**
	 * @return String
	 */
	public function getModelName() {
		return $this->_modelName;
	}
	
	/**
	 * @param String $modelName
	 */
	public function setModelName($modelName) {
		$this->_modelName = $modelName;
		return $this;
	}


}
