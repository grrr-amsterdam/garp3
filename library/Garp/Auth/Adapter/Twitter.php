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
				exit;
			} elseif ($request->getParam('oauth_token')) {
				$cookie = new Garp_Store_Cookie('Twitter_request_token');
				if (isset($cookie->token)) {
					$accesstoken = $consumer->getAccessToken($_GET, unserialize($cookie->token));
					// Discard request token
					$cookie->destroy();
					return $this->_getUserData($accesstoken);
				} else {
					$this->_addError('App was not authorized. Please try again.');
				}
			} elseif ($request->getParam('denied')) {
				$this->_addError('App was not authorized. Please try again.');
			}
		} catch (Exception $e) {
			$this->_addError($e->getMessage());
		}
		return false;
	}
	
	
	/**
	 * Store the user's profile data in the database, if it doesn't exist yet.
	 * @param Zend_Oauth_Token_Access $accesstoken
	 * @return Void
	 */
	protected function _getUserData(Zend_Oauth_Token_Access $accesstoken) {
		$username = $accesstoken->getParam('screen_name');
		$twitterService = new Zend_Service_Twitter(array(
			'accessToken' => $accesstoken,
			'username' => $username
		));
		$userData = $twitterService->user->show($username);
		$id = $userData->id;
		$name = $userData->name;
		$name = explode(' ', $name, 2);
		$userDataFromTwitter = array(
			'first_name' => $name[0],
			'last_name' => !empty($name[1]) ? $name[1] : ''
		);

		$model = new G_Model_AuthTwitter();
		$model->bindModel('Model_User');
		$userData = $model->fetchRow(
			$model->select()
				  ->where('twitter_uid = ?', $id)
		);
		if (!$userData || !$userData->Model_User) {
			$userData = $model->createNew($id, $userDataFromTwitter);
		} else {
			$model->updateLoginStats($userData->user_id);
			$userData = $userData->Model_User;
		}
		return $userData;
	}
}
