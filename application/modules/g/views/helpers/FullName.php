<?php
/**
 * G_View_Helper_FullName
 * Returns a concatenation of first name, last name prefix and last name
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 */
class G_View_Helper_FullName extends Zend_View_Helper_Abstract {
	/**
	 * @param Garp_Db_Table_Row|Array $person A User row or array representation thereof.
	 * @return String
	 */
	public function fullName($person) {
		return (string)new Garp_Util_FullName($person);
	}
}
