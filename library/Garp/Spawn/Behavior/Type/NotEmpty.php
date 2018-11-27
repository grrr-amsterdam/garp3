<?php
use Garp\Functional as f;

/**
 * @package Garp3
 * @author  David Spreekmeester <david@grrr.nl>
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Spawn_Behavior_Type_NotEmpty extends Garp_Spawn_Behavior_Type_Abstract {

    static public function isNeededBy(Garp_Spawn_Model_Abstract $model): bool {
        return count(static::_getRequiredFields($model)) > 0;
    }

    static private function _getRequiredFields(Garp_Spawn_Model_Abstract $model): array {
        $requiredFieldNames = $model->fields->getFields('required', true);
        return f\reject(
            function ($field) use ($model) {
                $isInvalid = $field->name === 'id'
                    || isset($field->default)
                    || ($model->isMultilingual() || !$field->isMultilingual());
                return $isInvalid;
            },
            $requiredFieldNames
        );
    }

    /**
     * In translated models (i18n leaves), multilingual columns should not be
     * mandatory on PHP validator level.
     * Nor will field "id", or any field with a default value.
     *
     * @return array
     */
    public function getParams(): array {
        return f\map(
            f\prop('name'),
            static::_getRequiredFields($this->_model)
        );
    }

}

