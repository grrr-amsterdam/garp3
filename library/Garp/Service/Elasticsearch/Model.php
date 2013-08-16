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
	const SEPARATOR = '/';

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

		$path = $this->_getPath($data['id']);
		unset($data['id']);

		$request 	= new Garp_Service_Elasticsearch_Request('PUT', $path, $data);
		$response	= $request->execute();

		if (!$response->isOk()) {
			throw new Exception($response->getBody());
		}
	}

	public function fetch($id) {
		$path = $this->_getPath($id);

		$request 	= new Garp_Service_Elasticsearch_Request('GET', $path);
		$response	= $request->execute();

		return $response->getBody();
	}

	public function delete($id) {
		$path = $this->_getPath($id);

		$request 	= new Garp_Service_Elasticsearch_Request('DELETE', $path);
		$response	= $request->execute();

		return $response->isOk();
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

	public function _getPath($id) {
		$modelName 	= $this->getModelName();

		$urlParts = array(
			$modelName,
			$id
		);

		$url = self::SEPARATOR . implode(self::SEPARATOR, $urlParts);
		return $url;
	}

}
