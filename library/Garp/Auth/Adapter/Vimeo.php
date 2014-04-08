<?php
/**
 * Garp_Auth_Adapter_Vimeo
 * Authenticate using Vimeo. Uses Zend_OAuth
 * 
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Auth
 * @lastmodified $Date: $
 */
class Garp_Auth_Adapter_Vimeo extends Garp_Auth_Adapter_Abstract {
	/**
	 * Config key
	 * @var String
	 */
	protected $_configKey = 'vimeo';

	
	/**
	 * Authenticate a user.
	 * @param Zend_Controller_Request_Abstract $request The current request
	 * @return Array|Boolean User data, or FALSE
	 */
	public function authenticate(Zend_Controller_Request_Abstract $request) {
		$callbackUrl = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://').$_SERVER['HTTP_HOST'].$request->getBaseUrl().'/g/auth/login/process/vimeo';
		$authVars = $this->_getAuthVars();
		if (!$authVars->consumerKey || !$authVars->consumerSecret) {
			throw new Garp_Auth_Exception('Required key "consumerKey" or "consumerSecret" not set in application.ini.');
		}
		$config = array(
			'siteUrl' => 'http://vimeo.com/oauth',
			'consumerKey' => $authVars->consumerKey,
			'consumerSecret' => $authVars->consumerSecret,
			'callbackUrl' => $callbackUrl
		);
		try {
			$consumer = new Zend_Oauth_Consumer($config);
			if ($request->isPost()) {
				$token = $consumer->getRequestToken();
				$cookie = new Garp_Store_Cookie('Vimeo_request_token');
				$cookie->token = serialize($token);
				$cookie->writeCookie();
				$consumer->redirect();
				exit;
			} elseif ($request->getParam('oauth_token')) {
				$cookie = new Garp_Store_Cookie('Vimeo_request_token');
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
	 * @param Zend_Oauth_Token_Access $accessToken
	 * @return Void
	 */
	protected function _getUserData(Zend_Oauth_Token_Access $accessToken) {
		$authVars = $this->_getAuthVars();

		$token = $accessToken->getToken();
		$tokenSecret = $accessToken->getTokenSecret();
		$vimeoService = new Garp_Service_Vimeo_Pro(
			$authVars->consumerKey,
			$authVars->consumerSecret,
			$token,
			$tokenSecret
		);
		$userDataFromVimeo = $vimeoService->people->getInfo($token);
		$id = $userDataFromVimeo['id'];

		$ini = Zend_Registry::get('config');
		$sessionColumns = Zend_Db_Select::SQL_WILDCARD;
		if (!empty($ini->auth->login->sessionColumns)) {
 		   	$sessionColumns = $ini->auth->login->sessionColumns;
 		   	$sessionColumns = explode(',', $sessionColumns);
		}
		$userModel = new Model_User();
		$userConditions = $userModel->select()->from($userModel->getName(), $sessionColumns);

		$model = new G_Model_AuthVimeo();
		$model->bindModel('Model_User', array('conditions' => $userConditions));
		$userData = $model->fetchRow(
			$model->select()
				  ->where('vimeo_id = ?', $id)
		);
		if (!$userData || !$userData->Model_User) {
			$userData = $model->createNew(
				$id,
				$accessToken,
				$this->_mapProperties($userDataFromVimeo)
			);
		} else {
			$model->updateLoginStats($userData->user_id);
			$userData = $userData->Model_User;
		}
		return $userData;
	}
}
