<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Spawn_Behavior_Type_Truncatable extends Garp_Spawn_Behavior_Type_Abstract {

	static public function isNeededBy(Garp_Spawn_Model_Abstract $model) {
		return true;
	}

	/**
	 * @return 	Bool 	Whether this behavior needs to be registered with an observer
	 * 					called in the PHP model's init() method
	 */
	public function needsPhpModelObserver() {
		return true;
	}

	public function getParams() {
		$filterTruncatable = function(Garp_Spawn_Field $field) {
			return 
				$field->isTextual() &&
				$field->maxLength
			;
		};

		$textFields = array_filter(
			$this->getModel()->fields->getFields(),
			$filterTruncatable
		); 

		$columns = array();
		foreach ($textFields as $textField) {
			$columns[$textField->name] = $textField->maxLength;
		}

		$params = array('columns' => $columns);
		return $params;
	}
}
