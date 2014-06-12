<?php
/**
 * Garp_Model_Validator_NotEmpty
 * Check if a value is not empty
 * @author Harmen Janssen, David spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Validator
 * @lastmodified $Date: $
 */
class Garp_Model_Validator_NotEmpty extends Garp_Model_Validator_Abstract {
	/**
	 * Columns to check for emptiness
	 * @var Array
	 */
	protected $_fields = array(); 

	/**
	 * Setup the validation environment
	 * @param Array $config Configuration options
	 * @return Void
	 */
	protected function _setup($config) {
		$this->_fields = $config;
	}

	/**
	 * Validate wether the given columns are not empty
	 * @param Array $data The data to validate
	 * @param Garp_Model_Db $model The model
	 * @param Boolean $onlyIfAvailable Wether to skip validation on fields that are not in the array
	 * @return Void
	 * @throws Garp_Model_Validator_Exception
	 */
	public function validate(array $data, Garp_Model_Db $model, $onlyIfAvailable = false) {
		foreach ($this->_fields as $field) {
			$this->_validate($field, $model, $data, $onlyIfAvailable);
		}
	}

	/**
 	 * Pass value along to more specific validate functions
 	 * Callback for array_walk call in self::validate()
 	 * @param String $column Column value
 	 * @param Array $params  Userdata passed to array_walk, containing $data and $onlyIfAvailable
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

		// Default validation to string
		$this->_validateString($value, $column);
	}

	/**
 	 * Validate the emptiness of a string
 	 */
	protected function _validateString($value, $column) {
		$val = '';
		if (!is_null($value)) {
			$val = trim($value);
		}
		if (!strlen($val)) {
			throw new Garp_Model_Validator_Exception(
				sprintf(__('%s is a required field'), __(Garp_Util_String::underscoredToReadable($column)))
			);
		}
	}

	/**
 	 * Validate the emptiness of a number
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
		$colInfo = $model->getFieldConfiguration($column);
		return $colInfo['type'];
	}
}
