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
		exit;
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

		$loginView = $authVars['loginView'];
		if (strpos($loginView, '.phtml') === false) {
			$loginView .= '.phtml';
		}

		$this->_helper->viewRenderer->setNoRender();
		$this->view->addScriptPath(APPLICATION_PATH.'/modules/'.$authVars['loginModule'].'/views/scripts/auth');
		$this->_helper->layout->setLayoutPath(APPLICATION_PATH.'/modules/'.$authVars['loginModule'].'/views/layouts');

		$layout = $authVars['layoutView'];
		if ($this->_helper->layout->isEnabled()) {
			$this->_helper->layout->setLayout($layout);
		}
		$this->getResponse()->setBody($this->view->render($loginView));
	}


	/**
	 * Process the login request. @see G_AuthController::loginAction as to 
	 * why this is separate.
	 * @return Void
	 */
	public function processAction() {
		$this->_helper->viewRenderer->setNoRender(true);

		$method = $this->getRequest()->getParam('method') ?: 'db';
		$adapter = Garp_Auth_Factory::getAdapter($method);
		$authVars = Garp_Auth::getInstance()->getConfigValues();
		/**
		 * Params can come from GET or POST.
		 * The implementing adapter should decide which to use,
		 * using the current request to fetch params.
		 */
		if ($userData = $adapter->authenticate($this->getRequest())) {
			if ($userData instanceof Garp_Db_Table_Row) {
				$userData = $userData->toArray();
			}
			Garp_Auth::getInstance()->store($userData, $method);
			if (!Garp_Auth::getInstance()->getStore() instanceof Garp_Store_Cookie) {
				// Store User role in a cookie, so that we can use it with Javascript
				$this->_storeRoleInCookie();
			}
			$targetUrl = $authVars['loginSuccessUrl'];
			$store = Garp_Auth::getInstance()->getStore();
			if ($targetUrl = $store->targetUrl) {
				unset($store->targetUrl);
			}

			$flashMessenger = $this->_helper->getHelper('FlashMessenger');
			$flashMessenger->addMessage($authVars['loginSuccessMessage']);
			$this->_redirect($targetUrl);
			exit;
		} else {
			// show the login page again
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
		Garp_Auth::getInstance()->destroy();
		$config = Garp_Auth::getInstance()->getConfigValues();
		$target = '/';
		if ($config && !empty($config['logoutUrl'])) {
			$target = $config['logoutUrl'];
		}

		// Remove the role cookie
		if (!Garp_Auth::getInstance()->getStore() instanceof Garp_Store_Cookie) {
			$this->_removeRoleCookie();
		}

		$flashMessenger = $this->_helper->getHelper('FlashMessenger');
		$flashMessenger->addMessage($config['logoutSuccessMessage']);
		$this->_redirect($target);
		exit;
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
}
