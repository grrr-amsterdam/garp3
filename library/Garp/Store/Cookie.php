<?php
/**
 * Garp_Store_Cookie
 * Store data in cookies
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.1.0
 * @package      Garp_Store
 */
class Garp_Store_Cookie implements Garp_Store_Interface {
	/**
 	 * Expiration time
 	 * @var Int
 	 * @todo Make this configurable, right now it's set to 30 days
 	 */
	const DEFAULT_COOKIE_DURATION = 2592000; 

	/**
 	 * Cookie save path
 	 * @var String
 	 * @todo Make this configurable
 	 */
	const DEFAULT_COOKIE_PATH = '/';

	/**
 	 * Cookie namespace
 	 * @var String
 	 */
	protected $_namespace = '';

	/**
 	 * Cookie data, associative array or scalar value.
 	 * @var Mixed
 	 */
	protected $_data = array();

	/**
 	 * Cookie duration
 	 * @var Int
 	 */
	protected $_cookieDuration;

	/**
 	 * Cookie path
 	 * @var String
 	 */
	protected $_cookiePath;

	/**
 	 * Cookie domain.
 	 * Note: leave this empty to make the cookie only work on
 	 * the current domain.
 	 * @var String
 	 */
	protected $_cookieDomain = ''; 

	/**
 	 * Record wether changes are made to the cookie
 	 * @var Boolean
 	 */
	protected $_modified = false; 

	/**
 	 * Class constructor
 	 * @param String $namespace 
 	 * @param String $cookieDuration
 	 * @param String $cookiePath
 	 * @param String $cookieDomain
 	 * @return Void
 	 */
	public function __construct($namespace, $cookieDuration = self::DEFAULT_COOKIE_DURATION, $cookiePath = self::DEFAULT_COOKIE_PATH, $cookieDomain = false) {
		$this->_namespace = $namespace;
		$this->_cookieDuration = $cookieDuration;
		$this->_cookiePath = $cookiePath;
		/* $this->_cookieDomain = $cookieDomain ?: (!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : gethostname()); */

		// fill internal array with existing cookie values
		if (array_key_exists($namespace, $_COOKIE)) {
			$this->_data = json_decode($_COOKIE[$namespace], true);
			if ($jsonError = json_last_error()) {
				$this->_data = array();
				$ini = Zend_Registry::get('config');
				if (!empty($ini->logging->enabled) && $ini->logging->enabled) {
					$jsonErrorStr = '';
					switch ($jsonError) {
						case JSON_ERROR_NONE:
							$jsonErrorStr = 'No error has occurred';
						break;
						case JSON_ERROR_DEPTH:
							$jsonErrorStr = 'The maximum stack depth has been exceeded';
						break;
						case JSON_ERROR_STATE_MISMATCH:
							$jsonErrorStr = 'Invalid or malformed JSON';
						break;
						case JSON_ERROR_CTRL_CHAR:
							$jsonErrorStr = 'Control character error, possibly incorrectly encoded';
						break;
						case JSON_ERROR_SYNTAX:
							$jsonErrorStr = 'Syntax error';
						break;
						case JSON_ERROR_UTF8:
							$jsonErrorStr = 'Malformed UTF-8 characters, possibly incorrectly encoded';
						break;
					}

					dump('cookie_faulty_json', $jsonErrorStr.': '.$_COOKIE[$namespace]);
				}
			}
		}
	} 

	/**
 	 * Write internal array to actual cookie.
 	 * @return Void
 	 */
	public function __destruct() {
		if ($this->isModified()) {
			$this->writeCookie();
		}
	} 

	/**
 	 * Check if cookie is modified
 	 * @return Void
 	 */
	public function isModified() {
		return $this->_modified;
	} 

	/**
 	 * Write internal array to actual cookie.
 	 * @return Void
 	 */
	public function writeCookie() {
		/**
 	 	 * When Garp is used from the commandline, writing cookies is impossible.
 	 	 * And that's okay.
 	 	 */
		if (Zend_Registry::isRegistered('CLI') && Zend_Registry::get('CLI')) {
			return true;
		}

		if (headers_sent($file, $line)) {
			throw new Garp_Store_Exception('Error: headers are already sent, cannot set cookie. '."\n".
				'Output already started at: '.$file.'::'.$line);
		}
		$data = is_array($this->_data) ? json_encode($this->_data, JSON_FORCE_OBJECT) : $this->_data;
		setcookie(
			$this->_namespace,
			$data,
			time()+$this->_cookieDuration,
			$this->_cookiePath,
			$this->_cookieDomain
		);
		
		$this->_modified = false;
	} 

	/**
 	 * Get value by key $key
 	 * @param String $key
 	 * @return Mixed
 	 */
	public function get($key) {
		if (is_array($this->_data) && array_key_exists($key, $this->_data)) {
			return $this->_data[$key];
		}
		return null;
	} 

	/**
 	 * Store $value by key $key
 	 * @param String $key Key name of the cookie var, or leave null to make this a cookie with a scalar value.
 	 * @param Mixed $value
 	 * @return $this
 	 */
	public function set($key = null, $value) {
		if ($key) {
			$this->_data[$key] = $value;
		} else {
			$this->_data = $value;
		}
		$this->_modified = true;
		return $this;
	}

	/**
 	 * Store a bunch of values all at once
 	 * @param Array $values
 	 * @return $this
 	 */
	public function setFromArray(array $values) {
		foreach ($values as $key => $val) {
			$this->set($key, $val);
		}
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
		return isset($this->_data[$key]);
	}

	/**
 	 * Magic unset
 	 * @param String $key
 	 * @return Void
 	 */
	public function __unset($key) {
		if (isset($this->_data[$key])) {
			$this->_modified = true;
			unset($this->_data[$key]);
		}
	}

	/**
 	 * Remove a certain key from the store
 	 * @param String $key Leave out to clear the entire namespace.
 	 * @return $this
 	 */
	public function destroy($key = false) {
		if (!$key) {
			$this->_data = array();
			// unset the whole cookie by using a date in the past
			setcookie(
				$this->_namespace,
				false,
				time()-$this->_cookieDuration,
				$this->_cookiePath,
				$this->_cookieDomain
			);
			$this->_modified = false;
		} elseif (isset($this->_data[$key])) {
			$this->__unset($key);
			$this->_modified = true;
		}
	}
}
