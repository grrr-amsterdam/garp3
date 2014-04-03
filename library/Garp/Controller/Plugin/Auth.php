<?php
/**
 * Garp_Controller_Plugin_Auth
 * Checks wether a visitor is authorized to execute the current action/controller.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class Garp_Controller_Plugin_Auth extends Zend_Controller_Plugin_Abstract {
	/**
	 * dispatchLoopShutdown callback
	 * @return Void
	 */
	public function dispatchLoopShutdown() {
		if (!$this->_isAuthRequest() && !$this->_isAllowed()) {
			$this->_redirectToLogin();
		}
	}


	/**
 	 * Is called after an action is dispatched by the dispatcher.
 	 * Here we force a write to the Auth cookie. Because user data may be 
 	 * read in the view, the cookie will otherwise not be written until
 	 * headers are already sent.
 	 * @param Zend_Controller_Request_Abstract $request
 	 * @return Void
 	 */
	public function postDispatch(Zend_Controller_Request_Abstract $request) {
		$store = Garp_Auth::getInstance()->getStore();
		if ($store instanceof Garp_Store_Cookie && $store->isModified()) {
			$store->writeCookie();
		}
	}
	
	
	/**
	 * See if a role is allowed to execute the current request.
	 * @return Boolean
	 */
	protected function _isAllowed() {
		// check if a user may view this page
		$request = $this->getRequest();
		return Garp_Auth::getInstance()->isAllowed(
			$request->getControllerName(),
			$request->getActionName()
		);
	}
	
	
	/**
	 * Redirect user to login page
	 * @return Void
	 */
	protected function _redirectToLogin() {
		$this->_storeTargetUrl();
	
		$auth = Garp_Auth::getInstance();
		$authVars = $auth->getConfigValues();

		$flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger');
		$flashMessenger->addMessage(
			!$auth->isLoggedIn() ? $authVars['notLoggedInMsg'] : $authVars['noPermissionMsg']
		);
		
		$redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
		$redirector->gotoUrl('/g/auth/login')
				   ->redirectAndExit();
		exit;
	}
	
	
	/**
	 * Check if the current request is for the G_AuthController
	 * @return Boolean
	 */
	protected function _isAuthRequest() {
		return 'auth' == $this->getRequest()->getControllerName();
	}
	
	
	/**
	 * Store targetUrl in session. After login the user is redirected
	 * back to this url.
	 * @return Void
	 */
	protected function _storeTargetUrl() {
		$targetUrl = $this->getRequest()->getRequestUri();
		$baseUrl = $this->getRequest()->getBaseUrl();
		/**
		 * Remove the baseUrl from the targetUrl. This is neccessary
		 * when Garp is installed in a subfolder.
		 */
		$targetUrl = str_replace($baseUrl, '', $targetUrl);
		if ($targetUrl !== '/favicon.ico') {
			$store = Garp_Auth::getInstance()->getStore();
			$store->targetUrl = $targetUrl;
		}
	}
}
