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
	const ERROR_NO_EXTRACTABLE_ID =
		'Could not extract the database id from the \'where\' query that was used in deleting this record.';
	const ERROR_RELATION_NOT_FOUND =
		'Could not find relation "%s" in model "%s".';

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

	protected function _afterSave(Garp_Model_Db $model, $primaryKey, $data) {
		if (is_array($primaryKey)) {
			throw new Exception(self::ERROR_PRIMARY_KEY_CANNOT_BE_ARRAY);
		}

		$rowSetObj = $this->_fetchRow($model, $primaryKey);
		if (!$rowSetObj) {
			/*	this is not supposed to happen,
			but due to concurrency it theoretically might. */
			return;
		}

		$row 				= $rowSetObj->current()->toArray();
		$filteredRow 		= $this->_filterRow($row, $model);

		$elasticModel 		= $this->_getElasticModel($model);
		$pkMash				= $this->_mashPrimaryKey($primaryKey);
		$filteredRow['id']	= $pkMash;

		$elasticModel->save($filteredRow);
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

		$row = $model->fetchAll($select);
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
		$namespace = $this->_getModelNamespace();

		$relatedModelClass 	= $namespace . $relationConfig['model'];
		$params 			= array(
			'modelClass' => $relatedModelClass,
			'rule' => $relationConfig['name']
		);

		if ($relationConfig['type'] === 'hasMany') {
			$params['rule'] = $relationConfig['oppositeRule'];
		}

		$relatedModel 		= new $relatedModelClass();
		$relatedBehavior 	= $relatedModel->getObserver('Elasticsearchable');

		//	do not bind this model, if it doesn't display the Elasticsearchable behavior.
		if (!$relatedBehavior) {
			return;
		}

		if ($relationConfig['type'] === 'hasAndBelongsToMany') {
			$bindingModelName 		= $this->_getBindingModelName($relationConfig);
			$bindingModelClass 		= $namespace . $bindingModelName;
			$params['bindingModel'] = $bindingModelClass;
		}

		$model->bindModel($relationConfig['name'], $params);
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