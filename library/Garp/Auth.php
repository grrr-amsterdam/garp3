<?php

/**
 * Garp_Auth
 * Handles all kinds of authentication related stuff.
 *
 * @package Garp
 * @author Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Auth {
    /**
     * Name of the column that stores the user's role
     */
    const ROLE_COLUMN = 'role';

    /**
     * Role when nobody is logged in
     */
    const DEFAULT_VISITOR_ROLE = 'visitor';

    /**
     * Default role when a user is logged in
     */
    const DEFAULT_USER_ROLE = 'user';

    /**
     * Singleton instance
     *
     * @var Garp_Auth
     */
    private static $_instance = null;

    /**
     * Storage
     *
     * @var Garp_Auth_Store
     */
    protected $_store;

    /**
     * Some config defaults
     *
     * @var Array
     */
    protected $_defaultConfigValues = array(
        'loginModule' => 'default',
        'loginView' => 'login',
        'layoutView' => 'layout',
        'loginSuccessUrl' => '/',
        'loginSuccessMessage' => 'You are successfully logged in',
        'logoutSuccessMessage' => 'You are now logged out',
        'salt' => 'you should really fill this in application.ini'
    );

    /**
     * Private constructor. Here be Singletons.
     *
     * @param Garp_Store_Interface $store Session or cookie, for instance
     * @return Void
     */
    private function __construct(Garp_Store_Interface $store = null) {
        $this->setStore($store ?: Garp_Store_Factory::getStore('Garp_Auth'));
    }

    /**
     * Get Garp_Auth instance
     *
     * @param Garp_Store_Interface $store Session or cookie, for instance
     * @return Garp_Auth
     */
    public static function getInstance(Garp_Store_Interface $store = null) {
        if (!Garp_Auth::$_instance) {
            Garp_Auth::$_instance = new Garp_Auth($store);
        }
        return Garp_Auth::$_instance;
    }

    /**
     * Return the currently used storage object
     *
     * @return Garp_Store_Interface
     */
    public function getStore() {
        return $this->_store;
    }

    /**
     * Set storage object
     *
     * @param Garp_Store_Interface $store
     * @return Garp_Auth
     */
    public function setStore(Garp_Store_Interface $store) {
        $this->_store = $store;
        return $this;
    }

    /**
     * Check if a user is logged in
     *
     * @return Boolean
     */
    public function isLoggedIn() {
        $hasUserData = isset($this->_store->userData);
        $hasLoginMethod = isset($this->_store->method);
        $hasValidToken = isset($this->_store->token) && $this->validateToken();
        $isLoggedIn = $hasUserData && $hasLoginMethod && $hasValidToken;

        // Don't leave invalid cookies laying around.
        // Clear data only when data is present, but it is invalid.
        if ($hasUserData && $hasLoginMethod && !$hasValidToken) {
            $this->_store->userData = null;
            $this->_store->method = null;
        }
        return $isLoggedIn;
    }

    /**
     * Get data from logged in user
     *
     * @return Array
     */
    public function getUserData() {
        return $this->_store->userData;
    }

    /**
     * Convenience method for grabbing id of the currently logged in user
     *
     * @return Int
     */
    public function getUserId() {
        $data = $this->getUserData();
        return !empty($data['id']) ? $data['id'] : null;
    }

    /**
     * Create a unique token for the currently logged in user.
     *
     * @param String $input Serialized user data
     * @return String
     */
    public function createToken($input) {
        $config = $this->getConfigValues();
        $salt = $config['salt'];

        $token = '';
        $token .= !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $token .= md5($input);
        $token .= md5($salt);

        /**
         * Embed an outline of the User table columns in the token. That way, whenever the
         * database changes, all current cookies are made invalid and users have to generate a
         * new cookie afresh by logging in.
         * This ensures the user cookies always contains all the columns.
         */
        $columns = '';
        try {
            $userModel = new Model_User();
            $columns = $userModel->info(Zend_Db_Table_Abstract::COLS);
            $columns = implode('.', $columns);
        } catch (Zend_Db_Adapter_Exception $e) {
            Garp_ErrorHandler::handlePrematureException($e);
        }

        $token .= $columns;
        $token = md5($token);

        return $token;
    }

    /**
     * Validate the current token
     *
     * @return Boolean
     */
    public function validateToken() {
        $userData = $this->_store->userData;
        $currToken = $this->_store->token;
        $checkToken = $this->createToken(serialize($userData));
        return $checkToken === $currToken;
    }

    /**
     * Store user data in session
     *
     * @param Mixed $data The user data
     * @param String $method The method used to login
     * @return Void
     */
    public function store($data, $method = 'db') {
        $token = $this->createToken(serialize($data));
        $this->_store->userData = $data;
        $this->_store->method = $method;
        $this->_store->token = $token;
        return $this;
    }

    /**
     * Destroy session, effectively logging out the user
     *
     * @return Void
     */
    public function destroy() {
        $this->_store->destroy();
    }

    /**
     * Retrieve auth-related config values from application.ini
     *
     * @param null $subSection
     * @return Array
     */
    public function getConfigValues($subSection = null) {
        $config = Zend_Registry::get('config');
        // set defaults
        $values = $this->_defaultConfigValues;
        if ($config->auth) {
            $values = array_merge($values, $config->auth->toArray());
        }
        if ($subSection) {
            return array_get($values, $subSection);
        }
        return $values;
    }

    /**
     * Check if the current user (ARO) has access to a certain controller action or Model CRUD
     * method (ACO).
     * Note that this requires 'Zend_Acl' to be available from Zend_Registry.
     *
     * @param String $resource A resource
     * @param String $privilege A specific privilege within a resource
     * @return Boolean
     */
    public function isAllowed($resource, $privilege = null) {
        $role = $this->getCurrentRole();
        if (Zend_Registry::isRegistered('Zend_Acl')) {
            $acl = Zend_Registry::get('Zend_Acl');
            return $acl->has($resource) ? $acl->isAllowed($role, $resource, $privilege) : false;
        }
        /**
         * Return TRUE when ACL is not in use, to allow for small, quick projects that don't need
         * a configured ACL.
         */
        return true;
    }

    /**
     * Get the role associated with the current session.
     * Note that an anonymous session, where nobody is logged in also has a role associated with it.
     *
     * @return String The role
     */
    public function getCurrentRole() {
        $role = self::DEFAULT_VISITOR_ROLE;
        if ($this->isLoggedIn()) {
            $role = self::DEFAULT_USER_ROLE;
            $data = $this->getUserData();
            if (isset($data[self::ROLE_COLUMN])) {
                $role = $data[self::ROLE_COLUMN];
            }
        }
        return $role;
    }

    /**
     * Return all available roles from the ACL tree.
     *
     * @param Boolean $verbose Wether to include a role's parents
     * @return Array A numeric array consisting of role strings
     */
    public function getRoles($verbose = false) {
        if (Zend_Registry::isRegistered('Zend_Acl')) {
            $acl = Zend_Registry::get('Zend_Acl');
            $roles = $acl->getRoles();

            // collect parents
            if ($verbose) {
                $roles = array_fill_keys($roles, array());
                foreach (array_keys($roles) as $role) {
                    $roles[$role]['parents'] = $this->getRoleParents($role);
                    $roles[$role]['children'] = $this->getRoleChildren($role);
                }
            }
            return $roles;
        }
        return array();
    }

    /**
     * Return the parents of a given role
     *
     * @param String $role
     * @param Boolean $onlyParents Wether to only return direct parents
     * @return Array
     */
    public function getRoleParents($role, $onlyParents = true) {
        $parents = array();
        if (Zend_Registry::isRegistered('Zend_Acl')) {
            $acl = Zend_Registry::get('Zend_Acl');
            $roles = $acl->getRoles();

            foreach ($roles as $potentialParent) {
                if ($acl->inheritsRole($role, $potentialParent, $onlyParents)) {
                    $parents[] = $potentialParent;
                }
            }
        }
        return $parents;
    }

    /**
     * Return the children of a given role
     *
     * @param String $role
     * @return Array
     */
    public function getRoleChildren($role) {
        $children = array();
        if (Zend_Registry::isRegistered('Zend_Acl')) {
            $acl = Zend_Registry::get('Zend_Acl');
            $roles = $acl->getRoles();

            foreach ($roles as $potentialChild) {
                if ($acl->inheritsRole($potentialChild, $role)) {
                    $children[] = $potentialChild;
                }
            }
        }
        return $children;
    }

    /**
     * Return which columns should be stored in the user session
     *
     * @return array
     */
    public function getSessionColumns() {
        $ini = Zend_Registry::get('config');
        $sessionColumns = Zend_Db_Select::SQL_WILDCARD;
        if (!empty($ini->auth->login->sessionColumns)) {
            $sessionColumns = $ini->auth->login->sessionColumns;
            $sessionColumns = explode(',', $sessionColumns);
        }
        return $sessionColumns;
    }

    public function generateActivationCodeExpiry() {
        $authVars = $this->getConfigValues();
        return date('Y-m-d', strtotime($authVars['forgotpassword']['activation_code_expires_in']));
    }

    public function generateActivationCodeForUser($token, $email, $userId) {
        $authVars = $this->getConfigValues();
        $activationCode = '';
        $activationCode .= $token;
        $activationCode .= md5($email);
        $activationCode .= md5($authVars['salt']);
        $activationCode .= md5($userId);
        $activationCode = md5($activationCode);
        return $activationCode;
    }

    /**
     * Figure out wether the forgot password email message is HTML or plain
     *
     * @return string
     */
    public function getForgotPasswordMessageFormat() {
        $authVars = $this->getConfigValues('forgotpassword');
        return !empty($authVars['email_partial']) ? 'html' :
            (!empty($authVars['email_snippet_column']) ?
                $authVars['email_snippet_column'] : 'text');

    }

    public function getForgotPasswordEmailMessage($user, $activationUrl) {
        $authVars = $this->getConfigValues('forgotpassword');
        if (!empty($authVars['email_partial'])) {
            return $this->_renderForgotPasswordPartial(
                $authVars['email_partial'], $user, $activationUrl
            );
        }
        return $this->_getForgotPasswordSnippet($user, $activationUrl);
    }

    protected function _renderForgotPasswordPartial($partial, $user, $activationUrl) {
        $viewObj = Zend_Controller_Front::getInstance()->getParam('bootstrap')
            ->getResource('view');
        $viewObj->assign(array(
            'user' => $user,
            'activationUrl' => $activationUrl
        ));
        return $viewObj->render($partial);
    }

    protected function _getForgotPasswordSnippet($user, $activationUrl) {
        $authVars = $this->getConfigValues('forgotpassword');

        $snippet_column = !empty($authVars['email_snippet_column']) ?
            $authVars['email_snippet_column'] : 'text';
        $snippet_identifier = !empty($authVars['email_snippet_identifier']) ?
            $authVars['email_snippet_identifier'] : 'forgot password email';
        $snippetModel = $this->_getSnippetModel();
        $emailSnippet = $snippetModel->fetchByIdentifier($snippet_identifier);
        $emailMessage = $emailSnippet->{$snippet_column};
        return Garp_Util_String::interpolate($emailMessage, array(
            'USERNAME' => (string)new Garp_Util_FullName($user),
            'ACTIVATION_URL' => (string)new Garp_Util_FullUrl($activationUrl)
        ));
    }

    /**
     * Retrieve snippet model for system messages.
     *
     * @return object
     */
    protected function _getSnippetModel() {
        $snippetModel = new Model_Snippet();
        if ($snippetModel->getObserver('Translatable')) {
            $i18nModelFactory = new Garp_I18n_ModelFactory();
            $snippetModel = $i18nModelFactory->getModel($snippetModel);
        }
        return $snippetModel;
    }
}
