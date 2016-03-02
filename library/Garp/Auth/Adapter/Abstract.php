<?php
/**
 * Garp_Auth_Adapter_Abstract
 * Blueprint for Auth adapters. Subclasses may implement
 * an authentication method of their choice.
 *
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Auth
 * @lastmodified $Date: $
 */
abstract class Garp_Auth_Adapter_Abstract {
	/**
	 * Config key
	 * @var String
	 */
	protected $_configKey = '';

	/**
	 * Collection of errors
	 * @var Array
	 */
	protected $_errors = array();

	/**
 	 * Redirect elsewhere after authenticate()
 	 * @var String
 	 */
	protected $_redirect = null;

	/**
	 * Authenticate a user.
	 * @param Zend_Controller_Request_Abstract $request The current request
	 * @param Zend_Controller_Response_Abstract $response The current response
	 * @return Array|Boolean User data, or FALSE
	 */
	abstract public function authenticate(Zend_Controller_Request_Abstract $request,
		Zend_Controller_Response_Abstract $response);

	/**
	 * Fetch user data. We never store all the user data in the session, just
	 * to be safe, and also to ensure that user data is not stale (because the
	 * session won't be magically updated when the user record changes).
	 * An adapter decides what user data to return in self::authenticate() and
	 * using that data it needs to be able to find the actual user data here.
	 * @param Mixed $sessionData
	 * @return Array User data
	 */
	public function getUserData($sessionData) {
		$userModel = new Model_User();
		$userData  = call_user_func_array(array($userModel, 'find'), (array)$sessionData);
		return $userData->current();
	}

	/**
 	 * Include a directive for AuthController saying it should redirect elsewhere.
 	 * This is useful for various auth methods that require authorization on an external domain.
 	 * @param String $url
 	 */
	public function setRedirect($url) {
		$this->_redirect = $url;
	}

	public function getRedirect() {
		return $this->_redirect;
	}

	/**
	 * Get auth values related to this adapter
	 * @return Zend_Config
	 */
	protected function _getAuthVars() {
		if (!$this->_configKey) {
			throw new Garp_Auth_Exception('No config key found in '.__CLASS__.'::_configKey.');
		}
		$config = Zend_Registry::get('config');
		if ($config->auth && $config->auth->adapters && $config->auth->adapters->{$this->_configKey}) {
			return $config->auth->adapters->{$this->_configKey};
		}
		return null;
	}

	/**
	 * Map properties coming from the 3rd party to columns used in our database
	 * @param Array $props
	 * @return Array
	 */
	protected function _mapProperties(array $props) {
		$authVars = $this->_getAuthVars();
		if ($authVars->mapperClass) {
			$mapper = new $authVars->mapperClass();
			if (!$mapper instanceof Garp_Auth_Adapter_Mapper_Abstract) {
				throw new Garp_Auth_Adapter_Exception('Invalid property mapper specified: ' .
					$authVars->mapperClass);
			}
			return $mapper->map($props);
		}
		if ($authVars->mapping && !empty($authVars->mapping)) {
			$cols = array();
			foreach ($authVars->mapping as $mappedProp => $col) {
				if ($col) {
					$cols[$col] = !empty($props[$mappedProp]) ? $props[$mappedProp] : null;
				}
			}
			return $cols;
		} else {
			throw new Garp_Auth_Exception('This authentication method requires '.
								' a mapping of columns in application.ini.');
		}
	}

	/**
 	 * Return array of columns that are stored in the session cookie
 	 * @return Array|String (can also be Zend_Db_Select::SQL_WILDCARD)
 	 */
	protected function _getSessionColumns() {
		return Garp_Auth::getInstance()->getSessionColumns();
	}

	/**
	 * Return all errors
	 * @return Array
	 */
	public function getErrors() {
		return $this->_errors;
	}

	/**
	 * Return most recent error
	 * @return String
	 */
	public function getError() {
		return end($this->_errors);
	}

	/**
	 * Add an error to the stack.
	 * @param String $error
	 * @return $this
	 */
	protected function _addError($error) {
		$this->_errors[] = $error;
		return $this;
	}

	/**
	 * Clear all errors
	 * @return $this
	 */
	protected function _clearErrors() {
		$this->_errors = array();
		return $this;
	}
}
