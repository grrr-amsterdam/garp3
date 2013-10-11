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
 *
 * Usage:
 * 
 * Define this behavior in a Spawn model configuration file.
 * "behaviors": {
 *		"Elasticsearchable": {
 *			"columns": ["name", "type", "short_description", "performed_by", "author_name", "director", "city", "cast"],
 *			"rootable": true
 *		}
 * }
 * 
 * Define which columns you want indexed in the 'columns' parameter.
 * Automatically, all related records that are also Elasticsearchable will be included in the indexed node.
 * Provide the 'rootable: true' parameter if this model should have its own entry in the root of the index
 * (as opposed to being related to records of other models).
 */
class Garp_Model_Behavior_Elasticsearchable extends Garp_Model_Behavior_Abstract {
	const ERROR_PRIMARY_KEY_CANNOT_BE_ARRAY =
		'The primary key cannot be an array, since it is used as a string based id in Elasticsearch.';
	const ERROR_NO_EXTRACTABLE_ID =
		'Could not extract the database id from the \'where\' query that was used in deleting this record.';
	const ERROR_RELATION_NOT_FOUND =
		'Could not find relation "%s" in model "%s".';

	/**
	 * @var Array $_columns
	 */
	protected $_columns;

	/**
	 * @var Boolean $_rootable
	 * This indicates whether this model should appear as having its own records in the ES index.
	 * If false, this model will only appear as related records in the indexer.
	 */
	protected $_rootable;
		

	/**
	 * Configuration.
	 * @return Void
	 */
	protected function _setup($config) {
		if (empty($config['columns'])) {
			throw new Garp_Model_Behavior_Exception('"columns" is a required parameter.');
		}

		$this->setColumns($config['columns']);

		$rootable = array_key_exists('rootable', $config)
			? $config['rootable']
			: false
		;
		$this->setRootable($rootable);
	}

	/**
 	 * AfterInsert event listener.
 	 * @param Array $args Event listener parameters
 	 * @return Void
 	 */
	public function afterInsert(&$args) {
		$model      = &$args[0];
		$primaryKey = &$args[2];

		$this->afterSave($model, $primaryKey);
	}

	/**
 	 * AfterUpdate event listener.
 	 * @param Array $args Event listener parameters
 	 * @return Void
 	 */
	public function afterUpdate(&$args) {
		$model 		= $args[0];
		$where 		= $args[3];

		$primaryKey = $model->extractPrimaryKey($where);
		$id 		= $primaryKey['id'];

		$this->afterSave($model, $id);
	}

	/**
	 * Generic method for pushing a database row to the indexer.
	 * @param Garp_Model_Db $model
	 * @param int $primaryKey
	 */
	public function afterSave(Garp_Model_Db $model, $primaryKey) {
		if (is_array($primaryKey)) {
			throw new Exception(self::ERROR_PRIMARY_KEY_CANNOT_BE_ARRAY);
		}

		$boundModel = new Garp_Service_Elasticsearch_Db_BoundModel($model);
		$row = $boundModel->fetchRow($primaryKey);

		if (!$row) {
			/* This is not supposed to happen,
			but due to concurrency it theoretically might. */
			return;
		}

		if (!$this->getRootable()) {
			/* This record should not appear directly in the index,
			*  but only as related records.
			*/
			return;
		}

		$rowFilter 			= new Garp_Service_Elasticsearch_Db_RowFilter($model);
		$columns 			= $this->getColumns();
		$filteredRow 		= $rowFilter->filter($row, $columns);

		$elasticModel 		= $this->_getElasticModel($model);
		$pkMash				= $this->_mashPrimaryKey($primaryKey);
		$filteredRow['id']	= $pkMash;

		$elasticModel->save($filteredRow);
	}

	public function afterDelete(&$args) {
		$model       = $args[0];
		$where       = $args[2];
		$pkExtractor = new Garp_Db_PrimaryKeyExtractor($model, $where);
		$matches = $pkExtractor->extract();
		if (!array_key_exists('id', $matches)) {
			throw new Exception(self::ERROR_NO_EXTRACTABLE_ID);
		}

		$dbId = $matches['id'];
		$elasticModel = $this->_getElasticModel($model);
		$elasticModel->delete($dbId);
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

	/**
	 * @return Boolean
	 */
	public function getRootable() {
		return $this->_rootable;
	}
	
	/**
	 * @param Boolean $rootable
	 */
	public function setRootable($rootable) {
		$this->_rootable = $rootable;
		return $this;
	}

	protected function _getElasticModel(Garp_Model_Db $model) {
		$modelId 		= $model->getNameWithoutNamespace();
		$elasticModel 	= new Garp_Service_Elasticsearch_Model($modelId);

		return $elasticModel;
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
