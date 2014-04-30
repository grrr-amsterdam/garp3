<?php
/**
 * Garp_Model_Behavior_Cachable
 * Caches fetch() calls.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class Garp_Model_Behavior_Cachable extends Garp_Model_Behavior_Core {
	/**
	 * Wether to execute before or after regular observers.
	 * @var String
	 */
	protected $_executionPosition = self::EXECUTE_LAST;

	/**
	 * Key used to write data to cache. This is populated by beforeFetch
	 * if no valid data is found in the cache. The id is then written
	 * to this property and afterFetch notices this. If it finds an 
	 * open cache key here, it will use it to write the fresh data to the 
	 * cache and reset the key.
	 * @var String
	 */
	protected $_openCacheKey;

	/**
	 * Configuration.
	 * @return Void
	 */
	protected function _setup($config) {}

	/**
	 * Before fetch callback, checks the cache for valid data.
	 * @param Array $args
	 * @return Void
	 */
	public function beforeFetch(&$args) {
		$model = &$args[0];
		$select = &$args[1];
		// check if the cache is in use
		if (!$model->getCacheQueries() || !Zend_Registry::get('readFromCache')) {
			return;
		}
		$cacheKey = $this->createCacheKey($model, $select);
		$results = Garp_Cache_Manager::readQueryCache($model, $cacheKey);
		if ($results !== -1) {
			$args[2] = $results;
		} else {
			$this->_openCacheKey = $cacheKey;
		}
	} 

	/**
	 * After fetch callback, writes data back to the cache.
	 * @param Array $args
	 * @return Void
	 */
	public function afterFetch(&$args) {
		if (!$this->_openCacheKey) {
			return;
		}
		$model = $args[0];
		$results = $args[1];
		if ($model->getCacheQueries() && Zend_Registry::get('readFromCache')) {
			Garp_Cache_Manager::writeQueryCache($model, $this->_openCacheKey, $results);
		}

		// reset the key
		$this->_openCacheKey = '';
	}

	/**
	 * After insert callback, will destroy the existing cache for this model
	 * @param Array $args
	 * @return Void
	 */
	public function afterInsert(&$args) {
		$model = &$args[0];
		if ($model->getCacheQueries() && Zend_Registry::get('readFromCache')) {
			Garp_Cache_Manager::purge($model);
		}
	}

	/**
	 * After update callback, will destroy the existing cache for this model
	 * @param Array $args
	 * @return Void
	 */
	public function afterUpdate(&$args) {
		$model = &$args[0];
		if ($model->getCacheQueries() && Zend_Registry::get('readFromCache')) {
			Garp_Cache_Manager::purge($model);
		}
	}

	/**
	 * After delete callback, will destroy the existing cache for this model
	 * @param Array $args
	 * @return Void
	 */
	public function afterDelete(&$args) {
		$model = &$args[0];
		if ($model->getCacheQueries() && Zend_Registry::get('readFromCache')) {
			Garp_Cache_Manager::purge($model);
		}
	}

	/**
	 * Create a unique hash for cache entries, based on the SELECT object,
	 * but also on the registered bindings, because a query might be the same
	 * with different results when bindings come into play.
	 * @param Garp_Model $model
	 * @param Zend_Db_Select $select
	 * @return String
	 */
	public function createCacheKey(Garp_Model $model, Zend_Db_Select $select) {
		$boundModels = serialize(Garp_Model_Db_BindingManager::getBindingTree(get_class($model)));
		$hash = md5(
			md5($select).
			md5($boundModels)
		);
		return $hash;
	}
}
