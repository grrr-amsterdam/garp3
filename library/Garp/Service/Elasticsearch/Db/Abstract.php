<?php
/**
 * Garp_Service_Elasticsearch_Db_Abstract
 * Generic Db class for convenience methods.
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Elasticsearch
 * @lastmodified $Date: $
 */
abstract class Garp_Service_Elasticsearch_Db_Abstract {
	
	protected function _getModelNamespace() {
		$namespace = APPLICATION_ENV === 'testing'
			? 'Mocks_Model_'
			: 'Model_'
		;

		return $namespace;
	}
}