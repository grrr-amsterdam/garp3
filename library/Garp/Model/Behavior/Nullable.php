<?php
use Garp\Functional as f;
/**
 * @package App_Model_Behavior
 * @author Ramiro Hammen <ramiro@grrr.nl>
 */
class Garp_Model_Behavior_Nullable extends Garp_Model_Behavior_Abstract {

    protected $_nullableFields;

    /**
     * Exception Messages
     */
    const EXCEPTION_MISSING_CONFIG = '"%a" is a required config key';

    protected function _setup($config) {
        $this->_validateConfig($config);
        $this->_nullableFields = $config['nullableFields'] ?: array();
    }

    public function beforeInsert(&$args) {
        $model = &$args[0];
        $data  = &$args[1];
        $this->_beforeSave($model, $data);
    }

    public function beforeUpdate(&$args) {
        $model = &$args[0];
        $data  = &$args[1];
        $this->_beforeSave($model, $data);
    }

    protected function _beforeSave($model, array &$data) {
        if (empty($this->_nullableFields)) {
            return;
        }

        $nullableFields = $this->_nullableFields;
        $data = f\reduce_assoc(
            function ($acc, $cur, $key) use ($nullableFields) {
                $acc[$key] = (in_array($key, $nullableFields) && $cur === '')
                    ? null : $cur;

                return $acc;
            },
            array(),
            $data
        );
    }

    protected function _validateConfig($config) {
        if (!array_key_exists('nullableFields', $config)) {
            throw new Garp_Model_Behavior_Exception(
                sprintf(self::EXCEPTION_MISSING_CONFIG, 'nullableFields')
            );
        }
    }
}