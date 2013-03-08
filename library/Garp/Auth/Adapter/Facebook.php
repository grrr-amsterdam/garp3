<?php
/**
 * Garp_Auth_Adapter_Facebook
 * Authenticate using Facebook (using oAuth)
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Auth
 * @lastmodified $Date: $
 */
class Garp_Auth_Adapter_Facebook extends Garp_Auth_Adapter_Abstract {
	protected $_configKey = 'facebook';
	
	
	/**
	 * Authenticate a user.
	 * @param Zend_Controller_Request_Abstract $request The current request
	 * @return Array|Boolean User data, or FALSE
	 */
	public function authenticate(Zend_Controller_Request_Abstract $request) {
		$facebook = $this->_getFacebookClient();
		
		/**
		 * Send the user to Facebook to login and give us access.
		 * This happens when the form on the login page gets posted. 
		 * Then this request will be made one more time; when the user comes back from Facebook.
		 * At that point he might has given us access, which is
		 * checked in the try {...} catch(){...} block below.
		 * Just note that any POST request here results in the user being redirected to Facebook.
		 */
		if ($request->isPost()) {
			$redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
			$redirector->gotoUrl($facebook->getLoginUrl());
			exit;
		}
		
		// Session based API call.
		try {
			$userData = $facebook->login();
			$userData = $this->_getUserData($userData);

			$authVars = $this->_getAuthVars();
			// Automatically fetch friends if so configured.
			if (!empty($authVars->friends->collect) && $authVars->friends->collect) {
				if (empty($authVars->friends->bindingModel)) {
					$bindingModel = 'Model_UserUser'; // A Sensible Default™
				} else {
					$bindingModel = $authVars->friends->bindingModel;
				}
				$facebook->mapFriends(array(
					'bindingModel' => $bindingModel,
					'user_id'      => $userData['id']
				));
			}
			return $userData;
		} catch (FacebookApiException $e) {
			$this->_addError($e->getMessage());
			return false;
		} catch (Exception $e) {
			$this->_addError('Er is een onbekende fout opgetreden. Probeer het later opnieuw.');
			return false;
		}
	}
	
	
	/**
	 * Store the user's profile data in the database, if it doesn't exist yet.
	 * @param Array $facebookData The profile data received from Facebook
	 * @return Void
	 */
	protected function _getUserData(array $facebookData) {
		$uid = $facebookData['id'];
		$ini = Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/application.ini');
		$sessionColumns = Zend_Db_Select::SQL_WILDCARD;
		if (!empty($ini->auth->login->sessionColumns)) {
 		   	$sessionColumns = $ini->auth->login->sessionColumns;
 		   	$sessionColumns = explode(',', $sessionColumns);
		}
		$userModel = new Model_User();
		$userConditions = $userModel->select()->from('user', $sessionColumns);
		$model = new G_Model_AuthFacebook();
		$model->bindModel('Model_User', array('conditions' => $userConditions));
		$userData = $model->fetchRow(
			$model->select()
				  ->where('facebook_uid = ?', $uid)
		);
 		if (!$userData || !$userData->Model_User) {
			$userData = $model->createNew(
				array(
					'facebook_uid' => $uid,
					'access_token' => $facebookData['access_token'],
				),
				$this->_mapProperties($facebookData)
			);
		} else {
			$model->updateLoginStats($userData->user_id, array(
				'access_token' => $facebookData['access_token'],
			));
			$userData = $userData->Model_User;
		}
		return $userData;
	}
	
	
	/**
	 * Load Facebook's own client
	 * @return Facebook
	 */
	protected function _getFacebookClient() {
		$authVars = $this->_getAuthVars();
		$facebook = Garp_Social_Facebook::getInstance(array(
			'appId'  => $authVars->appId,
			'secret' => $authVars->secret,
			'cookie' => false,
		));
		return $facebook;
	}
}
