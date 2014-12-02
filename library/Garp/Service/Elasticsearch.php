<?php
/**
 * Garp_Service_Elasticsearch
 * This class handles the overall Elasticsearch functionality.
 * For model based interaction, you'll need Garp_Service_Elasticsearch_Model instead.
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Elasticsearch
 * @lastmodified $Date: $
 */
class Garp_Service_Elasticsearch extends Zend_Service_Abstract {
	const CUSTOM_MAPPING_FILE = '/configs/elasticsearch-mapping.json';


	public function createIndex() {
		$mapping 	= $this->_getMapping();
		$configJson	= $mapping
			? '{"mappings": ' . $mapping . '}'
			: null
		;

		$request 	= new Garp_Service_Elasticsearch_Request('PUT', '/', $configJson);
		$response 	= $request->execute();

		return $response->isOk();
	}

	public function doesIndexExist() {
		try {
			$request 	= new Garp_Service_Elasticsearch_Request('GET', '/_mapping');
			$response 	= $request->execute();
			return $response->isOk();
		} catch (Exception $e) {}

		return false;
	}

	public function remap() {
		$mappingJson 	= $this->_getMapping();
		$mapping		= json_decode($mappingJson, true);

		foreach ($mapping as $type => $typeMapping) {
			$path 		= '/' . $type . '/_mapping';
			$typeMappingJson = json_encode(array($type => $typeMapping));
			$request 	= new Garp_Service_Elasticsearch_Request('PUT', $path, $mapping);
			$response 	= $request->execute();			
		}

		return true;
	}

	protected function _getMapping() {
		$mappingFilePath = APPLICATION_PATH . self::CUSTOM_MAPPING_FILE;
		$mapping = file_exists($mappingFilePath)
			? file_get_contents($mappingFilePath)
			: null
		;

		return $mapping;
	}
}
