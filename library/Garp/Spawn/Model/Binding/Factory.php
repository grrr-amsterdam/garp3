<?php
/**
 * Produces a Garp_Spawn_Model_Binding instance.
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Spawn_Model_Binding_Factory {
	const ERROR_GET_BINDING_MODEL_WRONG_RELATION_TYPE =
		"You can only produce Binding Models from hasAndBelongsToMany relations.";

	/**
	 * Create a Garp_Spawn_Model_Binding by providing a hasAndBelongsToMany $relation instance.
	 * @param Garp_Spawn_Relation $relation
	 */
	public function produceByRelation(Garp_Spawn_Relation $relation) {
		if ($relation->type !== 'hasAndBelongsToMany') {
			throw new Exception(self::ERROR_GET_BINDING_MODEL_WRONG_RELATION_TYPE);
		}

		$bindingModelConfig = $this->_getBindingModelConfig($relation);
		$model = new Garp_Spawn_Model_Binding($bindingModelConfig);

		return $model;
	}

	protected function _getBindingModelRelNames(Garp_Spawn_Relation $relation) {
		$localModel = $relation->getLocalModel();

		$hasNameConflict	= $relation->name === $localModel->id;
		$relLabel1			= $hasNameConflict ? $relation->name . '1' : $relation->name;
		$relLabel2 			= $hasNameConflict ? $relation->name . '2' : $localModel->id;
		/////////////
		var_dump($relation->type);
		var_dump($localModel->id);
		var_dump($relation->name);
		var_dump($relation->oppositeRule);
		var_dump($relation->model);
		var_dump($relLabel1);
		var_dump($relLabel2);
		var_dump('____________');
		///////////
		return array($relLabel1, $relLabel2);
	}

	/**
	 * @return Garp_Spawn_Config_Model_Binding
	 */
	protected function _getBindingModelConfig(Garp_Spawn_Relation $relation) {
		$habtmModelId = $this->_getBindingModelName($relation);
		list($relLabel1, $relLabel2) = $this->_getBindingModelRelNames($relation);
		$config = $this->_getBindingModelParams($relation, $relLabel1, $relLabel2);

		if ($relation->weighable) {
			$weightCol1 = Garp_Spawn_Util::camelcased2underscored($relLabel1 . $relLabel2) . '_weight';
			$weightCol2 = Garp_Spawn_Util::camelcased2underscored($relLabel2 . $relLabel1) . '_weight';
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

	protected function _getBindingModelName(Garp_Spawn_Relation $relation) {
		$modelNames = array($relation->getLocalModel()->id, $relation->model);
		sort($modelNames);

		$bindingModelName = in_array($relation->name, $modelNames) 
			// Rule name refers to one of the related models, so no custom relation key
			? $modelNames[0] . $modelNames[1]
			// Custom relation key
			: $modelNames[0] . $relation->name
		;

		return $bindingModelName;
	}

	protected function _getBindingModelParams(Garp_Spawn_Relation $relation, $relLabel1, $relLabel2) {
		$localModel = $relation->getLocalModel();
	
		$config = array(
			'listFields' => $relation->column,
			'inputs' => $relation->inputs ?: array(),
			'relations' => array(
				$relLabel1 => array(
					'type' => 'belongsTo',
					'model' => $relation->model
				),
				$relLabel2 => array(
					'type' => 'belongsTo',
					'model' => $localModel->id
				)
			)
		);

		return $config;
	}

}
