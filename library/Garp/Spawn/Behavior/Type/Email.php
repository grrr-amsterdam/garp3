<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Spawn_Behavior_Type_Email extends Garp_Spawn_Behavior_Type_Abstract {

	static public function isNeededBy(Garp_Spawn_Model_Abstract $model) {
		$emailFields = $model->fields->getFields('type', 'email');
		if (!$emailFields) {
			return false;
		}

		$emailFieldNames = array();
		foreach ($emailFields as $emailField) {
			$emailFieldNames[] = $emailField->name;
		}

		return (bool)$emailFieldNames;
	}
	
	/**
	 * In translated models (i18n leaves), multilingual columns should not be mandatory on PHP validator level.
	 */
	public function getParams() {
		$model 			= $this->getModel();		
		$emailFields 	= $model->fields->getFields('type', 'email');

		if (!$emailFields) {
			return;
		}

		$emailFieldNames = array();
		foreach ($emailFields as $field) {
			$emailFieldNames[] = $field->name;
		}

		return $emailFieldNames;
	}	
	
	
}