<?php
/**
 * Garp_Auth_Adapter_OpenId
 * Authenticate using OpenID. Uses Zend_Auth_Adapter_OpenId
 * 
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Auth
 * @lastmodified $Date: $
 */
class Garp_Auth_Adapter_OpenId extends Garp_Auth_Adapter_Abstract {
	/**
	 * Config key
	 * @var String
	 */
	protected $_configKey = 'openid';
	
	
	/**
	 * The Sreg specification to use with the OpenID call
	 * @var Zend_OpenId_Extension_Sreg
	 */
	protected $_sreg = null;
	
	
	/**
	 * Authenticate a user.
	 * @param Zend_Controller_Request_Abstract $request The current request
	 * @return Array|Boolean User data, or FALSE
	 */
	public function authenticate(Zend_Controller_Request_Abstract $request) {
		if ($request->getPost('openid_identifier') ||
			$request->getParam('openid_mode')) {
			$sreg = $this->getSreg();
			$openIdAdapter = new Zend_Auth_Adapter_OpenId(
				$request->getPost('openid_identifier'),
				null, null, null, $sreg
			);
			$result = $openIdAdapter->authenticate();
			if ($result->isValid()) {
				return $this->_getUserData($result->getIdentity(), $sreg->getProperties());
			} else {
				$errors = $result->getMessages();
				array_walk($errors, array($this, '_addError'));
			}
		}
		$this->_addError('Insufficient data received');
		return false;
	}
	
	
	/**
	 * Get the currently registered Sreg module
	 * @return Zend_OpenId_Extension_Sreg
	 */
	public function getSreg() {
		return $this->_sreg;
	}
	
	
	/**
	 * Register an Sreg extension with the OpenID call
	 * @param Zend_OpenId_Extension_Sreg $sreg
	 * @return $this
	 */
	public function setSreg(Zend_OpenId_Extension_Sreg $sreg) {
		$this->_sreg = $sreg;
		return $this;
	}
	
	
	/**
	 * Store the user's profile data in the database, if it doesn't exist yet.
	 * @param String $id The openid
	 * @param Array $props The properties fetched thru Sreg
	 * @return Void
	 */
	protected function _getUserData($id, array $props) {
		$ini = Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/application.ini');
		$sessionColumns = Zend_Db_Select::SQL_WILDCARD;
		if (!empty($ini->auth->login->sessionColumns)) {
 		   	$sessionColumns = $ini->auth->login->sessionColumns;
 		   	$sessionColumns = explode(',', $sessionColumns);
		}
		$userModel = new Model_User();
		$userConditions = $userModel->select()->from($userModel->getName(), $sessionColumns);

		$model = new G_Model_AuthOpenId();
		$model->bindModel('Model_User', array('conditions' => $userConditions));
		$userData = $model->fetchRow(
			$model->select()
				  ->where('openid = ?', $id)
		);
		if (!$userData || !$userData->Model_User) {
			$userData = $model->createNew($id, $this->_mapProperties($props));
		} else {
			$model->updateLoginStats($userData->user_id);
			$userData = $userData->Model_User;
		}
		return $userData->getPrimaryKey();
	}
}
