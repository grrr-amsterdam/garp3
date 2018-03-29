<?php
/**
 * @package Garp_Spawn_Behavior_Type
 * @author Ramiro Hammen <ramiro@grrr.nl>
 */
class Garp_Spawn_Behavior_Type_Nullable extends Garp_Spawn_Behavior_Type_Abstract {

    public function getParams() {
        $model = $this->getModel();
        return array(
            'nullableFields' => $model->fields->getFieldNames('required', false)
        );
    }

    static public function isNeededBy(Garp_Spawn_Model_Abstract $model) {
        return count($model->fields->getFieldNames('required', false)) > 0;
    }
}