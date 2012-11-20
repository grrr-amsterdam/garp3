<?php
/**
 * Garp_Cache_Manager
 * Various managerial cache-related tasks.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cache
 * @lastmodified $Date: $
 */
class Garp_Cache_Manager {
	/**
 	 * Read query cache
 	 * @param Garp_Model_Db $model
 	 * @param String $key
 	 * @return Mixed
 	 */
	public static function readQueryCache(Garp_Model_Db $model, $key) {
		$cache = new Garp_Cache_Store_Versioned($model->getName().'_version');
		return $cache->read($key);
	}


	/**
 	 * Write query cache
 	 * @param Garp_Model_Db $model
 	 * @param String $key
 	 * @param Mixed $results
 	 * @return Void
 	 */
	public static function writeQueryCache(Garp_Model_Db $model, $key, $results) {
		$cache = new Garp_Cache_Store_Versioned($model->getName().'_version');
		$cache->write($key, $results);
	}


	/**
	 * Purge all cache system wide
	 * @param Array|Garp_Model_Db $tags
	 * @param Boolean $createClusterJob Whether this purge should create a job to clear the other nodes in this server cluster, if applicable.
	 * @return Void
	 */
	public static function purge($tags = array(), $createClusterJob = true) {
		if ($tags instanceof Garp_Model_Db) {
			$tags = self::getTagsFromModel($tags);
		}
		self::purgeStaticCache($tags);
		self::purgeMemcachedCache($tags);
		self::purgePluginLoaderCache();

		$ini = Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/application.ini');
		if ($createClusterJob && $ini->app->clusteredHosting) {
			Garp_Cache_Store_Cluster::createJob($tags);
		}
	}


	/**
 	 * Clear the Memcached cache that stores queries and ini files and whatnot.
 	 * @param Array|Garp_Model_Db $modelNames
 	 * @return Void
 	 */
	public static function purgeMemcachedCache($modelNames = array()) {
		if ($modelNames instanceof Garp_Model_Db) {
			$modelNames = self::getTagsFromModel($modelNames);
		}

		if (empty($modelNames)) {
			if (Zend_Registry::isRegistered('CacheFrontend')) {
				$cacheFront = Zend_Registry::get('CacheFrontend'); 
				$cacheFront->clean(Zend_Cache::CLEANING_MODE_ALL);
			}
		} else {
			foreach ($modelNames as $modelName) {
				$model = new $modelName();
				$cache = new Garp_Cache_Store_Versioned($model->getName().'_version');
				$cache->incrementVersion();
			}
		}
	}


	/**
 	 * This clears Zend's Static caching.
 	 * @param Array|Garp_Model_Db $modelNames Clear the cache of a specific bunch of models.
 	 * @param String $cacheDir Directory containing the cache files
 	 * @return Void
 	 */
	public static function purgeStaticCache($modelNames = array(), $cacheDir = false) {
		if ($modelNames instanceof Garp_Model_Db) {
			$modelNames = self::getTagsFromModel($modelNames);
		}
	
		if (!$cacheDir) {
			$front = Zend_Controller_Front::getInstance();
			if (!$front->getParam('bootstrap') || !$front->getParam('bootstrap')->getResource('cachemanager')) {
				return false;
			}
			$cacheManager = $front->getParam('bootstrap')->getResource('cachemanager');
			$cache = $cacheManager->getCache(Zend_Cache_Manager::PAGECACHE);
			$cacheDir = $cache->getBackend()->getOption('public_dir');
		}
		$cacheDir = str_replace(' ', '\ ', $cacheDir);
		$cacheDir = rtrim($cacheDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

		if (empty($modelNames)) {
			// Destroy all
			$allPath = $cacheDir.'*';
			self::_deleteStaticCacheFile($allPath);
		} else {
			$ini = new Zend_Config_Ini(APPLICATION_PATH.'/configs/cache.ini', APPLICATION_ENV);
			if (empty($ini->tags)) {
				return;
			}
			foreach ($modelNames as $tag) {
				if ($ini->tags->{$tag}) {
					foreach ($ini->tags->{$tag} as $path) {
						while (strpos($path, '..') !== false) {
							$path = str_replace('..', '.', $path);
						}
						$filePath = $cacheDir.$path;
						self::_deleteStaticCacheFile($filePath);
					}
				}
			}
		}
	}


	/**
 	 * Remove pluginLoaderCache.php
 	 * @return Void
 	 */
	public static function purgePluginLoaderCache() {
		@unlink(APPLICATION_PATH.'/data/cache/pluginLoaderCache.php');
	}


	/**
 	 * Remove static cache file(s)
 	 * @param String $path
 	 * @return Boolean
 	 */
	protected static function _deleteStaticCacheFile($path) {
		$success = @system('rm -rf '.$path.';') !== false;
		return $success;
	}

	/**
 	 * Schedule a cache clear in the future.
 	 * @see Garp_Model_Behavior_Draftable for a likely pairing
 	 * @param Int $timestamp
 	 * @param Array $tags
 	 * @return Bool A guesstimate of wether the command has successfully been scheduled.
 	 *              Note that it's hard to determine if this is true. I have, for instance,
 	 *              not yet found a way to determine if the atrun daemon actually is active.
 	 */
	public static function scheduleClear($timestamp, array $tags = array()) {
		$time = date('H:i d.m.y', $timestamp);

		// Sanity check: are php and at available? ('which' returns an empty string in case of failure)
		if (exec('which php') && exec('which at')) {
			// The command must come from a file, create that in the data folder of this project.
			// Add timestamp to the filename so we can safely delete the file later
			$tags = implode(' ', $tags);
			$file = APPLICATION_PATH.'/data/at_cmd_'.time().md5($tags);
			$garpScriptFile = realpath(APPLICATION_PATH.'/../garp/scripts/garp.php');
			$cmd  = 'php '.$garpScriptFile.' Cache clear --APPLICATION_ENV='.APPLICATION_ENV.' '.$tags.';';
			if (file_put_contents($file, $cmd)) {
				$atCmd = 'at -f '.$file.' '.$time;
				exec($atCmd);
				// @todo Actually evaluate the status of the command.
				// This returning true is a bit arbitrary.. I have actually not found a way to read
				// the error messages returned from the command line.

				// Clean up the tmp file
				@unlink($file);
				return true;
			} else {
				throw new Garp_Model_Behavior_Exception('Cannot write tmp file for at job');
			}
		} else {
			throw new Garp_Model_Behavior_Exception('php and/or at are not available in this shell.');
		}
		return false;
	}


	/**
 	 * Get cache tags used with a given model.
 	 * @param Garp_Model_Db $model
 	 * @return Array
 	 */
	public static function getTagsFromModel(Garp_Model_Db $model) {
		$tags = array(get_class($model));
		foreach ($model->getBindableModels() as $modelName) {
			if (!in_array($modelName, $tags)) {
				$tags[] = $modelName;
			}
		}
		return $tags;
	}
}
