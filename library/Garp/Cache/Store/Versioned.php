<?php
/**
 * Garp_Cache_Store_Versioned
 * Caching wrapper, creating a versioned caching approach.
 * Useful mostly for Memcache.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cache
 * @lastmodified $Date: $
 */
class Garp_Cache_Store_Versioned {
	/**
	 * The key in the cache that stores the current version
	 * @var String
	 */
	protected $_versionKey;
	
	
	/**
	 * Class constructor
	 * @param String $versionKey The key in the cache that stores the current version
	 * @return Void
	 */
	public function __construct($versionKey) {
		$this->_versionKey = $versionKey;
	}
	
	
	/**
	 * Write data to cache
	 * @param String $key The cache id
	 * @param Mixed $data Data
	 * @return Boolean
	 */
	public function write($key, $data) {
		$cache = Zend_Registry::get('CacheFrontend');
		// include the current version in the cached results
		$version = (int)$cache->load($this->_versionKey);
		// save the data to cache but include the version number
		return $cache->save(array(
			'data' => $data,
			'version' => $version
		), $key);
	}
	
	
	/**
	 * Read data from cache
	 * @param String $key The cache id
	 * @return Mixed -1 if no valid cache is found.
	 */
	public function read($key) {
		$cache = Zend_Registry::get('CacheFrontend');
		// fetch results from cache
		if ($cache->test($key)) {
			$results = $cache->load($key);
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
