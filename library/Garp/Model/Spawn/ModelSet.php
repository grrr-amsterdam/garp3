<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Model_Spawn_ModelSet extends ArrayObject {

	public function __construct(Garp_Model_Spawn_Config_Model_Set $modelSetConfig) {
		foreach ($modelSetConfig as $modelId => $modelConfig) {
			$this[$modelId] = new Garp_Model_Spawn_Model($modelConfig);
		}
		
		ArrayObject::ksort($this);

		Garp_Model_Spawn_Relations::defineDefaultRelations($this);
		Garp_Model_Spawn_Relations::defineHasAndBelongsToMany($this);
		Garp_Model_Spawn_Relations::defineHasMany($this);
	}


	public function materializeCombinedBaseModel() {
		$output = '';
		foreach ($this as $model) {
			$output .= $model->renderBaseModel($this);
		}

		$modelSetFile = new Garp_Model_Spawn_Js_ModelSet_File_Base();
		$modelSetFile->save($output);
	}
	
	
	public function includeInJsModelLoader() {
		new Garp_Model_Spawn_Js_ModelsIncluder($this);
	}
}