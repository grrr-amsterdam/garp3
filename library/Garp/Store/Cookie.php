<?php
/**
 * Garp_Store_Cookie
 * Store data in cookies
 *
 * @package Garp_Store
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Store_Cookie implements Garp_Store_Interface {
    /**
     * Expiration time
     *
     * @var Int
     * @todo Make this configurable, right now it's set to 30 days
     */
    const DEFAULT_COOKIE_DURATION = 2592000;

    /**
     * Cookie save path
     *
     * @var string
     * @todo Make this configurable
     */
    const DEFAULT_COOKIE_PATH = '/';

    /**
     * Cookie namespace
     *
     * @var string
     */
    protected $_namespace = '';

    /**
     * Cookie data, associative array or scalar value.
     *
     * @var mixed
     */
    protected $_data = array();

    /**
     * Cookie duration
     *
     * @var Int
     */
    protected $_cookieDuration;

    /**
     * Cookie path
     *
     * @var string
     */
    protected $_cookiePath;

    /**
     * Cookie domain.
     * Note: leave this empty to make the cookie only work on
     * the current domain.
     *
     * @var string
     */
    protected $_cookieDomain = '';

    /**
     * Record wether changes are made to the cookie
     *
     * @var bool
     */
    protected $_modified = false;

    /**
     * Check if a cookie is available
     *
     * @param string $namespace
     * @return bool
     */
    public static function exists($namespace) {
        return isset($_COOKIE[$namespace]);
    }

    /**
     * Class constructor
     *
     * @param string $namespace
     * @param string $cookieDuration
     * @param string $cookiePath
     * @param string $cookieDomain
     * @return void
     */
    public function __construct($namespace, $cookieDuration = false,
        $cookiePath = self::DEFAULT_COOKIE_PATH, $cookieDomain = false
    ) {
        $this->_namespace = $namespace;
        $this->_cookieDuration = $cookieDuration ?: self::DEFAULT_COOKIE_DURATION;
        $this->_cookiePath = $cookiePath;
        $this->_cookieDomain = $cookieDomain ?: $this->_getCookieDomain();

        // fill internal array with existing cookie values
        if (array_key_exists($namespace, $_COOKIE)) {
            $this->_readInitialData($namespace);
        }
    }

    /**
     * Write internal array to actual cookie.
     *
     * @return void
     */
    public function __destruct() {
        if ($this->isModified()) {
            $this->writeCookie();
        }
    }

    /**
     * Check if cookie is modified
     *
     * @return void
     */
    public function isModified() {
        return $this->_modified;
    }

    /**
     * Write internal array to actual cookie.
     *
     * @return void
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
            throw new Garp_Store_Exception(
                'Error: headers are already sent, cannot set cookie. ' . "\n" .
                'Output already started at: ' . $file . '::' . $line
            );
        }
        $data = is_array($this->_data) ?
            json_encode($this->_data, JSON_FORCE_OBJECT) :
            $this->_data;
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
     *
     * @param string $key
     * @return mixed
     */
    public function get($key) {
        if (is_array($this->_data) && array_key_exists($key, $this->_data)) {
            return $this->_data[$key];
        }
        return null;
    }

    /**
     * Store $value by key $key
     *
     * @param string $key  Key name of the cookie var, or leave null to
     *                     make this a cookie with a scalar value.
     * @param mixed $value
     * @return $this
     */
    public function set($key, $value) {
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
     *
     * @param array $values
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
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key) {
        return $this->get($key);
    }

    /**
     * Magic setter
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set($key, $value) {
        $this->set($key, $value);
    }

    /**
     * Magic isset
     *
     * @param string $key
     * @return bool
     */
    public function __isset($key) {
        return isset($this->_data[$key]);
    }

    /**
     * Magic unset
     *
     * @param string $key
     * @return void
     */
    public function __unset($key) {
        if (isset($this->_data[$key])) {
            $this->_modified = true;
            unset($this->_data[$key]);
        }
    }

    /**
     * Remove a certain key from the store
     *
     * @param string $key Leave out to clear the entire namespace.
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

    /**
     * To array converter
     *
     * @return array
     */
    public function toArray() {
        return $this->_data;
    }

    /**
     * Read cookie domain from config.
     * If no cookie domain is present, return an empty string. The empty string will restrict the
     * cookie to the current domain.
     *
     * @return string
     */
    protected function _getCookieDomain() {
        $config = Zend_Registry::get('config');
        return $config->app->cookiedomain ?: '';
    }

    protected function _readInitialData($namespace) {
        $this->_data = json_decode($_COOKIE[$namespace], true);
        if (!$jsonError = json_last_error()) {
            return;
        }
        $this->_data = array();
        $config = Zend_Registry::get('config');
        if (!empty($config->logging->enabled) && $config->logging->enabled) {
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

            $logger = Garp_Log::factory('cookie_faulty_json.json');
            $logger->log($jsonErrorStr . ': ' . $_COOKIE[$namespace], Garp_Log::INFO);
        }
    }

}
