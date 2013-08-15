<?php
/**
 * Garp_Service_Elasticsearch
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
* @subpackage Elasticsearch
 * @lastmodified $Date: $
 */
class Garp_Service_Elasticsearch extends Zend_Service_Abstract {
	const MSG_INDEX_EXISTS =
		'Index already exists';
	const MSG_INDEX_CREATED =
		'Index created';
	const MSG_INDEX_CREATION_FAILURE =
		'Could not create index';


	public function __construct() {}

	/**
	 * Makes sure the service can be used; creates a primary index.
	 * @return String Log message
	 */
	public function prepare() {
		return $this->_createIndexIfNotExists();
	}

	/**
	 * @return String Log message
	 */
	protected function _createIndexIfNotExists() {
		$indexExists = $this->_fetchIndexExistence();

		if ($indexExists) {
			return self::MSG_INDEX_EXISTS;
		}

		return $this->_createIndex()
			? self::MSG_INDEX_CREATED
			: self::MSG_INDEX_CREATION_FAILURE;
	}

	protected function _createIndex() {
		$request 	= new Garp_Service_Elasticsearch_Request('PUT', '/');
		$response 	= $request->execute();

		return $response->isOk();
	}

	protected function _fetchIndexExistence() {
		$request 	= new Garp_Service_Elasticsearch_Request('GET', '/');
		$response 	= $request->execute();

		return $response->isOk();
	}
}
