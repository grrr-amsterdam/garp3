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

		$rowObj = $this->_fetchRow($model, $primaryKey);
		if (!$rowObj) {
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

		$row 				= $rowObj->toArray();
		$filteredRow 		= $this->_filterRow($row, $model);

		$elasticModel 		= $this->_getElasticModel($model);
		$pkMash				= $this->_mashPrimaryKey($primaryKey);
		$filteredRow['id']	= $pkMash;

		$elasticModel->save($filteredRow);
	}

	public function afterDelete(&$args) {
		$model 		= $args[0];
		$where 		= $args[2];
		$search 	= "/\=\s*(\d+)/";
		preg_match($search, $where, $matches);
		
		if (!array_key_exists(1, $matches)) {
			throw new Exception(self::ERROR_NO_EXTRACTABLE_ID);
		}

		$dbId = $matches[1];
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

	protected function _filterRow(array $rowWithRelations, Garp_Model_Db $model) {
		$filteredRow 	= array();
		$columns 		= $this->getColumns();
		
		foreach ($rowWithRelations as $columnName => $value) {
			if (
				!is_array($value) &&
				!in_array($columnName, $columns)
			) {
				// this is a column of the primary model that should not be indexed
				continue;
			}

			if (is_array($value)) {
				//	this is data from a related model
				$value = $this->_filterRelatedData($value, $columnName, $model);
			}

			$filteredRow[$columnName] = $value;
		}

		return $filteredRow;
	}

	protected function _filterRelatedData(array &$data, $relationName, Garp_Model_Db $model) {
		if ($data && is_array($data) && is_array(current($data))) {
			$this->_filterRelatedRowSet($data, $relationName, $model);
			//	this is not a row but a rowset, so walk over it.

			return $data;
		}

		return $this->_filterRelatedRow($data, $relationName, $model);
	}

	protected function _filterRelatedRowSet(array &$data, $relationName, Garp_Model_Db $model) {
		foreach ($data as $i => $dataNode) {
			$data[$i] = $this->_filterRelatedRow($dataNode, $relationName, $model);
		}

		return $data;
	}

	protected function _filterRelatedRow(array &$data, $relationName, Garp_Model_Db $model) {
		$modelClass 	= $this->_getModelClassFromRelationName($model, $relationName);
		$relModel 		= new $modelClass();
		$behavior 		= $relModel->getObserver('Elasticsearchable');

		if (!$behavior) {
			return;
		}

		$columns 		= $behavior->getColumns();

		$columnsAsKeys 	= array_flip($columns);
		$filteredData 	= array_intersect_key($data, $columnsAsKeys);

		return $filteredData;
	}

	protected function _getModelClassFromRelationName(Garp_Model_Db $model, $relationName) {
		$relations 		= $model->getConfiguration('relations');
		if (!array_key_exists($relationName, $relations)) {
			$error = sprintf(self::ERROR_RELATION_NOT_FOUND, $relationName, get_class($model));
			throw new Exception($error);
		}

		$namespace = $this->_getModelNamespace();
		$modelClass = $namespace . $relations[$relationName]['model'];

		return $modelClass;
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

	protected function _fetchRow(Garp_Model_Db $model, $primaryKey) {
		$relations = $model->getConfiguration('relations');

		foreach ($relations as $relation) {
			$this->_bindModel($model, $relation);
		}

		$select = $model->select()
			->where('id = ?', $primaryKey)
		;

		$row = $model->fetchRow($select);
		$model->unbindAllModels();
		return $row;
	}

	protected function _getModelNamespace() {
		$namespace = APPLICATION_ENV === 'testing'
			? 'Mocks_Model_'
			: 'Model_'
		;

		return $namespace;
	}

	protected function _bindModel(Garp_Model_Db $model, array $relationConfig) {
		$relatedModelClass	= $this->_getModelClass($relationConfig);
		$params 			= $this->_getParams($relationConfig);

		$relatedModel 		= new $relatedModelClass();
		$relatedBehavior 	= $relatedModel->getObserver('Elasticsearchable');

		//	do not bind this model, if it doesn't display the Elasticsearchable behavior.
		if (!$relatedBehavior) {
			return;
		}

		$model->bindModel($relationConfig['name'], $params);
	}

	protected function _getParams(array $relationConfig) {
		$namespace 			= $this->_getModelNamespace();
		$relatedModelClass	= $this->_getModelClass($relationConfig);

		$params 			= array(
			'modelClass' 	=> $relatedModelClass,
			'rule' 			=> $relationConfig['name']
		);

		if ($relationConfig['type'] === 'hasMany') {
			$params['rule'] = $relationConfig['oppositeRule'];
		}

		if ($relationConfig['type'] === 'hasAndBelongsToMany') {
			$bindingModelName 		= $this->_getBindingModelName($relationConfig);
			$bindingModelClass 		= $namespace . $bindingModelName;
			$params['bindingModel'] = $bindingModelClass;
		}

		// $params['conditions'] = $this->_getBindConditions($relationConfig);

		// Zend_Debug::dump($params['conditions']->__toString()); exit;
		return $params;
	}

	protected function _getBindConditions(array $relationConfig) {
		$relatedModelClass	= $this->_getModelClass($relationConfig);
		$relatedModel = new $relatedModelClass();

		$relatedTable = $relationConfig['type'] === 'hasAndBelongsToMany'
			? array('m' => $relatedModel->getName())
			: $relatedModel->getName()
		;

		$columnNames = array('name');
		$columns = array();

		foreach ($columnNames as $columnName) {
			$columnAlias = $relationConfig['name'] . '_' . $columnName;
			$columns[$columnAlias] = $columnName;
		}

		$columns[] = 'id';
		$columns[] = 'name';

		$select = $relatedModel->select()
			->from($relatedTable, $columns)
		;

		return $select;
	}

	// protected function _getRelatedColumns() {

	// }

	protected function _getModelClass(array $relationConfig) {
		$namespace = $this->_getModelNamespace();
		$relatedModelClass = $namespace . $relationConfig['model'];

		return $relatedModelClass;
	}

	protected function _getBindingModelName(array $relationConfig) {
		$modelNames = array(
			$relationConfig['oppositeRule'],
			$relationConfig['model']
		);
		sort($modelNames);
		$bindingModelName = implode($modelNames);

		return $bindingModelName;
	}
}