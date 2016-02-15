<?php
/**
 * Garp_Auth_Adapter_Hyves
 * Authenticate using Hyves (using OpenID)
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Auth
 * @lastmodified $Date: $
 */
class Garp_Auth_Adapter_Hyves extends Garp_Auth_Adapter_OpenId {
	protected $_configKey = 'hyves';


	/**
	 * Authenticate a user.
	 * @param Zend_Controller_Request_Abstract $request The current request
	 * @param Zend_Controller_Response_Abstract $response The current response
	 * @return Array|Boolean User data, or FALSE
	 */
	public function authenticate(Zend_Controller_Request_Abstract $request,
		Zend_Controller_Response_Abstract $response) {
		$this->setSreg(new Zend_OpenId_Extension_Sreg(
			array(
	            "nickname"	=> true,
	            "email"		=> true,
	            "fullname"	=> true,
	            "dob"		=> true,
	            "gender"	=> true,
	            "country"	=> true,
	            "language"	=> true,
	        ),
			null,
			1.1
		));

		return parent::authenticate($request);
	}
}
