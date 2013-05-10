<?php
/**
 * Generated PHP model
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Model
 * @lastmodified $Date: $
 */
class Garp_Model_Spawn_Php_Renderer {
	const _BASE_MODEL_PATH 		= '/modules/default/models/Base/';
	const _EXTENDED_MODEL_PATH 	= '/modules/default/models/';
		
	/**
	 * @var Garp_Model_Spawn_Model_Abstract $_model
	 */
	protected $_model;
	


	public function __construct(Garp_Model_Spawn_Model_Abstract $model) {
		$this->setModel($model);
	}


	public function save() {
		//	generate base model
		$baseModelPath = $this->_getBaseModelPath($this->_model->id);
		$baseModelContent = $this->_renderBaseModel();
		$this->_saveFile($baseModelPath, $baseModelContent, 'PHP base model', true);

		//	generate extended model
		$extendedModelPath = $this->_getExtendedModelPath($this->_model->id);
		$extendedModelContent = $this->_renderExtendedModel($this->_model);
		$this->_saveFile($extendedModelPath, $extendedModelContent, 'PHP extended model', false);

		//	generate hasAndBelongsToMany binding models that relate to this model
		$habtmRelations = $this->_model->relations->getRelations('type', 'hasAndBelongsToMany');
		if ($habtmRelations) {
			foreach ($habtmRelations as $habtmRelation) {
				$bindingModel 				= $habtmRelation->getBindingModel();
				$bindingBaseModelPath 		= $this->_getBaseModelPath($bindingModel->id);
				$bindingBaseModelContent 	= $this->_renderBindingBaseModel($this->_model, $habtmRelation);

				$status = 'PHP base binding model to ' . $habtmRelation->model;
				$this->_saveFile($bindingBaseModelPath, $bindingBaseModelContent, $status, true);
				
				$bindingExtendedModelPath = $this->_getExtendedModelPath($bindingModel->id);
				$bindingExtendedModelContent = $this->_renderExtendedModel($bindingModel);

				$status = 'PHP extended binding model to ' . $habtmRelation->model;
				$this->_saveFile($bindingExtendedModelPath, $bindingExtendedModelContent, $status, false);
			}
		}

		new Garp_Model_Spawn_Php_ModelsIncluder($this->_model->id);
	}
		
	/**
	 * @return Garp_Model_Spawn_Model_Abstract
	 */
	public function getModel() {
		return $this->_model;
	}
	
	/**
	 * @param Garp_Model_Spawn_Model_Abstract $model
	 */
	public function setModel($model) {
		$this->_model = $model;
	}
	
	protected function _saveFile($path, $content, $label, $overwrite = false) {
		if (
			$overwrite ||
			!$overwrite && !file_exists($path)
		) {
			if (!file_put_contents($path, $content)) {
				throw new Exception("Could not generate {$label}.");
			}
		}
	}


	protected static function _getBaseModelPath($modelId) {
		return APPLICATION_PATH.self::_BASE_MODEL_PATH.$modelId.'.php';
	}
	
	
	protected static function _getExtendedModelPath($modelId) {
		return APPLICATION_PATH.self::_EXTENDED_MODEL_PATH.$modelId.'.php';
	}

	protected function _renderBaseModel() {
		$model		= $this->getModel();
		$phpModel 	= new Garp_Model_Spawn_Php_Model_Base($model);
		$script 	= $phpModel->render();
		
		return $script;
	}
		
	protected function _getTableName() {
		$model 			= $this->getModel();
		$tableFactory 	= new Garp_Model_Spawn_MySql_Table_Factory($model);
		$table 			= $tableFactory->produceConfigTable();
		
		return $table->name;
	}

	protected function _renderExtendedModel(Garp_Model_Spawn_Model_Abstract $model) {
		$model		= $this->getModel();
		$phpModel 	= new Garp_Model_Spawn_Php_Model_Extended($model);
		$script 	= $phpModel->render();
		
		return $script;
	}
	
	
	protected function _renderBindingBaseModel(Garp_Model_Spawn_Model_Base $model, Garp_Model_Spawn_Relation $habtmRelation) {
		$model		= $this->getModel();
		$phpModel 	= new Garp_Model_Spawn_Php_Model_BindingBase($model, $habtmRelation);
		$script 	= $phpModel->render();
		
		return $script;
	}

	/**
	 * Render line with tabs and newlines
	 */
	protected function _rl($content, $tabs = 0, $newlines = 1) {
		return str_repeat("\t", $tabs).$content.str_repeat("\n", $newlines);
	}
}
