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

		$this->setColumns($config['columns']);
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

		$this->_afterSave($model, $primaryKey, $data);
	}

	/**
 	 * AfterUpdate event listener.
 	 * @param Array $args Event listener parameters
 	 * @return Void
 	 */
	public function afterUpdate(&$args) {
		$model 		= $args[0];
		$data 		= $args[2];
		$where 		= $args[3];

		$primaryKey = $model->extractPrimaryKey($where);
		$id 		= $primaryKey['id'];

		$this->_afterSave($model, $id, $data);
	}

	public function afterDelete(&$args) {
		$model 		= $args[0];
		$result		= $args[1];
		$where 		= $args[2];

		/**
		* @todo
		*/
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

		if (!array_key_exists('id', $columns)) {
			array_unshift($columns, 'id');
		}

		$this->_columns = $columns;
		return $this;
	}

	protected function _afterSave($model, $primaryKey, $data) {
		if (is_array($primaryKey)) {
			throw new Exception(self::ERROR_PRIMARY_KEY_CANNOT_BE_ARRAY);
		}

		$modelId 		= $model->getNameWithoutNamespace();
		$elasticModel 	= new Garp_Service_Elasticsearch_Model($modelId);

		$pkMash			= $this->_mashPrimaryKey($primaryKey);
		$data['id']		= $pkMash;

		$columns 		= $this->getColumns();
		$columnsAsKeys 	= array_flip($columns);
		$elasticData 	= array_intersect_key($data, $columnsAsKeys);
		$elasticModel->save($elasticData);
	}

	/**
	 * @param Mixed $primaryKey
	 * @return String
	 */
	protected function _mashPrimaryKey($primaryKey) {
		if (is_array($primaryKey)) {
			$mash = implode('-', $primaryKey);
			return $mash;
		}

		return (string)$primaryKey;
	}

}


