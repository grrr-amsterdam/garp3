<?php
use LinkedIn\LinkedIn;

/**
 * Garp_Auth_Adapter_LinkedIn
 * Authenticate using LinkedIn
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Garp_Auth_Adapter
 */
class Garp_Auth_Adapter_Linkedin extends Garp_Auth_Adapter_Abstract {
	const LINKED_IN_PROFILE_QUERY = '/people/~:(first_name,last_name,id,email-address,picture-url)';

	/**
 	* @var LinkedIn
 	*/
	protected $_linkedIn;

	protected $_configKey = 'linkedin';

	public function authenticate(Zend_Controller_Request_Abstract $request) {
		if ($request->getParam('error')) {
			$this->_addError($request->getParam('error_description'));
			return false;
		}

		// User returns from LinkedIn and has authorized the app
		if ($request->getParam('code')) {
			$accessToken = $this->_getLinkedInInstance()->getAccessToken($request->getParam('code'));
			return $this->_getUserData($accessToken);
		}

		// User has not interacted yet, and needs to authorize the app
		$authorizeUrl = $this->_getLinkedInInstance()->getLoginUrl(array(
			LinkedIn::SCOPE_BASIC_PROFILE, 
			LinkedIn::SCOPE_EMAIL_ADDRESS
		));
		Zend_Controller_Action_HelperBroker::getStaticHelper('redirector')
			->gotoUrl($authorizeUrl);
		return false;
	}

	protected function _getUserData($accessToken) {
		$profileData = $this->_getLinkedInInstance()->get(self::LINKED_IN_PROFILE_QUERY);
		$newUserData = $this->_linkedinDataToUserRecord($profileData);

		$userModel = new Model_User();
		$userConditions = $userModel->select()
			->from($userModel->getName(), $this->_getSessionColumns());

		$model = new G_Model_AuthLinkedin();
		$model->bindModel('Model_User', array('conditions' => $userConditions));
		$userData = $model->fetchRow(
			$model->select()
				  ->where('linkedin_uid = ?', $profileData['id'])
		);
		if (!$userData || !$userData->Model_User) {
			$userData = $model->createNew($profileData['id'], $newUserData);
		} else {
			$model->updateLoginStats($userData->user_id);
			$userData = $userData->Model_User;
		}
		return $userData;
	}

	protected function _linkedinDataToUserRecord(array $linkedInData) {
		return array(
			'first_name' => $linkedInData['firstName'],
			'last_name'  => $linkedInData['lastName'],
			'email'      => $linkedInData['emailAddress'],
			'role'       => 'user'
		);
	}

	protected function _getLinkedInInstance() {
		$authVars = $this->_getAuthVars();
		$callbackUrl = $authVars->callbackBaseUrl . '/g/auth/login/process/linkedin';
		// Sanity checks
		if (!$authVars->consumerKey || !$authVars->consumerSecret) {
			throw new Garp_Auth_Exception(
				'Required key "consumerKey" or "consumerSecret" not set in application.ini.');
		}
		if (!$this->_linkedIn) {
 		   	$this->_linkedIn = new LinkedIn(array(
				'api_key' => $authVars->consumerKey, 
				'api_secret' => $authVars->consumerSecret, 
				'callback_url' => $callbackUrl
			));
		}
		return $this->_linkedIn;
	}
}
