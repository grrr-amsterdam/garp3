<?php
/**
 * @package Garp
 * @author David Spreekmeester <david@grrr.nl>
 */
class Garp_Spawn_Behavior_Type_Authorable extends Garp_Spawn_Behavior_Type_Abstract {
    /**
     * @return  Bool    Whether this behavior needs to be registered with an observer
     *                  called in the PHP model's init() method
     */
    public function needsPhpModelObserver() {
        $model = $this->getModel();

        return !$model->isTranslated();
    }

    public function getColumns() {
        $params = $this->getParams();
        return [
            $params['authorField'] ?? Garp_Model_Behavior_Authorable::_AUTHOR_COLUMN,
            $params['modifierField'] ?? Garp_Model_Behavior_Authorable::_MODIFIER_COLUMN,
        ];
    }

}

