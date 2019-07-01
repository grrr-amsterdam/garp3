<?php

use Garp\Functional as f;

/**
 * Garp_Spawn_Behavior_Type_MinLength
 * Adds minlength validation to columns
 *
 * @package Garp_Spawn_Behavior_Type
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Spawn_Behavior_Type_MinLength extends Garp_Spawn_Behavior_Type_Abstract {

    static public function isNeededBy(Garp_Spawn_Model_Abstract $model) {
        return count(array_filter($model->fields->toArray(), f\prop('minLength')));
    }

    public function getParams() {
        $fields = array_filter($this->getModel()->fields->toArray(), f\prop('minLength'));
        return array_combine(
            array_map(f\prop('name'), $fields),
            array_map(f\prop('minLength'), $fields)
        );
    }

}
