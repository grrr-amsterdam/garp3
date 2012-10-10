<?php
/**
 * Garp_Controller_Helper_Auth
 * Interface to the Garp_Auth object.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Controller
 * @lastmodified $Date: $
 */
class Garp_Controller_Helper_Auth extends Zend_Controller_Action_Helper_Abstract {
	/**
	 * Maps methods to Garp_Auth
	 * @param String $method
	 * @param Array $args
	 * @return Mixed
	 */
	public function __call($method, $args) {
		$auth = Garp_Auth::getInstance();
		return call_user_func_array(array($auth, $method), $args);
	}
}
