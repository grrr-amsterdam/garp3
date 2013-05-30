<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Spawn_Behavior_Type_HtmlFilterable extends Garp_Spawn_Behavior_Type_Abstract {	

	static public function isNeededBy(Garp_Spawn_Model_Abstract $model) {
		$htmlFieldNames = $model->fields->getFieldNames('type', 'html');
		return (bool)$htmlFieldNames;
	}
	
	/**
	 * In translated models (i18n leaves), multilingual columns should not be mandatory on PHP validator level.
	 */
	public function getParams() {
		$model 	= $this->getModel();		

		$params = $model->fields->getFieldNames('type', 'html');
		$params = array_filter($params, array($this, $model->isTranslated() ?
			'_isMultilingualField' :
			'_isUnilingualField'
		));
		
		return $params;
	}
	
	protected function _isUnilingualField($fieldName) {
		$modelField = $this->getModel()->fields->getField($fieldName);
		return !$modelField->isMultilingual();
	}

	protected function _isMultilingualField($fieldName) {
		$modelField = $this->getModel()->fields->getField($fieldName);
		return $modelField->isMultilingual();
	}
}