<?php
/**
 * G_View_Helper_String
 * Various String helper functionality.
 * @author Harmen Janssen | grrr.nl, David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Helper
 * @lastmodified $Date: $
 */
class G_View_Helper_String extends Zend_View_Helper_Abstract {
	/**
	 * Chain method.
	 * @return G_View_Helper_String 
	 */
	public function string() {
		return $this;
	}


	/**
	 * Maps methods to Garp_Util_String
	 * @param String $method
	 * @param Array $args
	 * @return Mixed
	 */
	public function __call($method, $args) {
		return call_user_func_array(array('Garp_Util_String', $method), $args);
	}
}
