<?php
/**
 * Garp_Model_Behavior_Checkboxable
 * Normalizes checkbox values
 *
 * @package Garp_Model_Behavior
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Model_Behavior_Checkboxable extends Garp_Model_Behavior_Core {
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
            $data[$key] = $this->_normalizeCheckboxValue($data[$key]);
        }
        return $data;
    }

    protected function _normalizeCheckboxValue($value) {
        if (is_int($value)) {
            // Accept only ones and zeroes. Consider any non-zero value to be one.
            return 0 === $value ? $value : 1;
        }
        // Pass anything that's not an int through `intval`.
        // Bools true or false will be converted correctly, as will "1" and "0".
        // Anything else is just... wrong.
        return $this->_normalizeCheckboxValue(intval($value));
    }

    protected function _setup($config) {
        if (!array_key_exists('columns', $config)) {
            throw new Garp_Model_Behavior_Exception('Missing required config key "columns"');
        }
        return parent::_setup($config);
    }

}

