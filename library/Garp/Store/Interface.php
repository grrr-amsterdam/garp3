<?php
/**
 * Garp_Store_Interface
 * Blueprint for stores.
 *
 * @package Garp_Store
 * @author  Harmen Janssen <harmen@grrr.nl>
 *
 * @todo It would be a good thing if Iterable and similar interfaces are added to these classes,
 * so the stores can be accessed like an array with foreach loops and all that jazz.
 */
interface Garp_Store_Interface {
    /**
     * Class constructor
     *
     * @param string $namespace Global namespace
     * @return void
     */
    public function __construct($namespace);

    /**
     * Get value by key $key
     *
     * @param string $key
     * @return mixed
     */
    public function get($key);

    /**
     * Store $value by key $key
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function set($key, $value);

    /**
     * Magic getter
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key);

    /**
     * Magic setter
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set($key, $value);

    /**
     * Magic isset
     *
     * @param string $key
     * @return bool
     */
    public function __isset($key);

    /**
     * Magic unset
     *
     * @param string $key
     * @return void
     */
    public function __unset($key);

    /**
     * Remove a certain key from the store
     *
     * @param string $key
     * @return $this
     */
    public function destroy($key = false);

    /**
     * To array converter
     *
     * @return array
     */
    public function toArray();
}
