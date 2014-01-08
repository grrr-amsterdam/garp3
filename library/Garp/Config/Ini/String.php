<?php
/**
 * Garp_Config_Ini_String
 * Object used to trick Garp_Config_Ini::__construct into taking another path.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp_Config_Ini
 */
class Garp_Config_Ini_String {
	/**
 	 * @var String
 	 */
	protected $_value = '';


	/**
 	 * Class constructor
 	 * @param String $value
 	 * @return Void
 	 */
	public function __construct($value) {
		$this->setValue($value);
	}


	/**
 	 * @return String
 	 */
	public function getValue() {
		return $this->_value;
	}


	/**
 	 * @param String $value
 	 * @return Void
 	 */
	public function setValue($value) {
		$this->_value = (string)$value;
	}


	/**
 	 * @return String
 	 */
	public function __toString() {
		return $this->_value;
	}
}
