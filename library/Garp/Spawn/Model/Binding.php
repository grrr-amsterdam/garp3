<?php
/**
 * @author David Spreekmeester | grrr.nl
 * This class can be produced by Garp_Spawn_Model_Binding_Factory.
 */
class Garp_Spawn_Model_Binding extends Garp_Spawn_Model_Abstract {
	public function __construct(Garp_Spawn_Config_Model_Binding $config) {
		parent::__construct($config);

		$this->_setRelationFieldsAsPrimary();
	}

	protected function _setRelationFieldsAsPrimary() {
		$relFields = $this->fields->getFields('origin', 'relation');
		foreach ($relFields as $field) {
			$this->fields->alter($field->name, 'primary', true);
		}
	}
}