<?php
/**
 * Garp_Controller_Helper_Login
 * This helper contains some hooks to add functionality to the login process
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Controller
 * @lastmodified $Date: $
 */
class Garp_Controller_Helper_Login extends Zend_Controller_Action_Helper_Abstract {
	/**
 	 * Before login hook
 	 * @param Array $authVars Containing auth-related configuration.
 	 * @param Garp_Auth_Adapter_Abstract $adapter The chosen adapter.
 	 * @return Void
 	 */
	public function beforeLogin(array $authVars, Garp_Auth_Adapter_Abstract $adapter) {
	}


	/**
 	 * After login hook
 	 * @param Array $userData The data of the logged in user
 	 * @param String $targetUrl The URL the user is being redirected to
 	 * @return Void
 	 */
	public function afterLogin(array $userData, $targetUrl) {
	}


	/**
 	 * Before logout hook
 	 * @param Array $userData The data of the logged in user
 	 * @return Void
 	 * Note: the $userData argument is not type-hinted because NULL might be returned by Garp_Auth::getUserData()
 	 */
	public function beforeLogout($userData) {
	}


	/**
 	 * After logout hook
 	 * @param Array $userData The data of the logged in user
 	 * @return Void
 	 * Note: the $userData argument is not type-hinted because NULL might be returned by Garp_Auth::getUserData()
 	 */
	public function afterLogout($userData) {
	}
}
