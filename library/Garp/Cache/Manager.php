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
	 * Purge all cache system wide
	 * @param Array $tags
	 * @param Boolean $createClusterJob Whether this purge should create a job to clear the other nodes in this server cluster, if applicable.
	 * @return Void
	 */
	public static function purge(array $tags = array(), $createClusterJob = true) {
		self::purgeStaticCache($tags);
		self::purgeQueryCache($tags);

		$ini = Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/application.ini');
		if ($createClusterJob && $ini->app->clusteredHosting) {
			Garp_Cache_Store_Cluster::createJob($tags);
		}
	}


	/**
 	 * Clear the general cache that stores queries and ini files and whatnot.
 	 * @param Array $tags Tags are model names
 	 * @return Void
 	 */
	public static function purgeQueryCache(array $tags = array()) {
		if (empty($tags)) {
			if (Zend_Registry::isRegistered('CacheFrontend')) {
				$cacheFront = Zend_Registry::get('CacheFrontend'); 
				$cacheFront->clean(Zend_Cache::CLEANING_MODE_ALL);
			}
		} else {
			foreach ($tags as $modelName) {
				$model = new $modelName();
				$cache = new Garp_Cache_Store_Versioned($model->getName().'_version');
				$cache->incrementVersion();
			}			
		}
	}


	/**
 	 * This clears Zend's Static caching.
 	 * @param Array $tags Clear the cache of a specific bunch of tags.
 	 * @return Void
 	 */
	public static function purgeStaticCache(array $tags = array()) {
		$cacheDir = APPLICATION_PATH.'/../public/cached/';
		$cacheDir = str_replace(' ', '\ ', $cacheDir);

		$deleteFiles = function($path) use ($cacheDir) {
			while (strpos($path, '..') !== false) {
				$path = str_replace('..', '.', $path);
			}
			$path = $cacheDir.$path; 
			@system('rm -rf '.$path.';');
			return true;
		};

		if (empty($tags)) {
			// Destroy all
			$deleteFiles('*');
		} else {
			$ini = Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/cache.ini');
			if (empty($ini->tags)) {
				return;
			}
			foreach ($tags as $tag) {
				if ($ini->tags->{$tag}) {
					foreach ($ini->tags->{$tag} as $file) {
						$deleteFiles($file);
					}
				}
			}
		}
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
}
