<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Spawn_Behavior_Type_Translatable extends Garp_Spawn_Behavior_Type_Abstract {	
	/**
	 * @return 	Bool 	Whether this behavior needs to be registered with an observer
	 * 					called in the PHP model's init() method
	 */
	public function needsPhpModelObserver() {
		$model = $this->getModel();
		
		return !$model->isTranslated();
	}
	
	public function getParams() {
		$model = $this->getModel();

		if (!$model->isMultilingual()) {
			return;
		}
		
		$fields 	= $model->fields->getFields('multilingual', true);
		$fieldNames = array();
		
		foreach ($fields as $field) {
			$fieldNames[] = $field->name;
		}
		
		$params = array('columns' => $fieldNames);
		return $params;
	}
}