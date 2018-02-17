<?php
/**
 * This class can be produced by Garp_Spawn_Model_Binding_Factory.
 *
 * @package Garp_Spawn_Model
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Spawn_Model_Binding extends Garp_Spawn_Model_Abstract {

    public function __construct(Garp_Spawn_Config_Model_Binding $config) {
        parent::__construct($config);

        $this->_setRelationFieldsAsPrimary();
    }

    public function getTableName(): string {
        return '_' . $this->id;
    }

    public function getTableClassName(): string {
        return 'Garp_Spawn_Db_Table_Binding';
    }

    protected function _setRelationFieldsAsPrimary() {
        $relFields = $this->fields->getFields('origin', 'relation');
        foreach ($relFields as $field) {
            $this->fields->alter($field->name, 'primary', true);
        }
    }

}
