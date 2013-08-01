<?php
/**
 * @author David Spreekmeester | grrr.nl
 * @pattern Singleton
 * A set of abstract models.
 */
class Garp_Spawn_Model_Set extends ArrayObject {
	private static $_instance = null;


	public static function getInstance(Garp_Spawn_Config_Model_Set $config = null) {
		if (!self::$_instance) {
			self::$_instance = self::_createInstance($config);
			self::_addMirroredRelations();
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

	protected static function _addMirroredRelations() {
		foreach (self::$_instance as $model) {
			$model->relations->addMirrored();
		}
	}
	
	protected function _sortModels() {
		ArrayObject::ksort($this);
	}
}

