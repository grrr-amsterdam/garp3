<?php
/**
 * Garp_Controller_Helper_Register
 * This helper contains some hooks to add functionality to the register process
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Controller
 * @lastmodified $Date: $
 */
class Garp_Controller_Helper_Register extends Zend_Controller_Action_Helper_Abstract {
	/**
 	 * Before register hook
 	 * @param Array $postData Data submitted by the user
 	 * @return Void
 	 */
	public function beforeRegister(array $postData) { }


	/**
 	 * After register hook
 	 * Note: The newly registered user is in Garp_Auth::getUserData().
 	 * @return Void
 	 */
	public function afterRegister() { }
}
