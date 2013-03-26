<?php
/**
 * G_AuthController
 * This controller handles logging users in and out.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class G_AuthController extends Garp_Controller_Action {
	/**
	 * Index page, just redirects to $this->loginAction().
	 * It's only here because it might be handy to have a landing page someday.
	 * @return Void
	 */
	public function indexAction() {
		$this->_redirect('/g/auth/login');
	}
	

	/**
 	 * Register a new account
 	 * @return Void
 	 */
	public function registerAction() {
		$this->view->title = 'Registreren';
		$authVars = Garp_Auth::getInstance()->getConfigValues();
		
		if ($this->getRequest()->isPost()) {
			$errors = array();
			$postData = $this->getRequest()->getPost();
			$this->view->postData = $postData;

			// Apply some mild validation
			$password = $this->getRequest()->getPost('password');

			$checkRepeatPassword = !empty($authVars['register']['repeatPassword']) && $authVars['register']['repeatPassword'];
			if ($checkRepeatPassword) {
				$repeatPasswordField = $this->getRequest()->getPost($authVars['register']['repeatPasswordField']);
				unset($postData[$authVars['register']['repeatPasswordField']]);
				if ($password != $repeatPasswordField) {
					$errors[] = 'De wachtwoorden komen niet overeen.';
				}
			}

			if (!$errors) {
				// Save the new user
				$userModel = new Model_User();
				try {
					// Before register hook
					$this->_beforeRegister($postData);

					$insertId = $userModel->insert($postData);
					$this->_helper->flashMessenger($authVars['register']['successMessage']);

					// Store new user directly thru Garp_Auth so that they're logged in immediately
					$newUser = $userModel->find($insertId)->current();

					$auth = Garp_Auth::getInstance();
					$auth->store($newUser->toArray(), 'db');

					// After register hook
					$this->_afterRegister();

					$this->_redirect($authVars['register']['successUrl']);
				} catch (Zend_Db_Statement_Exception $e) {
					if (strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'email_unique') !== false) {
						$errors[] = 'Dit e-mailadres is al in gebruik op deze website.';
					} else {
						throw $e;
					}
				} catch (Exception $e) {
					$errors[] = 'Er is iets misgegaan bij het registreren. Probeer het later nog eens';
				}
			}
			$this->view->errors = $errors;
		}

		// Show view
		$this->_renderView($authVars['register']);
	}


	/**
	 * Show a login page.
	 * Note that $this->processAction does the actual logging in.
	 * This separation is useful because some 3rd parties send back
	 * GET variables instead of POST. This way we don't need to 
	 * worry about that here.
	 * @return Void
	 */
	public function loginAction() {
		$this->view->title = 'Inloggen';
		$this->view->description = 'Log hier in om toegang te krijgen tot persoonlijke pagina\'s.';
		
		// allow callers to set a targetUrl via the request
		if ($this->getRequest()->getParam('targetUrl')) {
			$targetUrl = $this->getRequest()->getParam('targetUrl');
			Garp_Auth::getInstance()->getStore()->targetUrl = $targetUrl;
		}

		$authVars = Garp_Auth::getInstance()->getConfigValues();
		// self::processAction might have populated 'errors'
		if ($this->getRequest()->getParam('errors')) {
			$this->view->errors = $this->getRequest()->getParam('errors');
		}

		// Show view
		$this->_renderView($authVars['login']);
	}


	/**
	 * Process the login request. @see G_AuthController::loginAction as to 
	 * why this is separate.
	 * @return Void
	 */
	public function processAction() {
		// This action does not render a view, it only redirects elsewhere.
		$this->_helper->viewRenderer->setNoRender(true);
		$method = $this->getRequest()->getParam('method') ?: 'db';
		$adapter = Garp_Auth_Factory::getAdapter($method);
		$authVars = Garp_Auth::getInstance()->getConfigValues();

		// Before login hook.
		$this->_beforeLogin($authVars, $adapter);

		/**
		 * Params can come from GET or POST.
		 * The implementing adapter should decide which to use,
		 * using the current request to fetch params.
		 */
		if ($userData = $adapter->authenticate($this->getRequest())) {
			if ($userData instanceof Garp_Db_Table_Row) {
				$userData = $userData->toArray();
			}
			
			// Save user data in a store.
			Garp_Auth::getInstance()->store($userData, $method);

			// Store User role in a cookie, so that we can use it with Javascript.
			if (!Garp_Auth::getInstance()->getStore() instanceof Garp_Store_Cookie) {
				$this->_storeRoleInCookie();
			}

			// Determine targetUrl. This is the URL the user was trying to access before logging in, or a default URL.
			$targetUrl = !empty($authVars['login']['successUrl']) ? $authVars['login']['successUrl'] : '/';
			$store = Garp_Auth::getInstance()->getStore();
			if ($targetUrl = $store->targetUrl) {
				unset($store->targetUrl);
			}

			// After login hook.
			$this->_afterLogin($userData, $targetUrl);

			// Set a Flash message welcoming the user.
			$flashMessenger = $this->_helper->getHelper('FlashMessenger');
			$fullName = new Garp_Util_FullName($userData);
			$successMessage = sprintf($authVars['login']['successMessage'], $fullName);
			$flashMessenger->addMessage($successMessage);
			$this->_redirect($targetUrl);
			exit;
		} else {
			// Show the login page again.
			$request = clone $this->getRequest();
			$request->setActionName('login')
				->setParam('errors', $adapter->getErrors());
			$this->_helper->actionStack($request);
		}
	}


	/**
	 * Log a user out.
	 * @return Void
	 */
	public function logoutAction() {
		$auth = Garp_Auth::getInstance();
		$userData = $auth->getUserData();
		$this->_beforeLogout($userData);

		$auth->destroy();
		$authVars = $auth->getConfigValues();
		$target = '/';
		if ($authVars && !empty($authVars['logout']['successUrl'])) {
			$target = $authVars['logout']['successUrl'];
		}

		// Remove the role cookie
		if (!$auth->getStore() instanceof Garp_Store_Cookie) {
			$this->_removeRoleCookie();
		}

		$this->_afterLogout($userData);

		$flashMessenger = $this->_helper->getHelper('FlashMessenger');
		$flashMessenger->addMessage($authVars['logout']['successMessage']);
		$this->_redirect($target);
	}


	/**
 	 * Forgot password
 	 * @return Void
 	 */
	public function forgotpasswordAction() {
		$this->view->title = 'Wachtwoord vergeten';
		$auth = Garp_Auth::getInstance();
		$authVars = $auth->getConfigValues();
		$request = $this->getRequest();

		if ($request->getParam('success') == '1') {
			$this->view->successMessage = $authVars['forgotpassword']['success_message'];
		}

		if ($request->isPost()) {
			// Honeypot validation
			$hp = $request->getPost('hp');
			if (!empty($hp)) {
				throw new Garp_Auth_Exception('Je hebt een veld ingevuld dat leeg moest blijven.');
			}

			// Find user by email address
			$this->view->email = $email = $request->getPost('email'); 
			$userModel = new Model_User();
			$user = $userModel->fetchRow(
				$userModel->select()->where('email = ?', $email)
			);
			if (!$user) {
				$this->view->formError = 'Dit e-mailadres is bij ons niet bekend.';
			} else {
				// Update user
				$activationToken = uniqid();
				$activationCode  = '';
				$activationCode .= $activationToken;
				$activationCode .= md5($email);
				$activationCode .= md5($authVars['salt']);
				$activationCode .= md5($user->id);
				$activationCode = md5($activationCode);
				$activationUrl = '/g/auth/resetpassword/c/'.$activationCode.'/e/'.md5($email).'/';

				$activationCodeExpiresColumn = $authVars['forgotpassword']['activation_code_expiration_date_column'];
				$activationTokenColumn = $authVars['forgotpassword']['activation_token_column'];
				$activationCodeExpiry = date('Y-m-d', strtotime($authVars['forgotpassword']['activation_code_expires_in']));

				$user->{$activationCodeExpiresColumn} = $activationCodeExpiry;
				$user->{$activationTokenColumn} = $activationToken;

				if ($user->save()) {
					// Render the email message
					$this->_helper->layout->disableLayout();
					$this->view->user = $user;
					$this->view->activationUrl = $activationUrl;
					// Add "default" module as a script path so the partial can 
					// be found.
					$this->view->addScriptPath(APPLICATION_PATH.'/modules/default/views/scripts/');
					$emailMessage = $this->view->render($authVars['forgotpassword']['email_partial']);
				
					// Send mail to the user
					// @todo Make this more transparent. Use a Strategy design pattern for instance.
					$emailMethod = 'ses';
					if (!empty($authVars['forgotpassword']['email_method'])) {
						$emailMethod = $authVars['forgotpassword']['email_method'];
					}
					if ($emailMethod === 'ses') {
						$ses = new Garp_Service_Amazon_Ses();
						$response = $ses->sendEmail(array(
							'Destination' => $email,
							'Message'     => $emailMessage,
							'Subject'     => $authVars['forgotpassword']['email_subject'],
							'Source'      => $authVars['forgotpassword']['email_from_address']
						));
					} elseif ($emailMethod === 'zend') {
						$mail = new Zend_Mail();
						$mail->setBodyText($emailMessage);
						$mail->setFrom($authVars['forgotpassword']['email_from_address']);
						$mail->addTo($email);
						$mail->setSubject($authVars['forgotpassword']['email_subject']);
						$response = $mail->send();
					} else {
						throw new Garp_Auth_Exception('Unknown email_method chosen. '.
							'Please reconfigure auth.forgotpassword.email_method');
					}
					if ($response) {
						$this->_redirect($authVars['forgotpassword']['url'].'?success=1');
					} else {
						$this->view->formError = $authVars['forgotpassword']['failure_message'];
					}
				}
			}
		}
		
		// Show view
		$this->_helper->layout->setLayout('default');
		$this->_renderView($authVars['forgotpassword']);
	}


	/**
 	 * Allow a user to reset his password after he had forgotten it.
 	 */
	public function resetpasswordAction() {
		$this->view->title = 'Stel je wachtwoord opnieuw in';
		$auth = Garp_Auth::getInstance();
		$authVars = $auth->getConfigValues();
		$request = $this->getRequest();
		$activationCode = $request->getParam('c');
		$activationEmail = $request->getParam('e');
		$expirationColumn = $authVars['forgotpassword']['activation_code_expiration_date_column'];

		$userModel = new Model_User();
		$activationCodeClause = 
			'MD5(CONCAT('.
				$userModel->getAdapter()->quoteIdentifier($authVars['forgotpassword']['activation_token_column']).','.
				'MD5(email),'.
				'MD5('.$userModel->getAdapter()->quote($authVars['salt']).'),'.
				'MD5(id)'.
			')) = ?'
		;
		$select = $userModel->select()
			// check if a user matches up to the given code
			->where($activationCodeClause, $activationCode)
			// check if the given email address is part of the same user record
			->where('MD5(email) = ?', $activationEmail)
		;

		$user = $userModel->fetchRow($select);
		if (!$user) {
			$this->view->error = 'Er is geen gebruiker gevonden met de opgegeven gegevens.';
		} elseif (strtotime($user->{$expirationColumn}) < time()) {
			$this->view->error = 'Deze link is verlopen.';
		} else {
			if ($request->isPost()) {
				$password = $request->getPost('password');
				if (!$password) {
					$this->view->formError = 'Wachtwoord is een verplicht veld.';
				} else {
					// Update the user's password and send him along to the login page
					$updateClause = $userModel->getAdapter()->quoteInto('id = ?', $user->id);
					$userModel->update(array(
						'password' => $password,
						$authVars['forgotpassword']['activation_token_column'] => null,
						$authVars['forgotpassword']['activation_code_expiration_date_column'] => null
					), $updateClause);
					$this->_helper->flashMessenger($authVars['resetpassword']['success_message']);
					$this->_redirect('/g/auth/login');
				}
			}
		}

		// Show view
		$this->_renderView($authVars['resetpassword']);
	}


	/**
 	 * Validate email address. In scenarios where users receive an email validation email, 
 	 * this action is used to validate the address.
 	 */
	public function validateemailAction() {
		$this->view->title = 'Activeer e-mailadres';
		$auth = Garp_Auth::getInstance();
		$authVars = $auth->getConfigValues();
		$request = $this->getRequest();
		$activationCode = $request->getParam('c');
		$activationEmail = $request->getParam('e');
		$emailValidColumn = $authVars['validateEmail']['email_valid_column'];

		$userModel = new Model_User();
		// always collect fresh data for this one
		$userModel->setCacheQueries(false);
		$activationCodeClause = 
			'MD5(CONCAT('.
				$userModel->getAdapter()->quoteIdentifier($authVars['validateEmail']['token_column']).','.
				'MD5(email),'.
				'MD5('.$userModel->getAdapter()->quote($authVars['salt']).'),'.
				'MD5(id)'.
			')) = ?'
		;
		$select = $userModel->select()
			// check if a user matches up to the given code
			->where($activationCodeClause, $activationCode)
			// check if the given email address is part of the same user record
			->where('MD5(email) = ?', $activationEmail)
		;

		$user = $userModel->fetchRow($select);
		if (!$user) {
			$this->view->error = 'De opgegeven code is ongeldig.';
		} else {
			$user->{$emailValidColumn} = 1;
			if (!$user->save()) {
				$this->view->error = 'Er is een onbekende fout opgetreden, je e-mailadres kon niet worden geactiveerd. Probeer het later nog eens.';
			} elseif ($auth->isLoggedIn()) {
				// If the user is currently logged in, update the cookie
				$method = $auth->getStore()->method;
				$userData = $auth->getUserData();
				// Sanity check: is the user that has just validated his email address the currently logged in user?
				if ($userData['id'] == $user->id) {
					$userData[$emailValidColumn] = 1;
					$auth->store($userData, $method);
				}
			}
		}

		// Show view
		$this->_renderView($authVars['validateEmail']);
	}


	/**
 	 * Render a configured view
 	 * @param Array $authVars Configuration for a specific auth section.
 	 * @return Void
 	 */
	protected function _renderView($authVars) {
		$view = $authVars['view'];
		if (strpos($view, '.phtml') === false) {
			$view .= '.phtml';
		}

		$this->_helper->viewRenderer->setNoRender();
		$this->view->addScriptPath(APPLICATION_PATH.'/modules/'.$authVars['module'].'/views/scripts/auth');
		$this->_helper->layout->setLayoutPath(APPLICATION_PATH.'/modules/'.$authVars['module'].'/views/layouts');

		$layout = $authVars['layout'];
		if ($this->_helper->layout->isEnabled()) {
			$this->_helper->layout->setLayout($layout);
		}
		$this->getResponse()->setBody($this->view->render($view));
	}


	/**
 	 * Store user role in cookie, so it can be used with Javascript
 	 * @return Void
 	 */
	protected function _storeRoleInCookie() {
		$userRecord = Garp_Auth::getInstance()->getUserData();
		if (!empty($userRecord['role'])) {
			$cookie = new Garp_Store_Cookie('Garp_Auth');
			$cookie->userData = array('role' => $userRecord['role']);
		}
	}


	/**
 	 * Remove role cookie
 	 * @return Void
 	 */
	protected function _removeRoleCookie() {
		// Use the cookie store to destroy the cookie.
		$store = new Garp_Store_Cookie('Garp_Auth');
		$store->destroy();
	}


	/**
 	 * Before register hook
 	 * @param Array $postData 
 	 * @return Void
 	 */
	protected function _beforeRegister(array &$postData) {
		if ($registerHelper = $this->_getRegisterHelper()) {
			$registerHelper->beforeRegister($postData);
		}
	}


	/**
 	 * After register hook
 	 * @return Void
 	 */
	protected function _afterRegister() {
		if ($registerHelper = $this->_getRegisterHelper()) {
			$registerHelper->afterRegister();
		}
	}


	/**
 	 * Before login hook
 	 * @param Array $authVars Containing auth-related configuration.
 	 * @param Garp_Auth_Adapter_Abstract $adapter The chosen adapter.
 	 * @return Void
 	 */
	protected function _beforeLogin(array $authVars, Garp_Auth_Adapter_Abstract $adapter) {
		if ($loginHelper = $this->_getLoginHelper()) {
			$loginHelper->beforeLogin($authVars, $adapter);
		}
	}


	/**
 	 * After login hook
 	 * @param Array $userData The data of the logged in user
 	 * @param String $targetUrl The URL the user is being redirected to
 	 * @return Void
 	 */
	protected function _afterLogin(array $userData, $targetUrl) {
		if ($loginHelper = $this->_getLoginHelper()) {
			$loginHelper->afterLogin($userData, $targetUrl);
		}
	}


	/**
 	 * Before logout hook
 	 * @param Array $userData The current user's data
 	 * @return Void
 	 */
	protected function _beforeLogout($userData) {
		if ($loginHelper = $this->_getLoginHelper()) {
			$loginHelper->beforeLogout($userData);
		}
	}


	/**
 	 * Before login hook
	 * @param Array $userData The current user's data
 	 * @return Void
 	 */
	protected function _afterLogout($userData) {
		if ($loginHelper = $this->_getLoginHelper()) {
			$loginHelper->afterLogout($userData);
		}
	}


	/**
 	 * Get the Login helper, if registered.
 	 * @return Zend_Controller_Action_Helper_Abstract
 	 */
	protected function _getLoginHelper() {
		if ($loginHelper = $this->_helper->getHelper('Login')) {
			if (!$loginHelper instanceof Garp_Controller_Helper_Login) {
				throw new Garp_Auth_Exception('A Login Helper is registered, but not of type Garp_Controller_Helper_Login.');
			}
			return $loginHelper;
		}
		return null;
	}


	/**
 	 * Get the Register helper, if registered.
 	 * @return Zend_Controller_Action_Helper_Abstract
 	 */
	protected function _getRegisterHelper() {
		if ($registerHelper = $this->_helper->getHelper('Register')) {
			if (!$registerHelper instanceof Garp_Controller_Helper_Register) {
				throw new Garp_Auth_Exception('A Register Helper is registered, but not of type Garp_Controller_Helper_Register.');
			}
			return $registerHelper;
		}
		return null;
	}
}
