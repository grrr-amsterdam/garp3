<?php

use function Garp\__;

/**
 * Garp_Auth_Adapter_Twitter
 * Authenticate using Twitter. Uses Zend_OAuth
 *
 * @package Garp
 * @author Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Auth_Adapter_Twitter extends Garp_Auth_Adapter_Abstract {
    /**
     * Config key
     *
     * @var String
     */
    protected $_configKey = 'twitter';

    /**
     * Authenticate a user.
     *
     * @param Zend_Controller_Request_Abstract $request The current request
     * @param Zend_Controller_Response_Abstract $response The current response
     * @return Array|Boolean User data, or FALSE
     */
    public function authenticate(
        Zend_Controller_Request_Abstract $request,
        Zend_Controller_Response_Abstract $response
    ) {
        $callbackUrl = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $request->getBaseUrl() . '/g/auth/login/process/twitter';
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
                $cookie = new Garp_Store_Cookie('Garp_Auth');
                $cookie->token = serialize($token);
                if (!empty($this->_extendedUserColumns)) {
                    $cookie->extendedUserColumns = serialize($this->_extendedUserColumns);
                }
                $cookie->writeCookie();
                $consumer->redirect();
                return true;
            }
            $cookie = new Garp_Store_Cookie('Garp_Auth');
            if ($request->getParam('oauth_token') && isset($cookie->token)) {
                $accesstoken = $consumer->getAccessToken($_GET, unserialize($cookie->token));
                // Discard request token
                if ($cookie->extendedUserColumns) {
                    $this->setExtendedUserColumns(unserialize($cookie->extendedUserColumns));
                    $cookie->destroy('extendedUserColumns');
                }
                $cookie->destroy('oauth_token');

                return $this->_getUserData(
                    $this->_getTwitterService($accesstoken, $authVars->consumerKey, $authVars->consumerSecret),
                    $accesstoken->getParam('user_id')
                );
            }

            $this->_addError('App was not authorized. Please try again.');
            return false;
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false
                && strpos($e->getMessage(), 'email_unique') !== false
            ) {
                $this->_addError(__('this email address already exists'));
                return false;
            }
            // Provide generic error message
            $this->_addError(APPLICATION_ENV === 'development'
                ? $e->getMessage()
                : __('login error')
            );
        }
        return false;
    }


    /**
     * Store the user's profile data in the database, if it doesn't exist yet.
     *
     * @param Zend_Service_Twitter $twitterService
     * @param mixed $twitterUserId
     * @return Void
     * @throws Garp_Auth_Adapter_Exception
     * @throws Garp_Auth_Exception
     */
    protected function _getUserData(Zend_Service_Twitter $twitterService, $twitterUserId) {
        $twitterUserData = $twitterService->users->show($twitterUserId);
        $userColumns = $this->_mapProperties((array)$twitterUserData->toValue());

        $userModel = new Model_User();
        $userConditions = $userModel->select()->from(
            $userModel->getName(), $this->_getSessionColumns()
        );

        $model = new Model_AuthTwitter();
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
