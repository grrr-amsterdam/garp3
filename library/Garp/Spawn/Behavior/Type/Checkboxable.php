<?php

use Garp\Functional as f;

/**
 * Garp_Spawn_Behavior_Type_Checkboxable
 * class description
 *
 * @package Garp_Spawn_Behavior_Type
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Spawn_Behavior_Type_Checkboxable extends Garp_Spawn_Behavior_Type_Abstract {
    static public function isNeededBy(Garp_Spawn_Model_Abstract $model) {
        return !!count(self::_getCheckboxesFromModel($model));
    }

    public function needsPhpModelObserver() {
        return true;
    }

    public function getParams() {
        $checkboxes = self::_getCheckboxesFromModel($this->getModel());
        return array('columns' => array_map(f\prop('name'), $checkboxes));
    }

    static protected function _getCheckboxesFromModel(Garp_Spawn_Model_Abstract $model) {
        return $model->fields->getFields('type', 'checkbox');
    }
}
