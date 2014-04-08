<?php
/**
 * Garp_Auth_Adapter_Db
 * Simplest authentication method; thru a local database table.
 * 
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Auth
 * @lastmodified $Date: $
 */
class Garp_Auth_Adapter_Db extends Garp_Auth_Adapter_Abstract {
	/**
	 * Config key
	 * @var String
	 */
	protected $_configKey = 'db';
	
	
	/**
	 * Authenticate a user.
	 * @param Zend_Controller_Request_Abstract $request The current request
	 * @return Array|Boolean User data, or FALSE
	 */
	public function authenticate(Zend_Controller_Request_Abstract $request) {
		$authVars = new Garp_Util_Configuration($this->_getAuthVars()->toArray());
		$authVars->obligate('model')
				 ->obligate('identityColumn')
				 ->obligate('credentialColumn')
				 ->setDefault('hashMethod', 'MD5')
				 ->setDefault('salt', '');
		
		if (!$request->getPost($authVars['identityColumn']) ||
			!$request->getPost($authVars['credentialColumn'])) {
			$this->_addError('Insufficient data received');
			return false;
		}
		
		$identityValue = $request->getPost($authVars['identityColumn']);
		$credentialValue = $request->getPost($authVars['credentialColumn']);

		$ini = Zend_Registry::get('config');
		$sessionColumns = null;
 	   	if (!empty($ini->auth->login->sessionColumns)) {
 		   	$sessionColumns = $ini->auth->login->sessionColumns;
 		   	$sessionColumns = explode(',', $sessionColumns);
		}

		$model = new G_Model_AuthLocal();
		try {
			$result = $model->tryLogin($identityValue, $credentialValue, $authVars, $sessionColumns);
			return $result->toArray();
		} catch (Garp_Auth_Adapter_Db_UserNotFoundException $e) {
			$this->_addError('The email address is not found');
		} catch (Garp_Auth_Adapter_Db_InvalidPasswordException $e) {
			$this->_addError('The password is invalid');
		}
		return false;
	}
}
