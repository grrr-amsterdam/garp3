<?php
/**
 * G_View_Helper_Auth
 * class description
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Helper
 * @lastmodified $Date: $
 */
class G_View_Helper_Auth extends Zend_View_Helper_Abstract {
	/**
	 * Method for chainability
	 * @return $this
	 */
	public function auth() {
		return $this;
	}
	
	
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