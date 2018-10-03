<?php
/**
 * Garp_Auth_Adapter_Passwordless
 * Allow token-based, passwordless authentication.
 * Inspired by https://passwordless.net/
 *
 * @package Garp_Auth_Adapter
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Auth_Adapter_Passwordless extends Garp_Auth_Adapter_Abstract {
    /**
     * Default token expiration time
     *
     * @var string
     */
    const DEFAULT_TOKEN_EXPIRATION_TIME = '+30 minutes';

    /**
     * Config key
     *
     * @var string
     */
    protected $_configKey = 'passwordless';

    /**
     * Authenticate a user.
     *
     * @param Zend_Controller_Request_Abstract $request   The current request
     * @param Zend_Controller_Response_Abstract $response The current response
     * @return array|bool                                 User data,
     *                                                    or FALSE when no user is logged in yet
     */
    public function authenticate(
        Zend_Controller_Request_Abstract $request,
        Zend_Controller_Response_Abstract $response
    ) {
        if (!$request->isPost()) {
            return $this->acceptToken(
                $request->getParam('token'),
                $request->getParam('uid')
            );
        }
        $this->requestToken($request->getPost());
        return false;
    }

    /**
     * Request a new token
     *
     * @param array $userData
     * @return void
     * @todo Allow different delivery-methods, such as SMS?
     */
    public function requestToken(array $userData) {
        if (empty($userData['email'])) {
            $this->_addError(
                sprintf(
                    __('%s is a required field'),
                    __('Email')
                )
            );
            return false;
        }

        $validator = new Zend_Validate_EmailAddress();
        if (!$validator->isValid($userData['email'])) {
            $this->_addError(
                sprintf(
                    __('%s is not a valid email address'),
                    __('Email')
                )
            );
            return false;
        }

        $userId = $this->_createOrFetchUserRecord($userData);
        $token  = $this->createOrUpdateAuthRecord($userId);

        $this->_sendTokenEmail($userData['email'], $userId, $token);
        $this->setRedirect($this->_getRedirectUrl());
    }

    /**
     * Accept a user token
     *
     * @param string $token
     * @param int $uid User id
     * @return Garp_Model_Db Logged in user
     */
    public function acceptToken($token, $uid) {
        if (!$token || !$uid) {
            $this->_addError(__('Insufficient data received'));
            return false;
        }
        $authPwlessModel = $this->_getPasswordlessModel();
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
        if ($this->_userIsAlreadyLoggedIn($row)) {
            return $row->Model_User;
        }

        if (!$this->_tokenIsValid($row)) {
            return false;
        }

        $authPwlessModel->getObserver('Authenticatable')->updateLoginStats(
            $row->Model_User->id, array(
                'claimed' => 1
            )
        );

        return $row->Model_User;
    }

    /**
     * Create auth record containing the token a user can log in with
     *
     * @param int $userId
     * @return string The token
     */
    public function createOrUpdateAuthRecord($userId) {
        $token = $this->_getToken($userId);
        $authPwlessModel = new Model_AuthPasswordless();
        $select = $authPwlessModel->select()->where('user_id = ?', $userId);
        if ($authRecord = $authPwlessModel->fetchRow($select)) {
            $authPwlessModel->update(
                array(
                    'token' => $token,
                    'token_expiration_date' => $this->_getExpirationDate(),
                    'claimed' => 0
                ), 'id = ' . $authRecord->id
            );
            return $token;
        }
        $authPwlessModel->insert(
            array(
                'user_id' => $userId,
                'token' => $token,
                'token_expiration_date' => $this->_getExpirationDate()
            )
        );
        return $token;
    }

    protected function _tokenIsValid(Garp_Db_Table_Row $row) {
        // If token stays valid forever, the checks below are unnecessary
        if ($this->_tokenNeverExpires()) {
            return true;
        }

        // Check expiration
        if ($this->_tokenIsExpired($row)) {
            $this->_addError(__('passwordless token expired'));
            return false;
        }

        // Check wether it was already used to log in
        if ($this->_tokenIsClaimed($row)) {
            $this->_addError(__('passwordless token claimed'));
            return false;
        }
        return true;
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

    /**
     * Generate a unique token.
     * If `reuse_existing_token` is configured thus, we will check if a token is known already for
     * the given userid.
     *
     * @param int $userId
     * @return string
     */
    protected function _getToken($userId = null) {
        if ($userId
            && $this->_getAuthVars()
            && array_get($this->_getAuthVars()->toArray(), 'reuse_existing_token')
        ) {
            return $this->_fetchExistingToken($userId) ?: $this->_getToken();
        }
        if (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes(32));
        }
        return mt_rand();
    }

    protected function _tokenNeverExpires() {
        return $this->_getAuthVars()
            && array_get($this->_getAuthVars()->toArray(), 'token_never_expires');
    }

    protected function _tokenIsClaimed(Garp_Db_Table_Row $row) {
        return intval($row->claimed) === 1;
    }

    protected function _tokenIsExpired(Garp_Db_Table_Row $row) {
        return time() > strtotime($row->token_expiration_date);
    }

    protected function _getExpirationDate() {
        return date(
            'Y-m-d H:i:s',
            strtotime($this->_getAuthVars()->token_expires_in ?? self::DEFAULT_TOKEN_EXPIRATION_TIME)
        );
    }

    protected function _sendTokenEmail($email, $userId, $token) {
        return $this->_getTokenMailer($email, $userId, $token)->send();
    }

    protected function _getTokenMailer($email, $userId, $token): Garp_Auth_Adapter_Passwordless_TokenMailerAbstract {
        $authVars = $this->_getAuthVars();
        $className = $authVars->token_mailer_class;
        return class_exists($className)
            ? new $className($email, $userId, $token, $authVars)
            : new Garp_Auth_Adapter_Passwordless_TokenMailer($email, $userId, $token, $this->_getAuthVars());
    }

    protected function _getRedirectUrl() {
        $authVars = $this->_getAuthVars();
        $route = 'home';
        if (isset($authVars->requesttoken_redirect_route)) {
            $route = $authVars->requesttoken_redirect_route;
        }
        return new Garp_Util_FullUrl(array(array(), $route));
    }

    protected function _getCurrentUserData() {
        $auth = Garp_Auth::getInstance();
        if ($auth->isLoggedIn()) {
            return $auth->getUserData();
        }
        return null;
    }

    protected function _userIsAlreadyLoggedIn(Garp_Db_Table_Row $row) {
        $currentUserData = $this->_getCurrentUserData();
        return isset($currentUserData['id'])
            && intval($currentUserData['id']) === intval($row->Model_User->id);
    }

    protected function _getPasswordlessModel() {
        $authPwlessModel = new Model_AuthPasswordless();
        $userModel = new Model_User();
        $userConditions = $userModel->select()->from(
            $userModel->getName(),
            Garp_Auth::getInstance()->getSessionColumns()
        );
        $authPwlessModel->bindModel(
            'Model_User', array(
                'conditions' => $userConditions,
                'rule' => 'User'
            )
        );
        return $authPwlessModel;
    }

    protected function _fetchExistingToken($userId) {
        $authPwlessModel = new Model_AuthPasswordless();
        $existingRow = $authPwlessModel->fetchByUserId($userId);
        return $existingRow ? $existingRow['token'] : null;
    }
}


