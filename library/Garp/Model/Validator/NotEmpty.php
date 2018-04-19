<?php
/**
 * Garp_Model_Validator_NotEmpty
 * Check if a value is not empty
 *
 * @package Garp_Model_Validator
 * @author  Harmen Janssen <harmen@grrr.nl>
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Model_Validator_NotEmpty extends Garp_Model_Validator_Abstract {

    /**
     * Columns to check for emptiness
     *
     * @var array
     */
    protected $_fields = [];

    /**
     * Setup the validation environment
     *
     * @param array $config Configuration options
     * @return void
     */
    protected function _setup($config) {
        $this->_fields = $config;
    }

    /**
     * Validate wether the given columns are not empty.
     *
     * @param array $data The data to validate
     * @param Garp_Model_Db $model The model
     * @param bool $onlyIfAvailable Wether to skip validation on fields that are not in the array
     * @return void
     * @throws Garp_Model_Validator_Exception
     */
    public function validate(array $data, Garp_Model_Db $model, $onlyIfAvailable = false) {
        foreach ($this->_fields as $field) {
            $this->_validate($field, $model, $data, $onlyIfAvailable);
        }
    }

    /**
     * Pass value along to more specific validate functions.
     *
     * @param string        $column Column value
     * @param Garp_Model_Db $model The model
     * @param array         $data
     * @param bool          $onlyIfAvailable
     * @return void
     */
    protected function _validate($column, $model, $data, $onlyIfAvailable) {
        if ($onlyIfAvailable && !array_key_exists($column, $data)) {
            return;
        }
        $value = array_key_exists($column, $data) ? $data[$column] : null;
        $colType = $this->_getColumnType($column, $model);

        if ($colType == 'numeric') {
            $this->_validateNumber($value, $column);
            return;
        }

        $this->_validateString($value, $column);
    }

    /**
     * Validate the emptiness of a string.
     *
     * @param mixed $value
     * @param string $column
     * @return void
     * @throws Garp_Model_Validator_Exception
     */
    protected function _validateString($value, $column) {
        $value = is_string($value) ? trim($value) : $value;
        if (empty($value)) {
            throw new Garp_Model_Validator_Exception(
                sprintf(__('%s is a required field'), __(Garp_Util_String::underscoredToReadable($column)))
            );
        }
    }

    /**
     * Validate the emptiness of a number.
     *
     * @param mixed $value
     * @param string $column
     * @return void
     * @throws Garp_Model_Validator_Exception
     */
    protected function _validateNumber($value, $column) {
        // Not much to check, since 0 is falsy but also a valid integer value.
        if (is_null($value)) {
            throw new Garp_Model_Validator_Exception(
                sprintf(__('%s is a required field'), __(Garp_Util_String::underscoredToReadable($column)))
            );
        }
    }

    protected function _getColumnType($column, Garp_Model_Db $model) {
        return $model->getFieldConfiguration($column)['type'];
    }
}
