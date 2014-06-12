<?php
/**
 * Garp_Model_Validator_Email
 * Check if a value looks like a valid email address
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Validator
 * @lastmodified $Date: $
 */
class Garp_Model_Validator_Email extends Garp_Model_Validator_Abstract {
	/**
	 * The regular expression used to validate email addresses
	 * @var String
	 */
	const EMAIL_REGEXP = '/^|\s([0-9a-zA-Z-_+]([-.\w]*[0-9a-zA-Z-_+])*@([0-9a-zA-Z][-\w]*[0-9a-zA-Z]\.)+[a-zA-Z]{2,9})$|\s/';
	
	
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
	public function validate(array $data, Garp_Model_Db $model, $onlyIfAvailable = true) {
		$theColumns = $this->_fields;
		$regexp = self::EMAIL_REGEXP;
		$validate = function($c) use ($data, $onlyIfAvailable, $regexp) {
			if (
				$onlyIfAvailable &&
				(
					!array_key_exists($c, $data) ||
					empty($data[$c])
				)
			) {
				return;
			}
			if (empty($data[$c]) || !preg_match($regexp, $data[$c])) {
				$value = !empty($data[$c]) ? $data[$c] : '';
				$error = sprintf(__("'%value%' is not a valid email address in the basic format local-part@hostname"), $value);
				throw new Garp_Model_Validator_Email_Exception($error);
			}
		};
		array_walk($theColumns, $validate);
	}
}
