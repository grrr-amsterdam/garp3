<?php
/**
 * Garp_Model_Behavior_Elasticsearchable
 * Adds fields and related models to an Elasticsearch index.
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Behavior
 * @lastmodified $Date: $
 */
class Garp_Model_Behavior_Elasticsearchable extends Garp_Model_Behavior_Abstract {
	const ERROR_PRIMARY_KEY_CANNOT_BE_ARRAY =
		'The primary key cannot be an array, since it is used as a string based id in Elasticsearch.';

	/**
	 * @var Array $_columns
	 */
	protected $_columns;
	

	/**
	 * Configuration.
	 * @return Void
	 */
	protected function _setup($config) {
		if (empty($config['columns'])) {
			throw new Garp_Model_Behavior_Exception('"columns" is a required parameter.');
		}

		$this->setColumns($config['column']);
	}

	/**
 	 * AfterInsert event listener.
 	 * @param Array $args Event listener parameters
 	 * @return Void
 	 */
	public function afterInsert(&$args) {
		$model      = &$args[0];
		$data       = &$args[1];
		$primaryKey = &$args[2];
		$this->_afterSave($model, $primaryKey);
	}

	/**
 	 * AfterUpdate event listener.
 	 * @param Array $args Event listener parameters
 	 * @return Void
 	 */
	public function afterUpdate(&$args) {
		$model = $args[0];
		$where = $args[3];
		$primaryKey = $model->extractPrimaryKey($where);
		$id = $primaryKey['id'];
		$this->_afterSave($model, $id);
	}

	/**
	 * @return Array
	 */
	public function getColumns() {
		return $this->_columns;
	}
	
	/**
	 * @param Mixed $columns
	 */
	public function setColumns($columns) {
		if (is_string($columns)) {
			$columns = (array)$columns;
		}

		$this->_columns = $columns;
		return $this;
	}

	protected function _afterSave($model, $primaryKey) {
		if (is_array($primaryKey)) {
			throw new Exception(self::ERROR_PRIMARY_KEY_CANNOT_BE_ARRAY);
		}

		$elasticModel = new Garp_Service_Elasticsearch_Model();

		//////////????????????
	}

}
