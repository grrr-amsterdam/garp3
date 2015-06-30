<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Spawn_Behavior_Type_Translatable extends Garp_Spawn_Behavior_Type_Abstract {

	static public function isNeededBy(Garp_Spawn_Model_Abstract $model) {
		return $model->isMultilingual();
	}

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

		$fieldNames = array_merge(
			array_map(function($field) { return $field->name; },
				$model->fields->getFields('multilingual', true)),
			array_map(function($rel) { return $rel->column; },
				$model->relations->getRelations('multilingual', true))
		);

		$params = array('columns' => array_values($fieldNames));
		return $params;
	}
}
