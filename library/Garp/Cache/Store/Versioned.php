<?php
/**
 * Garp_Cache_Store_Versioned
 * Caching wrapper, creating a versioned caching approach.
 * Useful mostly for Memcache.
 *
 * @package Garp_Cache
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Cache_Store_Versioned {
    /**
     * The key in the cache that stores the current version
     *
     * @var string
     */
    protected $_versionKey;


    /**
     * Class constructor
     *
     * @param string $versionKey The key in the cache that stores the current version
     * @return void
     */
    public function __construct($versionKey) {
        $this->_versionKey = $versionKey;
    }

    /**
     * Write data to cache
     *
     * @param string $key The cache id
     * @param mixed $data Data
     * @return bool
     */
    public function write($key, $data) {
        $cache = Zend_Registry::get('CacheFrontend');
        // include the current version in the cached results
        $version = (int)$cache->load($this->_versionKey);
        // save the data to cache but include the version number
        return $cache->save(
            array(
                'data' => $data,
                'version' => $version
            ),
            $key
        );
    }


    /**
     * Read data from cache
     *
     * @param string $key The cache id
     * @return mixed -1 if no valid cache is found.
     */
    public function read($key) {
        $cache = Zend_Registry::get('CacheFrontend');
        // fetch results from cache
        if (($results = $cache->load($key)) !== false) {
            if (!is_array($results)) {
                return -1;
            }
            $version = (int)$cache->load($this->_versionKey);
            // compare version numbers
            if (array_key_exists('version', $results) && (int)$results['version'] === $version) {
                return $results['data'];
            }
        }
        return -1;
    }

    /**
     * Increment the cached version. This invalidates the current cached results.
     *
     * @return Garp_Cache_Store_Versioned $this
     */
    public function incrementVersion() {
        $cache = Zend_Registry::get('CacheFrontend');
        $version = (int)$cache->load($this->_versionKey);
        $version += 1;
        $cache->save($version, $this->_versionKey);
        return $this;
    }
}
