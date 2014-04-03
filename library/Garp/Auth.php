<?php
/**
 * Garp_Auth
 * Handles all kinds of authentication related stuff.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Auth
 * @lastmodified $Date: $
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
	 * @var Garp_Auth
	 */
	private static $_instance = null;
	

	/**
 	 * Storage
 	 * @var Garp_Auth_Store
 	 */
	protected $_store;

	
	/**
	 * Some config defaults
	 * @var Array
	 */
	protected $_defaultConfigValues = array(
		'loginModule'			=> 'default',
		'loginView'				=> 'login',
		'layoutView'			=> 'layout',
		'loginSuccessUrl'		=> '/',
		'loginSuccessMessage'	=> 'You are successfully logged in',
		'logoutSuccessMessage'	=> 'You are now logged out',
		'salt'                  => 'you should really fill this in application.ini'
	);
	
	
	/**
	 * Private constructor. Here be Singletons.
	 * @return Void
	 */
	private function __construct() {
		$this->_store = Garp_Store_Factory::getStore('Garp_Auth');
	}
	
	
	/**
	 * Get Garp_Auth instance
	 * @return Garp_Auth
	 */
	public static function getInstance() {
		if (!Garp_Auth::$_instance) {
			Garp_Auth::$_instance = new Garp_Auth();
		}
		return Garp_Auth::$_instance;
	}


	/**
 	 * Return the currently used storage object
 	 * @return Garp_Auth_Store
 	 */
	public function getStore() {
		return $this->_store;
	}
	
	
	/**
	 * Check if a user is logged in
	 * @return Boolean
	 */
	public function isLoggedIn() {
		$hasUserData = isset($this->_store->userData);
		$hasLoginMethod = isset($this->_store->method);
		$hasValidToken = isset($this->_store->token) && $this->validateToken($this->_store->token);
		return $hasUserData && $hasLoginMethod && $hasValidToken;
	}
	
	
	/**
	 * Get data from logged in user
	 * @return Array
	 */
	public function getUserData() {
		return $this->_store->userData;
	}
	
	
	/**
	 * Create a unique token for the currently logged in user.
	 * @param String $input Serialized user data
	 * @return String
	 */
	public function createToken($input) {
		$config = $this->getConfigValues();
		$salt   = $config['salt'];

		$token  = '';
		$token .= !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$token .= md5($input);
		$token .= md5($salt);
		$token  = md5($token);

		return $token;
	}
	
	
	/**
	 * Validate the current token
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
	 * @param Mixed $data The user data
	 * @param String $method The method used to login
	 * @return Void
	 */
	public function store($data, $method) {
		$token = $this->createToken(serialize($data));
		$this->_store->userData = $data;
		$this->_store->method = $method;
 	 	$this->_store->token = $token;
	}
	
	
	/**
	 * Destroy session, effectively logging out the user
	 * @return Void
	 */
	public function destroy() {
		$this->_store->destroy();
	}
	
	
	/**
	 * Retrieve auth-related config values from application.ini
	 * @return Array
	 */
	public function getConfigValues() {
		$config	= Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/application.ini', APPLICATION_ENV);
		// set defaults
		$values = $this->_defaultConfigValues;
		if ($config->auth) {
			$values = array_merge($values, $config->auth->toArray());
		}
		return $values;
	}


	/**
 	 * Check if the current user (ARO) has access to a certain controller action or Model CRUD method (ACO).
 	 * Note that this requires 'Zend_Acl' to be available from Zend_Registry.
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
 		 * Return TRUE when ACL is not in use, to allow for small, quick projects that don't need a configured ACL.
 		 */
		return true;
	}


	/**
 	 * Get the role associated with the current session.
 	 * Note that an anonymous session, where nobody is logged in also has a role associated with it.
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
	 * @return Array A numeric array consisting of role strings
	 */
	public function getRoles() {
		$aclConfig	= Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/acl.ini', APPLICATION_ENV);
		$aclArray = $aclConfig->acl->toArray();
		
		if (array_key_exists('roles', $aclArray)) {
			return array_keys($aclArray['roles']);
		} else {
			throw new Exception("No roles could be found in the ACL configuration.");
		}
	}
	
}
