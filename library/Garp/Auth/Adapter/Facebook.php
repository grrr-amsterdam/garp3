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
		
		$session = $facebook->getSession();		
		$userData = $uid = null;
		// Session based API call.
		try {
			$uid = $facebook->getUser();
			// If a user is authenticated, $userData will be filled with user data
			$userData = $facebook->api('/me');
			return $this->_getUserData($uid, $userData);
		} catch (FacebookApiException $e) {
			$this->_addError($e->getMessage());
			return false;				
		}
	}
	
	
	/**
	 * Store the user's profile data in the database, if it doesn't exist yet.
	 * @param String $uid The Facebook UID
	 * @param Array $facebookData The profile data received from Facebook
	 * @return Void
	 */
	protected function _getUserData($uid, array $facebookData) {
		$model = new G_Model_AuthFacebook();
		$model->bindModel('Model_User');
		$userData = $model->fetchRow(
			$model->select()
				  ->where('facebook_uid = ?', $uid)
		);
 		if (!$userData || !$userData->Model_User) {
			$userData = $model->createNew($uid, $this->_mapProperties($facebookData));
		} else {
			$model->updateLoginStats($userData->user_id);
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
		require_once APPLICATION_PATH.'/../garp/library/Garp/3rdParty/facebook/src/facebook.php';
		$facebook = new Facebook(array(
			'appId'  => $authVars->appId,
			'secret' => $authVars->secret,
			'cookie' => false,
		));
		return $facebook;
	}
}
