<?php
/**
 * Garp_Model_Validator_NotEmpty
 * Check if a value is not empty
 * @author Harmen Janssen | grrr.nl
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
	 * @param Boolean $onlyIfAvailable Wether to skip validation on fields that are not in the array
	 * @return Void
	 * @throws Garp_Model_Validator_Exception
	 */
	public function validate(array $data, $onlyIfAvailable = false) {
		$theColumns = $this->_fields;
		$validate = function($c) use ($data, $onlyIfAvailable) {
			if ($onlyIfAvailable && !array_key_exists($c, $data)) {
				return;
			}
			if (empty($data[$c])) {
				throw new Garp_Model_Validator_Exception("Column $c cannot be empty.");
			}
		};
		array_walk($theColumns, $validate);
	}
}