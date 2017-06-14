<?php
/**
 * Garp_Store_Session
 * Store data in session.
 *
 * @package Garp_Store
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Store_Session implements Garp_Store_Interface {
    /**
     * @var Zend_Session_Namespace
     */
    protected $_session;

    /**
     * Class constructor
     *
     * @param string $namespace
     * @return void
     */
    public function __construct($namespace) {
        $this->_session = new Zend_Session_Namespace($namespace);
    }

    /**
     * Get value by key $key
     *
     * @param string $key
     * @return mixed
     */
    public function get($key) {
        if (isset($this->_session->{$key})) {
            return $this->_session->{$key};
        }
        return null;
    }

    /**
     * Store $value by key $key
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function set($key, $value) {
        $this->_session->{$key} = $value;
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
        return isset($this->_session->{$key});
    }

    /**
     * Magic unset
     *
     * @param string $key
     * @return void
     */
    public function __unset($key) {
        unset($this->_session->{$key});
    }

    /**
     * Remove a certain key from the store
     *
     * @param string $key
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

    public function toArray() {
        return (array)$this->_session;
    }
}
