<?php
/**
 * Garp_Auth_Adapter_Passwordless
 * Allow token-based, passwordless authentication.
 * Inspired by https://passwordless.net/
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Garp_Auth_Adapter
 */
class Garp_Auth_Adapter_Passwordless extends Garp_Auth_Adapter_Abstract {
	const DEFAULT_TOKEN_EXPIRATION_TIME = '+30 minutes';

	/**
	 * Config key
	 * @var String
	 */
	protected $_configKey = 'passwordless';

	/**
	 * Authenticate a user.
	 * @param Zend_Controller_Request_Abstract $request The current request
	 * @param Zend_Controller_Response_Abstract $response The current response
	 * @return Array|Boolean User data, or FALSE when no user is logged in yet
	 */
	public function authenticate(Zend_Controller_Request_Abstract $request,
		Zend_Controller_Response_Abstract $response) {
		if (!$request->isPost()) {
			return $this->acceptToken($request->getParam('token'),
				$request->getParam('uid'));
		}
		$this->requestToken($request->getPost());
		return false;
	}

	/**
 	 * Request a new token
 	 * @todo Allow different delivery-methods, such as SMS?
 	 */
	public function requestToken(array $userData) {
		if (empty($userData['email'])) {
			$this->_addError(sprintf(__('%s is a required field'),
				__('Email')));
			return false;
		}
		$userId = $this->_createOrFetchUserRecord($userData);
		$token  = $this->_createOrUpdateAuthRecord($userId);

		$this->_sendTokenEmail($userData['email'], $userId, $token);

		$this->setRedirect($this->_getRedirectUrl());
	}

	/**
 	 * Accept a user token
 	 * @param String $token
 	 * @param Int $uid User id
 	 * @return Garp_Model_Db Logged in user
 	 */
	public function acceptToken($token, $uid) {
		if (!$token || !$uid) {
			$this->_addError(__('Insufficient data received'));
			return false;
		}
		$authPwlessModel = new Model_AuthPasswordless();
		$userModel = new Model_User();
		$userConditions = $userModel->select()->from($userModel->getName(),
			Garp_Auth::getInstance()->getSessionColumns());
		$authPwlessModel->bindModel('Model_User', array(
			'conditions' => $userConditions,
			'rule' => 'User'
		));
		$select = $authPwlessModel->select()
			->where('`token` = ?', $token)
			->where('user_id = ?', $uid);

		$row = $authPwlessModel->fetchRow($select);
		if (!$row || !$row->Model_User) {
			$this->_addError(__('passwordless token not found'));
			return false;
		}

		// Check wether the user is already logged in. Let's not inconvenience them
		// with security when it's not that important
		$currentUserData = $this->_getCurrentUserData();
		if (isset($currentUserData['id']) &&
			(int)$currentUserData['id'] === (int)$row->Model_User->id) {
			return $row->Model_User;
		}

		// Check expiration
		if (time() > strtotime($row->token_expiration_date)) {
			$this->_addError(__('passwordless token expired'));
			return false;
		}

		// Check wether it was already used to log in
		if ((int)$row->claimed === 1) {
			$this->_addError(__('passwordless token claimed'));
			return false;
		}

		$authPwlessModel->getObserver('Authenticatable')->updateLoginStats($row->Model_User->id, array(
			'claimed' => 1
		));

		return $row->Model_User;
	}

	protected function _createOrFetchUserRecord(array $userData) {
		$userModel = new Model_User();
		$userData = $userModel->filterColumns($userData);
		$select = $userModel->select()->where('email = ?', $userData['email']);
		if ($userRecord = $userModel->fetchRow($select)) {
			return $userRecord->id;
		}
		return $userModel->insert($userData);
	}

	protected function _createOrUpdateAuthRecord($userId) {
		$token = $this->_getToken();
		$authPwlessModel = new Model_AuthPasswordless();
		$select = $authPwlessModel->select()->where('user_id = ?', $userId);
		if ($authRecord = $authPwlessModel->fetchRow($select)) {
			$authPwlessModel->update(array(
				'token' => $token,
				'token_expiration_date' => $this->_getExpirationDate(),
				'claimed' => 0
			), 'id = ' . $authRecord->id);
			return $token;
		}
		$authPwlessModel->insert(array(
			'user_id' => $userId,
			'token' => $token,
			'token_expiration_date' => $this->_getExpirationDate()
		));

		return $token;
	}

	protected function _getToken() {
		if (function_exists('openssl_random_pseudo_bytes')) {
			return bin2hex(openssl_random_pseudo_bytes(32));
		}
		return mt_rand();
	}

	protected function _getExpirationDate() {
		if ($this->_getAuthVars() && array_key_exists('token_expires_in', $this->_getAuthVars())) {
			$authVars = $this->_getAuthVars();
			return date('Y-m-d H:i:s', strtotime($authVars['token_expires_in']));
		}
		return date('Y-m-d H:i:s', strtotime(self::DEFAULT_TOKEN_EXPIRATION_TIME));
	}

	protected function _sendTokenEmail($email, $userId, $token) {
		$mailer = new Garp_Mailer();
		return $mailer->send(array(
			'to' => $email,
			'subject' => $this->_getEmailSubject(),
			'message' => $this->_getEmailBody($userId, $token)
		));
	}

	protected function _getEmailBody($userId, $token) {
		$authVars = $this->_getAuthVars();
		if (!empty($authVars->email_body_snippet_identifier) &&
			$authVars->email_body_snippet_identifier) {
			return $this->_interpolateEmailBody(
				$this->_getSnippet($authVars->email_body_snippet_identifier), $userId, $token);
		}
		if (!empty($authVars->email_body)) {
			return $this->_interpolateEmailBody($authVars->email_body, $userId, $token);
		}

		throw new Garp_Auth_Adapter_Passwordless_Exception('Missing email body: configure a ' .
			'snippet or hard-code a string.');
	}

	protected function _getEmailSubject() {
		$authVars = $this->_getAuthVars();
		if (isset($authVars->email_subject_snippet_identifier) &&
			$authVars->email_subject_snippet_identifier) {
			return $this->_getSnippet($authVars->email_subject_snippet_identifier);
		}
		if (isset($authVars->email_subject) && $authVars->email_subject) {
			return $authVars->email_subject;
		}

		throw new Garp_Auth_Adapter_Passwordless_Exception('Missing email subject: configure a ' .
			'snippet or hard-code a string.');

	}

	protected function _interpolateEmailBody($body, $userId, $token) {
		return Garp_Util_String::interpolate($body, array(
			'LOGIN_URL' => $this->_getLoginUrl($userId, $token)
		));
	}

	protected function _getLoginUrl($userId, $token) {
		return new Garp_Util_FullUrl(array(array('method' => 'passwordless'), 'auth_submit')) .
			'?uid=' . $userId . '&token=' . $token;
	}

	protected function _getRedirectUrl() {
		$authVars = $this->_getAuthVars();
		$route = 'home';
		if (isset($authVars->requesttoken_redirect_route)) {
			$route = $authVars->requesttoken_redirect_route;
		}
		return new Garp_Util_FullUrl(array(array(), $route));
	}

	protected function _getSnippet($identifier) {
		$snippetModel = new Model_Snippet();
		if ($snippetModel->isMultilingual()) {
			$snippetModel = instance(new Garp_I18n_ModelFactory)->getModel('Snippet');
		}
		return $snippetModel->fetchByIdentifier($identifier)->text;
	}

	protected function _getCurrentUserData() {
		$auth = Garp_Auth::getInstance();
		if ($auth->isLoggedIn()) {
			return $auth->getUserData();
		}
		return null;
	}
}
