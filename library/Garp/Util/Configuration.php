<?php
/**
 * Garp_Util_Configuration
 * A configuration array with some convenience methods built-in
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Util
 * @lastmodified $Date: $
 */
class Garp_Util_Configuration extends ArrayIterator {
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
		$msg = $msg ?: "\"$key\" is required but not set.";
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

	public function obligateType($key, $type, $msg = '') {
		$this->obligate($key);
		$val = $this[$key];
		$msg = $msg ?: "\"$key\" is set but not the correct type.";
		$valid = true;
		if ($type === 'array') {
			$valid = is_array($val);
		} elseif ($type === 'object') {
			$valid = is_object($val);
		} elseif ($type === 'number') {
			$valid = is_int($val) || is_float($val);
		} elseif ($type === 'numeric') {
			$valid = is_numeric($val);
		} elseif ($type === 'string') {
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
