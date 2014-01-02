<?php
/**
 * Garp_Store_Session
 * Store data in session.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Store
 * @lastmodified $Date: $
 *
 */
class Garp_Store_Session {
	/**
 	 * @var Zend_Session_Namespace
 	 */
	protected $_session;


	/**
 	 * Class constructor
 	 * @param String $namespace 
 	 * @return Void
 	 */
	public function __construct($namespace) {
		$this->_session = new Zend_Session_Namespace($namespace);
	}


	/**
 	 * Get value by key $key
 	 * @param String $key
 	 * @return Mixed
 	 */
	public function get($key) {
		if (isset($this->_session->{$key})) {
			return $this->_session->{$key};
		}
		return null;
	}


	/**
 	 * Store $value by key $key
 	 * @param String $key
 	 * @param Mixed $value
 	 * @return $this
 	 */
	public function set($key, $value) {
		$this->_session->{$key} = $value;
		return $this;
	}


	/**
 	 * Magic getter
 	 * @param String $key
 	 * @return Mixed
 	 */
	public function __get($key) {
		return $this->get($key);
	}


	/**
 	 * Magic setter
 	 * @param String $key
 	 * @param Mixed $value
 	 * @return Void
 	 */
	public function __set($key, $value) {
		$this->set($key, $value);
	}


	/**
 	 * Magic isset
 	 * @param String $key
 	 * @return Boolean
 	 */
	public function __isset($key) {
		return isset($this->_session->{$key});
	}


	/**
 	 * Magic unset
 	 * @param String $key
 	 * @return Void
 	 */
	public function __unset($key) {
		unset($this->_session->{$key});
	}


	/**
 	 * Remove a certain key from the store
 	 * @param String $key
 	 * @return $this
 	 */
	public function destroy($key = false) {
		if ($key) {
			unset($this->_session->{$key});
		} else {
			$this->_session->unsetAll();
		}
		return $this;
	}
}
