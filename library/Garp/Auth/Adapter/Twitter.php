<?php
/**
 * Garp_Auth_Adapter_Twitter
 * Authenticate using Twitter. Uses Zend_OAuth
 *
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Auth
 * @lastmodified $Date: $
 */
class Garp_Auth_Adapter_Twitter extends Garp_Auth_Adapter_Abstract {
	/**
	 * Config key
	 * @var String
	 */
	protected $_configKey = 'twitter';


	/**
	 * Authenticate a user.
	 * @param Zend_Controller_Request_Abstract $request The current request
	 * @return Array|Boolean User data, or FALSE
	 */
	public function authenticate(Zend_Controller_Request_Abstract $request) {
		$callbackUrl = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://').$_SERVER['HTTP_HOST'].$request->getBaseUrl().'/g/auth/login/process/twitter';
		$authVars = $this->_getAuthVars();
		if (!$authVars->consumerKey || !$authVars->consumerSecret) {
			throw new Garp_Auth_Exception('Required key "consumerKey" or "consumerSecret" not set in application.ini.');
		}
		$config = array(
			'siteUrl' => 'https://api.twitter.com/oauth',
			'consumerKey' => $authVars->consumerKey,
			'consumerSecret' => $authVars->consumerSecret,
			'callbackUrl' => $callbackUrl
		);
		try {
			$consumer = new Zend_Oauth_Consumer($config);
			if ($request->isPost()) {
				$token = $consumer->getRequestToken();
				$cookie = new Garp_Store_Cookie('Twitter_request_token');
				$cookie->token = serialize($token);
				$cookie->writeCookie();
				$consumer->redirect();
				return true;
			}
			$cookie = new Garp_Store_Cookie('Twitter_request_token');
			if ($request->getParam('oauth_token') && isset($cookie->token)) {
				$accesstoken = $consumer->getAccessToken($_GET, unserialize($cookie->token));
				// Discard request token
				$cookie->destroy();
				return $this->_getUserData(
					$this->_getTwitterService($accesstoken, $authVars->consumerKey, $authVars->consumerSecret),
					$accesstoken->getParam('user_id')
				);
			}

			$this->_addError('App was not authorized. Please try again.');
			return false;
		} catch (Exception $e) {
			if (strpos($e->getMessage(), 'Duplicate entry') !== false &&
				strpos($e->getMessage(), 'email_unique') !== false) {
				$this->_addError(__('this email address already exists'));
				return false;
			}
			// Provide generic error message
			$this->_addError(APPLICATION_ENV === 'development' ? $e->getMessage() :
				__('login error'));
		}
		return false;
	}


	/**
	 * Store the user's profile data in the database, if it doesn't exist yet.
	 * @param Zend_Oauth_Token_Access $accesstoken
	 * @return Void
	 */
	protected function _getUserData(Zend_Service_Twitter $twitterService, $twitterUserId) {
		$twitterUserData = $twitterService->users->show($twitterUserId);
		$userColumns = $this->_mapProperties((array)$twitterUserData->toValue());

		$userModel = new Model_User();
		$userConditions = $userModel->select()->from(
			$userModel->getName(), $this->_getSessionColumns());

		$model = new G_Model_AuthTwitter();
		$model->bindModel('Model_User', array(
			'conditions' => $userConditions,
			'rule' => 'User'
		));
		$userData = $model->fetchRow(
			$model->select()
				  ->where('twitter_uid = ?', $twitterUserId)
		);
		if (!$userData || !$userData->Model_User) {
			$userData = $model->createNew($twitterUserId, $userColumns);
		} else {
			$model->getObserver('Authenticatable')->updateLoginStats($userData->user_id);
			$userData = $userData->Model_User;
		}
		return $userData;
	}

	protected function _getTwitterService(Zend_Oauth_Token_Access $accesstoken, $consumerKey, $consumerSecret) {
		return new Zend_Service_Twitter(array(
			'accessToken' => $accesstoken,
			'oauthOptions' => array(
				'username' => $accesstoken->getParam('screen_name'),
				'consumerKey' => $consumerKey,
				'consumerSecret' => $consumerSecret
			)
		));
	}
}
