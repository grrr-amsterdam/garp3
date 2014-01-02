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
		
		$model = new G_Model_AuthLocal();
		$result = $model->tryLogin($identityValue, $credentialValue, $authVars);
		
		if ($result) {
			return $result->toArray();
		} else {
			$this->_addError('No record of that user was found');
		}
		return false;
	}
}
