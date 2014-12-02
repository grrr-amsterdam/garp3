<?php
/**
 * Garp_Validate_DutchPostalCode
 * Validates a Dutch postal code
 * @author Harmen Janssen | grrr.nl
 * @version 1
 * @package Garp
 * @subpackage Validate
 */
class Garp_Validate_DutchPostalCode extends Zend_Validate_Abstract {

	const POSTALCODE_REGEXP = '/^(\d{4})\s?([A-Za-z]{2})$/';

	const INVALID_INPUT = 'invalidInput';
	const INVALID_POSTALCODE = 'invalidPostcode';

	protected $_messageTemplates = array(
		self::INVALID_INPUT    => "'%value%' is not a string",
		self::INVALID_POSTALCODE => "'%value%' is not a valid Dutch postcode."
	);

	public function isValid($value) {
		$this->_setValue($value);

		if (!is_string($value)) {
			$this->_error(self::INVALID_INPUT);
			return false;
		} elseif (!preg_match(self::POSTALCODE_REGEXP, $value)) {
			$this->_error(self::INVALID_POSTALCODE);
			return false;
		}
		return true;
	}
}
