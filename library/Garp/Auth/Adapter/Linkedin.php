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
	const LINKED_IN_PROFILE_QUERY = '/people/~:(first_name,last_name,id,email-address,picture-url,formatted-name,positions:(company,is-current))';

	/**
 	* @var LinkedIn
 	*/
	protected $_linkedIn;

	protected $_configKey = 'linkedin';

	public function authenticate(Zend_Controller_Request_Abstract $request,
		Zend_Controller_Response_Abstract $response) {
		if ($request->getParam('error')) {
			$this->_addError($request->getParam('error_description'));
			return false;
		}

		try {
			$cookie = new Garp_Store_Cookie('LinkedIn_Oauth_Helper');
			// User returns from LinkedIn and has authorized the app
			if ($request->getParam('code')) {
				$accessToken = $this->_getLinkedInInstance()->getAccessToken($request->getParam('code'));
				if ($cookie->extendedUserColumns) {
					$this->setExtendedUserColumns(unserialize($cookie->extendedUserColumns));	
				}
				$cookie->destroy();

				return $this->_getUserData($accessToken);
			}

			// User has not interacted yet, and needs to authorize the app
			
			if (!empty($this->_extendedUserColumns)) {
				$cookie->extendedUserColumns = serialize($this->_extendedUserColumns);
			}
			$cookie->writeCookie();

			$authorizeUrl = $this->_getLinkedInInstance()->getLoginUrl(array(
				LinkedIn::SCOPE_BASIC_PROFILE,
				LinkedIn::SCOPE_EMAIL_ADDRESS
			));
			Zend_Controller_Action_HelperBroker::getStaticHelper('redirector')
				->gotoUrl($authorizeUrl);
			return false;
		} catch (Exception $e) {
			if (strpos($e->getMessage(), 'Duplicate entry') !== false &&
				strpos($e->getMessage(), 'email_unique') !== false) {
				$this->_addError(__('this email address already exists'));
				return false;
			}
			$this->_addError(APPLICATION_ENV === 'development' ? $e->getMessage() :
 			   	__('login error'));
			throw $e;
			return false;
		}
	}

	protected function _getUserData($accessToken) {
		$profileData = $this->_getLinkedInInstance()->get(self::LINKED_IN_PROFILE_QUERY);
		$newUserData = $this->_mapProperties($profileData);
		$userModel = new Model_User();
		$userConditions = $userModel->select()
			->from($userModel->getName(), $this->_getSessionColumns());

		$model = new G_Model_AuthLinkedin();
		$model->bindModel('Model_User', array(
			'conditions' => $userConditions,
			'rule' => 'User'
		));
		$userData = $model->fetchRow(
			$model->select()
				  ->where('linkedin_uid = ?', $profileData['id'])
		);
		if (!$userData || !$userData->Model_User) {
			$userData = $model->createNew($profileData['id'], $newUserData)->toArray();
			// Make sure only the session columns remain
			$userData = array_intersect_key($userData, array_fill_keys($this->_getSessionColumns(),
				null));
		} else {
			$model->getObserver('Authenticatable')->updateLoginStats($userData->user_id);
			$userData = $userData->Model_User;
		}
		return $userData;
	}

	protected function _getLinkedInInstance() {
		$authVars = $this->_getAuthVars();
		$callbackUrl = new Garp_Util_FullUrl(array(array('method' => 'linkedin'), 'auth_submit'));
		//if ($authVars->callbackBaseUrl) {
			//$callbackUrl = $authVars->callbackBaseUrl . '/g/auth/login/process/linkedin';
		//}
		// Sanity checks
		if (!$authVars->consumerKey || !$authVars->consumerSecret) {
			throw new Garp_Auth_Exception(
				'Required key "consumerKey" or "consumerSecret" not set in application.ini.');
		}
		if (!$this->_linkedIn) {
 		   	$this->_linkedIn = new LinkedIn(array(
				'api_key' => $authVars->consumerKey,
				'api_secret' => $authVars->consumerSecret,
				'callback_url' => (string)$callbackUrl
			));
		}
		return $this->_linkedIn;
	}
}
