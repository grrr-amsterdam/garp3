<?php
/**
 * Garp_Service_Elasticsearch_Db_BoundModel
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Elasticsearch
 * @lastmodified $Date: $
 */
class Garp_Service_Elasticsearch_Db_BoundModel extends Garp_Service_Elasticsearch_Db_Abstract {

	/**
	 * @var Garp_Db_Model $_model
	 */
	protected $_model;
	
	public function __construct(Garp_Model_Db $model) {
		$this->setModel($model);
	}

	public function fetchRow($primaryKey) {
		$model 		= $this->getModel();
		$relations 	= $model->getConfiguration('relations');

		foreach ($relations as $relation) {
			$this->_bindModel($relation);
		}

		$select = $model->select()
			->where('id = ?', $primaryKey)
		;

		$row = $model->fetchRow($select);
		$model->unbindAllModels();

		return $row;
	}

	/**
	 * @return Garp_Db_Model
	 */
	public function getModel() {
		return $this->_model;
	}
	
	/**
	 * @param Garp_Db_Model $model
	 */
	public function setModel($model) {
		$this->_model = $model;
		return $this;
	}

	protected function _bindModel(array $relationConfig) {
		$model 				= $this->getModel();
		$relatedModelClass	= $this->_getModelClass($relationConfig);
		$params 			= $this->_getParams($relationConfig);

		$relatedModel 		= new $relatedModelClass();
		$relatedBehavior 	= $this->_getRelatedBehavior($relatedModel);

		//	do not bind this model if it doesn't display the Elasticsearchable behavior.
		if (!$relatedBehavior) {
			return;
		}

		$model->bindModel($relationConfig['name'], $params);
	}

	protected function _getRelatedBehavior(Garp_Model_Db $relatedModel) {
		$relatedBehavior = $relatedModel->getObserver('Elasticsearchable');
		return $relatedBehavior;
	}

	protected function _getModelClass(array $relationConfig) {
		$namespace = $this->_getModelNamespace();
		$relatedModelClass = $namespace . $relationConfig['model'];

		return $relatedModelClass;
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

		if ($conditions = $this->_getBindConditions($relationConfig)) {
			$params['conditions'] = $conditions;
		}

		return $params;
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

	protected function _getBindConditions(array $relationConfig) {
		$relatedModelClass	= $this->_getModelClass($relationConfig);
		$relatedModel 		= new $relatedModelClass();

		$relatedTable = $relationConfig['type'] === 'hasAndBelongsToMany'
			? array('m' => $relatedModel->getName())
			: $relatedModel->getName()
		;

		$relatedBehavior 		= $this->_getRelatedBehavior($relatedModel);
		if (!$relatedBehavior) {
			return;
		}

		$columnNames 			= $relatedBehavior->getColumns();
		$columnNamesWithAliases = $this->_getColumnNamesWithAliases($relationConfig['name'], $columnNames);

		$select = $relatedModel->select()
			->from($relatedTable, $columnNamesWithAliases)
		;

		return $select;
	}

	protected function _getColumnNamesWithAliases($relationName, $columnNames) {
		$prefixedColumnNames 	= $this->_prefixColumnsWithRelationNamespace($relationName, $columnNames);
		$columnNamesWithAliases = array_combine($prefixedColumnNames, $columnNames);

		return $columnNamesWithAliases;
	}

}