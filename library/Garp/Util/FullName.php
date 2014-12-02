<?php
/**
 * Garp_Util_FullName
 * Represents a concatenated string, composed of separate name fields.
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Util
 */
class Garp_Util_FullName {
	protected $_fullName;


	/**
 	 * Class constructor
 	 * @param String $baseUrl
 	 * @return Void
 	 */
	public function __construct($person) {
		if ($person instanceof Zend_Db_Table_Row_Abstract) {
			$person = $person->toArray();
		}

		$this->_fullName = $this->_getFullName($person);
	}


	/**
 	 * Get the value
 	 * @return String
 	 */
	public function __toString() {
		return $this->_fullName;
	}


	/**
 	 * Create full URL
 	 * @param Garp_Db_Table_Row | StdClass $person
 	 * @return String
 	 */
	protected function _getFullName($person) {
		if (
			array_key_exists('first_name', $person) &&
			array_key_exists('last_name_prefix', $person) &&
			array_key_exists('last_name', $person)
		) {
			return
				$person['first_name']
				.(
					$person['last_name_prefix'] ?
						' '.$person['last_name_prefix'] :
						''
				)
				.(
					$person['last_name'] ?
						' '.$person['last_name'] :
						''
				)
			;
		} elseif (array_key_exists('name', $person)) {
			return $person['name'];
		} else {
			throw new Exception("This model does not have a first name, last name prefix and last name. Nor does it have a singular name field.");
		}
	}
}
