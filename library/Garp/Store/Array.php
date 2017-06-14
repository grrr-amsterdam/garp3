<?php
/**
 * Garp_Store_Array
 * Store elements in an array.
 * Note: this is not a persistent store. Use it only for Unit Tests.
 *
 * @package Garp_Store
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Store_Array implements Garp_Store_Interface {
    /**
     * @var array
     */
    protected $_data = array();

    /**
     * Namespace used to store data.
     *
     * @var string
     */
    protected $_namespace;

    /**
     * Class constructor
     *
     * @param string $namespace Global namespace
     * @return void
     */
    public function __construct($namespace) {
        $this->_namespace = $namespace;
        $this->_data[$this->_namespace] = array();
    }

    /**
     * Get value by key $key
     *
     * @param string $key
     * @return mixed
     */
    public function get($key) {
        return array_key_exists($key, $this->_data[$this->_namespace]) ?
            $this->_data[$this->_namespace][$key] :
            null
        ;
    }

    /**
     * Store $value by key $key
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function set($key, $value) {
        $this->_data[$this->_namespace][$key] = $value;
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
        return $this->set($key, $value);
    }

    /**
     * Magic isset
     *
     * @param string $key
     * @return bool
     */
    public function __isset($key) {
        return isset($this->_data[$this->_namespace][$key]);
    }

    /**
     * Magic unset
     *
     * @param string $key
     * @return void
     */
    public function __unset($key) {
        $this->destroy($key);
    }

    /**
     * Remove a certain key from the store
     *
     * @param string $key
     * @return $this
     */
    public function destroy($key = false) {
        if ($key) {
            unset($this->_data[$this->_namespace][$key]);
        } else {
            $this->_data[$this->_namespace] = array();
        }
        return $this;
    }

    public function toArray() {
        return $this->_data[$this->_namespace];
    }
}
