<?php
/**
 * Garp_Model_Behavior_Set
 * Normalizes mysql `set` values
 *
 * @package Garp_Model_Behavior
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Model_Behavior_Set extends Garp_Model_Behavior_Core {
    protected $_executionPosition = self::EXECUTE_LAST;

    public function beforeInsert(&$args) {
        $model = &$args[0];
        $data  = &$args[1];
        $data = $this->_beforeSave($data);
    }

    public function beforeUpdate(&$args) {
        $model = &$args[0];
        $data  = &$args[1];
        $where = &$args[2];
        $data = $this->_beforeSave($data);
    }

    protected function _beforeSave(array $data) {
        $relevantKeys = array_intersect(array_keys($data), $this->_config['columns']);
        foreach ($relevantKeys as $key) {
            $data[$key] = $this->_normalizeSetValue($data[$key]);
        }
        return $data;
    }

    protected function _normalizeSetValue($value) {
        if (!is_array($value)) {
            return $value;
        }
        return implode(
            ',',
            $value
        );
    }

    protected function _setup($config) {
        if (!array_key_exists('columns', $config)) {
            throw new Garp_Model_Behavior_Exception('Missing required config key "columns"');
        }
        return parent::_setup($config);
    }

}


