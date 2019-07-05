<?php

use function Garp\__;

/**
 * Garp_Controller_Plugin_Auth
 * Checks wether a visitor is authorized to execute the current action/controller.
 *
 * @package Garp
 * @author Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Controller_Plugin_Auth extends Zend_Controller_Plugin_Abstract {

    public function preDispatch(Zend_Controller_Request_Abstract $request) {
        /**
         * First check if this request can actually be dispatched.
         * If not, we don't have to redirect to login
         */
        $frontController = Zend_Controller_Front::getInstance();
        $dispatcher = $frontController->getDispatcher();
        if (!$dispatcher->isDispatchable($request)) {
            // let nature run its course
            return true;
        }
        if ($this->_isAuthRequest() || $this->_isAllowed()) {
            return true;
        }

        if ($this->getRequest()->isXmlHttpRequest()) {
            /**
             * In an AJAX environment it's not very useful to redirect to the login page.
             * Instead, we configure a 403 unauthorized header, we send the headers early
             * and exit the whole shebang.
             */
            $this->getResponse()->setHttpResponseCode(403);
            $this->getResponse()->sendHeaders();
            return true;
        }

        // If you make it over here, you are not authorized and are redirected to the login page
        $this->_redirectToLogin();
    }

    /**
     * Is called after an action is dispatched by the dispatcher.
     * Here we force a write to the Auth cookie. Because user data may be
     * read in the view, the cookie will otherwise not be written until
     * headers are already sent.
     *
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
     *
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
     *
     * @return Void
     */
    protected function _redirectToLogin() {
        $this->_storeTargetUrl();

        $auth = Garp_Auth::getInstance();
        $authVars = $auth->getConfigValues();

        $flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger');
        $flashMessenger->addMessage(!$auth->isLoggedIn()
            ? __($authVars['notLoggedInMsg'])
            : __($authVars['noPermissionMsg'])
        );

        // Make sure the redirect is not cached
        Zend_Controller_Action_HelperBroker::getStaticHelper('cache')->setNoCacheHeaders(
            $this->getResponse()
        );

        $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
        $redirectMethod = 'gotoUrlAndExit';
        $redirectParams = array('/g/auth/login');

        if (!empty($authVars['login']['route'])) {
            $redirectMethod = 'gotoRoute';
            $redirectParams = array(array(), $authVars['login']['route']);
        }
        call_user_func_array(array($redirector, $redirectMethod), $redirectParams);
    }

    /**
     * Check if the current request is for the G_AuthController
     *
     * @return Boolean
     */
    protected function _isAuthRequest() {
        return 'auth' == $this->getRequest()->getControllerName();
    }

    /**
     * Store targetUrl in session. After login the user is redirected
     * back to this url.
     *
     * @return Void
     */
    protected function _storeTargetUrl() {
        $request = $this->getRequest();
        // Only store targetUrl when method = GET. A redirect to a POST request is useless.
        if (!$request->isGet()) {
            return;
        }

        // Allow ?targetUrl=/path/to/elsewhere on any URL
        if (!$targetUrl = $request->getParam('targetUrl')) {
            $targetUrl = $request->getRequestUri();
            $baseUrl = $request->getBaseUrl();
            /**
             * Remove the baseUrl from the targetUrl. This is neccessary
             * when Garp is installed in a subfolder.
             */
            $targetUrl = Garp_Util_String::strReplaceOnce($baseUrl, '', $targetUrl);
        }

        if ($targetUrl !== '/favicon.ico' && !$request->isXmlHttpRequest()) {
            $store = Garp_Auth::getInstance()->getStore();
            $store->targetUrl = $targetUrl;
        }
    }
}
