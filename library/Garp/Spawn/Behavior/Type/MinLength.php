<?php
/**
 * Garp_Spawn_Behavior_Type_MinLength
 * Adds minlength validation to columns
 *
 * @package Garp_Spawn_Behavior_Type
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Spawn_Behavior_Type_MinLength extends Garp_Spawn_Behavior_Type_Abstract {

    static public function isNeededBy(Garp_Spawn_Model_Abstract $model) {
        return count(array_filter($model->fields->toArray(), getProperty('minLength')));
    }

    public function getParams() {
        $fields = array_filter($this->getModel()->fields->toArray(), getProperty('minLength'));
        return array_combine(
            array_map(getProperty('name'), $fields),
            array_map(getProperty('minLength'), $fields)
        );
    }

}
