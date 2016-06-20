<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Spawn_Behavior_Type_Truncatable extends Garp_Spawn_Behavior_Type_Abstract {

    static public function isNeededBy(Garp_Spawn_Model_Abstract $model) {
        return true;
    }

    /**
     * @return  Bool    Whether this behavior needs to be registered with an observer
     *                  called in the PHP model's init() method
     */
    public function needsPhpModelObserver() {
        return count(array_filter($this->getModel()->fields->getFields(),
            $this->_getArrayFilterForTruncatableFields())) > 0;
    }

    public function getParams() {
        $textFields = array_filter($this->getModel()->fields->getFields(),
            $this->_getArrayFilterForTruncatableFields());

        $columns = array();
        foreach ($textFields as $textField) {
            $columns[$textField->name] = $textField->maxLength;
        }

        $params = array('columns' => $columns);
        return $params;
    }

    protected function _getArrayFilterForTruncatableFields() {
        return function(Garp_Spawn_Field $field) {
            return $field->isTextual() && $field->maxLength;
        };
    }
}
