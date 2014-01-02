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
		if ($model->cacheQueries && Zend_Registry::get('readFromCache')) {
			$cache = new Garp_Cache_Store_Versioned($model->getName().'_version');
			$id = $this->_createUniqueId($model, $select);
			$results = $cache->read($id);

			if ($results !== -1) {
				// populate the third parameter with the cached version
				$args[2] = $results;
			} else {
				$this->_openCacheKey = $id;
			}
		}
	}
	
	
	/**
	 * After fetch callback, writes data back to the cache.
	 * @param Array $args
	 * @return Void
	 */
	public function afterFetch(&$args) {
		if ($this->_openCacheKey) {
			$model = $args[0];
			$results = $args[1];
					
			$cache = new Garp_Cache_Store_Versioned($model->getName().'_version');
			$cache->write($this->_openCacheKey, $results);

			// reset the key
			$this->_openCacheKey = '';
		}
	}
	
	
	/**
	 * After insert callback, will destroy the existing cache for this model
	 * @param Array $args
	 * @return Void
	 */
	public function afterInsert(&$args) {
		$model = &$args[0];
		$this->_clearCache($model);
	}
	
	
	/**
	 * After update callback, will destroy the existing cache for this model
	 * @param Array $args
	 * @return Void
	 */
	public function afterUpdate(&$args) {
		$model = &$args[0];
		$this->_clearCache($model);
	}
	
	
	/**
	 * After delete callback, will destroy the existing cache for this model
	 * @param Array $args
	 * @return Void
	 */
	public function afterDelete(&$args) {
		$model = &$args[0];
		$this->_clearCache($model);
	}
	
	
	/**
	 * Create a unique hash for cache entries, based on the SELECT object,
	 * but also on the registered bindings, because a query might be the same
	 * with different results when bindings come in to play.
	 * @param Garp_Model $model
	 * @param Zend_Db_Select $select
	 * @return String
	 */
	protected function _createUniqueId(Garp_Model $model, Zend_Db_Select $select) {
		$boundModels = serialize(Garp_Model_Db_BindingManager::getBindingTree(get_class($model)));
		$hash = md5(
			md5($select).
			md5($boundModels)
		);
		return $hash;
	}
	
	
	/**
	 * Clear all cache for a given model
	 * @param Garp_Model $model
	 * @return Void
	 */
	protected function _clearCache(Garp_Model $model) {
		$models = array(get_class($model));
		foreach ($model->getBindableModels() as $modelName) {
			if (!in_array($modelName, $models)) {
				$models[] = $modelName;
			}
		}
		/**
 		 * Purge query cache using all bindable models.
 		 * This is done because the subject model may be fetched thru another model, 
 		 * and thus end up in the cache of the other model.
 		 */
		Garp_Cache_Manager::purgeQueryCache($models);

		/**
 		 * Static cache is much more controlled area, because we can know beforehand
 		 * exactly which URLs will contain which models. Therefore, it's the 
 		 * developer's responsibility to add those URLs to cache.ini to make sure
 		 * the correct cache gets cleared. 
 		 */
		Garp_Cache_Manager::purgeStaticCache(array(get_class($model)));
	}
}
