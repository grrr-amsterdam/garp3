<?php
/**
 * Produces a Garp_Spawn_Model_Binding instance.
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Spawn_Model_Binding_Factory {
	const ERROR_GET_BINDING_MODEL_WRONG_RELATION_TYPE =
		"You can only produce Binding Models from hasAndBelongsToMany relations.";

	/** 
 	 * @var Garp_Spawn_Relation $_relation The direct relation to the end model, which hops over the HABTM relation.
 	 */
	protected $_relation;

	/**
	 * Create a Garp_Spawn_Model_Binding by providing a hasAndBelongsToMany $relation instance.
	 * @param Garp_Spawn_Relation $relation
	 */
	public function produceByRelation(Garp_Spawn_Relation $relation) {
		$this->setRelation($relation);

		if ($relation->type !== 'hasAndBelongsToMany') {
			throw new Exception(self::ERROR_GET_BINDING_MODEL_WRONG_RELATION_TYPE);
		}

		$bindingModelConfig = $this->_getBindingModelConfig();
		$model = new Garp_Spawn_Model_Binding($bindingModelConfig);

		return $model;
	}

	public function getRelation() {
		return $this->_relation;
	}

	public function setRelation(Garp_Spawn_Relation $relation) {
		$this->_relation = $relation;
	}

	/**
	 * @return Garp_Spawn_Config_Model_Binding
	 */
	protected function _getBindingModelConfig() {
		$relation = $this->getRelation();

		$habtmModelId = $this->_getBindingModelName();
		$rules = $this->_getRules();
		$config = $this->_getBindingModelParams();

		if ($relation->weighable) {
			$weightCol1 = Garp_Spawn_Util::camelcased2underscored($rules[0] . $rules[1]) . '_weight';
			$weightCol2 = Garp_Spawn_Util::camelcased2underscored($rules[1] . $rules[0]) . '_weight';
			$config['inputs'][$weightCol1] = array('type' => 'numeric');
			$config['inputs'][$weightCol2] = array('type' => 'numeric');
		}

		$bindingModelConfig = new Garp_Spawn_Config_Model_Binding(
			$habtmModelId,
			new Garp_Spawn_Config_Storage_PhpArray(array($habtmModelId => $config)),
			new Garp_Spawn_Config_Format_PhpArray
		);

		return $bindingModelConfig;
	}

	/**
 	 * Order in such a way that the reference to the referring model comes before the referred model.
 	 */
	protected function _areRulesEgocentricallySorted(array $ruleNames) {
		$relation = $this->getRelation();
		$localModel = $relation->getLocalModel();
		return $ruleNames[0] === $localModel->id;
	}

	protected function _sortRulesEgocentrically(array $ruleNames) {
		if (!$this->_areRulesEgocentricallySorted($ruleNames)) {
			$ruleNames = array_reverse($ruleNames);
		}

		return $ruleNames;
	}

	/**
 	 * Returns relation rules, sorted egocentrically.
 	 * @return Array First rule refers to the model itself, second to the related model referenced in the $relation object.
 	 */
	protected function _getRules() {
		$relation = $this->getRelation();
		$localModel = $relation->getLocalModel();

		$hasNameConflict = $relation->name === $localModel->id;
		if ($hasNameConflict) {
			return array($relation->name . '1', $relation->name . '2');
		}

		$modelIds = $this->_getModelIdsAlphabetically();

		$rules = array($relation->name, $this->_getSecondRule());
		$rules = $this->_sortRulesEgocentrically($rules);

		return $rules;
	}

	/**
 	 * Displays second HABTM rule. The first rule is always the name of the direct relation to the end model.
 	 */
	protected function _getSecondRule() {
		$relation = $this->getRelation();
		$firstRule = $relation->name;
		$hasCustomRelName = $this->_hasCustomRelName();
		$modelIds = $this->_getModelIdsAlphabetically();

		if ($hasCustomRelName) {
			return $modelIds[0];
		}

		return $modelIds[0] !== $firstRule
			? $modelIds[0]
			: $modelIds[1]
		;
	}

	protected function _hasCustomRelName() {
		$relation = $this->getRelation();
		$modelIds = $this->_getModelIdsAlphabetically();

		return !in_array($relation->name, $modelIds);
	}

	protected function _getModelIds() {
		$relation = $this->getRelation();
		$localModel = $relation->getLocalModel();
		$modelIds = array($localModel->id, $relation->model);

		return $modelIds;
	}

	protected function _getModelIdsAlphabetically() {
		$modelIds = $this->_getModelIds();
		sort($modelIds);

		return $modelIds;
	}

	protected function _getModelIdsByRuleSort() {
		$rules = $this->_getRules();
		$modelIds = $this->_getModelIds();
		$reverse = !$this->_areModelIdsSortedByRule($modelIds);

		if ($reverse) {
			$modelIds = array_reverse($modelIds);
		}

		return $modelIds;
	}

	/**
 	 * Whether modelId sorting is coherent with the egocentric sorting of rules.
 	 * @return Boolean
 	 */
	protected function _areModelIdsSortedByRule(array $modelIds) {
		$rules = $this->_getRules();

		foreach ($modelIds as $modelId) {
			$ruleNumber = array_search($modelId, $rules);
			if ($ruleNumber !== false) {
				return $rules[$ruleNumber] === $modelIds[$ruleNumber];
			}
		}
	}

	protected function _getBindingModelName() {
		$relation = $this->getRelation();
		$modelNames = $this->_getModelIdsAlphabetically();

		$bindingModelName = !$this->_hasCustomRelName() 
			// Rule name refers to one of the related models, so no custom relation key
			? $modelNames[0] . $modelNames[1]
			// Custom relation key
			: $modelNames[0] . $relation->name
		;

		return $bindingModelName;
	}

	protected function _getBindingModelParams() {
		$rules = $this->_getRules();

		$relation = $this->getRelation();
		$localModel = $relation->getLocalModel();
		$models = $this->_getModelIdsByRuleSort();
	
		$config = array(
			'listFields' => $relation->column,
			'inputs' => $relation->inputs ?: array(),
			'relations' => array(
				$rules[0] => array(
					'type' => 'belongsTo',
					'model' => $models[0]
				),
				$rules[1] => array(
					'type' => 'belongsTo',
					'model' => $models[1]
				)
			)
		);

		return $config;
	}
}
