<?php
/**
 * Garp_Util_Configuration
 * A configuration array with some convenience methods built-in
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.5
 * @package      Garp_Util
 */
class Garp_Util_Configuration extends ArrayIterator {
	/**#@+
 	 * Constants used in type validation
 	 */
	const TYPE_ARRAY   = 'array';
	const TYPE_OBJECT  = 'object';
	const TYPE_NUMBER  = 'number';
	const TYPE_NUMERIC = 'numeric';
	const TYPE_STRING  = 'string';
    /**#@-*/

	/**#@+
 	 * Exceptions
 	 */
	const EXCEPTION_MISSINGKEY = '"%s" is required but not set.';
	const EXCEPTION_INCORRECTTYPE = '"%s" is set but not the correct type.';
	/**#@-*/

	/**
	 * Convenience method for requiring a certain key.
	 * (note; this method is not called "require" as would be 
	 * more obvious since that is a PHP reserved keyword)
	 * @param String|Int $key The key that's required
	 * @param String $msg Optional error message
	 * @return Garp_Util_Configuration $this
	 * @throws Garp_Util_Configuration_Exception
	 */
	public function obligate($key, $msg = '') {
		$msg = $msg ?: sprintf(self::EXCEPTION_MISSINGKEY, $key);
		if (!$this->offsetExists($key)) {
			throw new Garp_Util_Configuration_Exception($msg);
		}
		return $this;
	}
	
	/**
	 * Convenience method for setting default values.
	 * The value gets set if the key does not exist or 
	 * when it's empty (if $ifEmpty is true).
	 * @param String|Int $key The key of the value
	 * @param Mixed $default The default value
	 * @param Boolean $ifEmpty Wether to set even if the key already
	 * 						   exists but is empty.
	 * @return Garp_Util_Configuration $this
	 */
	public function setDefault($key, $default, $ifEmpty = false) {
		if (($ifEmpty && $this->offsetExists($key) && !$this->offsetGet($key)) ||
			!$this->offsetExists($key)) {
			$this->offsetSet($key, $default);
		}
		return $this;
	}

	/**
 	 * Validate a value's type
 	 * @param String $key
 	 * @param String $type
 	 * @param String $msg Optional error message
 	 * @return Garp_Util_Configuration $this
 	 * @throws Garp_Util_Configuration_Exception_IncorrectType
 	 */
	public function validateType($key, $type, $msg = '') {
		$this->obligate($key);
		$val = $this[$key];
		$msg = $msg ?: sprintf(self::EXCEPTION_INCORRECTTYPE, $key); 
		$valid = true;
		if ($type === self::TYPE_ARRAY) {
			$valid = is_array($val);
		} elseif ($type === self::TYPE_OBJECT) {
			$valid = is_object($val);
		} elseif ($type === self::TYPE_NUMBER) {
			$valid = is_int($val) || is_float($val);
		} elseif ($type === self::TYPE_NUMERIC) {
			$valid = is_numeric($val);
		} elseif ($type === self::TYPE_STRING) {
			$valid = is_string($val);
		}

		if (!$valid) {
			throw new Garp_Util_Configuration_Exception_IncorrectType($msg);
		}
		return $this;
	}

	/**
 	 * Convert to simple array
 	 * @return Array
 	 */
	public function toArray() {
		return (array)$this;
	}
}
