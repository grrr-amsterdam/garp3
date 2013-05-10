<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Model_Spawn_Behavior_Type_NotEmpty extends Garp_Model_Spawn_Behavior_Type_Abstract {	
	
	/**
	 * In translated models (i18n leaves), multilingual columns should not be mandatory on PHP validator level.
	 */
	public function getParams() {
		$model 			= $this->getModel();
		$params 		= parent::getParams();
		
		if (!$model->isTranslated()) {
			return $params;
		}
		
		$params = array_filter($params, array($this, '_isUnilingualField'));
		return $params;
	}
	
	protected function _isUnilingualField($fieldName) {
		$modelField = $this->getModel()->fields->getField($fieldName);
		return !$modelField->isMultilingual();
	}
}