<?php
/**
 * G_View_Helper_FullName
 * Returns a concatenation of first name, last name prefix and last name
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 */
class G_View_Helper_FullName extends Zend_View_Helper_Abstract {
	/**
	 * @param Garp_Db_Table_Row $person A User row
	 * @return String
	 */
	public function fullName(Garp_Db_Table_Row $person) {
		if (
			isset($person->first_name) &&
			isset($person->last_name_prefix) &&
			isset($person->last_name)
		) {
			return
				$person->first_name
				.(
					$person->last_name_prefix ?
						' '.$person->last_name_prefix :
						''
				)
				.' '.$person->last_name
			;
		} else throw new Exception("This model does not have a first name, last name prefix and last name.");
	}
}
