<?php
/**
 * Garp_Auth_Adapter_LinkedIn
 * Authenticate using LinkedIn
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Garp_Auth_Adapter
 */
class Garp_Auth_Adapter_Linkedin extends Garp_Auth_Adapter_Abstract {
	/**
 	* Config key
 	* @var String
 	*/
	protected $_configKey = 'linkedin';

	public function authenticate(Zend_Controller_Request_Abstract $request) {
		$callbackUrl = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://').$_SERVER['HTTP_HOST'].$request->getBaseUrl().'/g/auth/login/process/linkedin';
		$authVars = $this->_getAuthVars();
		if (!$authVars->consumerKey || !$authVars->consumerSecret) {
			throw new Garp_Auth_Exception('Required key "consumerKey" or "consumerSecret" not set in application.ini.');
		}
		$config = array(
			'siteUrl' => 'https://www.linkedin.com/uas/oauth2/authorization?response_type=code',
			'consumerKey' => $authVars->consumerKey,
			'consumerSecret' => $authVars->consumerSecret,
			'callbackUrl' => $callbackUrl
		);
		//try {
			$consumer = new Zend_Oauth_Consumer($config);
			if ($request->isPost()) {
				$token = $consumer->getRequestToken();
				$cookie = new Garp_Store_Cookie('Linkedin_request_token');
				$cookie->token = serialize($token);
				$cookie->writeCookie();
				$consumer->redirect();
				exit;
			} elseif ($request->getParam('oauth_token')) {
				$cookie = new Garp_Store_Cookie('Linkedin_request_token');
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
		//} catch (Exception $e) {
			//$this->_addError($e->getMessage());
		//}
		return false;
	}

	/*
	https://www.linkedin.com/uas/oauth2/authorization?response_type=code
                                           &client_id=YOUR_API_KEY
                                           &scope=SCOPE
                                           &state=STATE
                                           &redirect_uri=YOUR_REDIRECT_URI
*/
}
