<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Spawn_Behavior_Type_NotEmpty extends Garp_Spawn_Behavior_Type_Abstract {	
	
	/**
	 * In translated models (i18n leaves), multilingual columns should not be mandatory on PHP validator level.
	 */
	public function getParams() {
		$model 	= $this->getModel();		
		$params = $this->_getFieldNames();
		
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
	
	protected function _getFieldNames() {
		$requiredFieldNames = $this->_getRequiredFieldNames();
		if (!$requiredFieldNames) {
			return;
		}

		$fieldNames = $requiredFieldNames;
		$indexOfIdColumn = array_search('id', $fieldNames);
		unset($fieldNames[$indexOfIdColumn]);
		
		return $fieldNames;
	}
	
	protected function _getRequiredFieldNames() {
		$model = $this->getModel();

		if (!$model->isMultilingual()) {
			return $this->_model->fields->getFieldNames('required', true);
		}

		return $this->_getUnilingualFieldNames();
	}
	
	protected function _getUnilingualFieldNames() {
		$unilingualFieldNames 	= array();
		$requiredFields 		= $this->_model->fields->getFields('required', true);
		
		foreach ($requiredFields as $field) {
			if (!$field->isMultilingual()) {
				$unilingualFieldNames[] = $field->name;
			}
		}
		
		return $unilingualFieldNames;
	}
	
}