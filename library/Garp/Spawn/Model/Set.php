<?php
/**
 * @author David Spreekmeester | grrr.nl
 * @pattern Singleton
 * A set of abstract models.
 */
class Garp_Spawn_Model_Set extends ArrayObject {
	const ERROR_RELATION_TO_NON_EXISTING_MODEL = "The '%s' model defines a %s relation to unexisting model '%s'.";

	private static $_instance = null;

	/**
	 * @todo: deze default relations moeten naar de configlaag verplaatst worden.
	 */
	protected $_defaultRelations = array(
		'Author' => array(
			'model' => 'User',
			'type' => 'hasOne',
			'inverse' => false,
			'label' => 'Created by'
		),
		'Modifier' => array(
			'model' => 'User',
			'type' => 'hasOne',
			'inverse' => false,
			'editable' => false,
			'label' => 'Modified by'
		)
	);


	public static function getInstance(Garp_Spawn_Config_Model_Set $config = null) {
		if (!self::$_instance) {
			self::$_instance = self::_createInstance($config);
		}
		
		return self::$_instance;
	}
	
	private static function _createInstance(Garp_Spawn_Config_Model_Set $config = null) {
		if (!$config) {
			$config = new Garp_Spawn_Config_Model_Set();
		}
	
		return new Garp_Spawn_Model_Set($config);
	}

	/**
	 * Use Garp_Spawn_Model_Set::getInstance() instead, for performance.
	 */
	public function __construct(Garp_Spawn_Config_Model_Set $modelSetConfig) {
		foreach ($modelSetConfig as $modelId => $modelConfig) {
			$this[$modelId] = new Garp_Spawn_Model_Base($modelConfig);
		}

		$this->_sortModels();
		$this->_defineDefaultRelations();
		$this->_mirrorHabtmRelationsInSet();
		$this->_mirrorHasManyRelationsInSet();

	}


	public function materializeCombinedBaseModel() {
		$output = '';
		foreach ($this as $model) {
			$output .= $model->renderJsBaseModel($this);
		}

		$modelSetFile = new Garp_Spawn_Js_ModelSet_File_Base();
		$modelSetFile->save($output);
	}
	
	
	public function includeInJsModelLoader() {
		new Garp_Spawn_Js_ModelsIncluder($this);
	}
	
	protected function _defineDefaultRelations() {
		foreach ($this as &$model) {
			foreach ($this->_defaultRelations as $defRelName => $defRelParams) {
				if (!count($model->relations->getRelations('name', $defRelName))) {
					$model->relations->add($defRelName, $defRelParams);
				}
			}
		}
	}

	protected function _mirrorHasManyRelationsInSet() {
		//	inverse singular relations to multiple relations from the other model
		foreach ($this as $model) {
			$this->_mirrorHasManyRelationsInOpposingModels($model);
		}
	}
	
	protected function _mirrorHasManyRelationsInOpposingModels(Garp_Spawn_Model_Base $model) {
		$singularRelations = $model->relations->getSingularRelations();

		foreach ($singularRelations as $relationName => $relation) {
			if (!$relation->inverse) {
				break;
			}

			$this->_mirrorRelationsInModel($model, $relation);
		}
	}

	protected function _mirrorHabtmRelationsInSet() {
		//	inverse singular relations to multiple relations from the other model
		foreach ($this as $model) {
			$this->_mirrorHabtmRelationsInOpposingModels($model);
		}
	}
	
	protected function _mirrorHabtmRelationsInOpposingModels(Garp_Spawn_Model_Base $model) {
		$habtmRelations = $model->relations->getRelations('type', array('hasAndBelongsToMany'));

		foreach ($habtmRelations as $relationName => $relation) {
			$this->_mirrorRelationsInModel($model, $relation);
		}
		
	}
	
	protected function _mirrorRelationsInModel(Garp_Spawn_Model_Base $model, Garp_Spawn_Relation $relation) {
		$this->_throwErrorIfRelatedModelDoesNotExist($model, $relation);

		$remoteModel = &$this[$relation->model];
		$mirroredRelation = $relation->mirror($remoteModel);
		$remoteModel->relations->addRaw($mirroredRelation);
	}
	
	protected function _throwErrorIfRelatedModelDoesNotExist(Garp_Spawn_Model_Base $model, Garp_Spawn_Relation $relation) {
		if (!array_key_exists($relation->model, $this)) {
			$error = sprintf(
				self::ERROR_RELATION_TO_NON_EXISTING_MODEL,
				$model->id,
				$relation->type,
				$relation->model
			);
			throw new Exception($error);
		}
	}
	
	protected function _sortModels() {
		ArrayObject::ksort($this);
	}
}

