<?php
/**
 * Garp_Cli_Command_Cache
 * 
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class Garp_Cli_Command_Cache extends Garp_Cli_Command {
	/**
 	 * Clear all the cache
 	 * @param Array $args Tags.
 	 * @return Boolean
 	 */
	public function clear(array $args = array()) {
		$app = Zend_Registry::get('application');
		$bootstrap = $app->getBootstrap();
		$cacheDir = false;
		if ($bootstrap && $bootstrap->getResource('cachemanager')) {
			$cacheManager = $bootstrap->getResource('cachemanager');
			$cache = $cacheManager->getCache(Zend_Cache_Manager::PAGECACHE);
			$cacheDir = $cache->getBackend()->getOption('public_dir');
		}

		Garp_Cache_Manager::purgeStaticCache($args, $cacheDir);
		Garp_Cache_Manager::purgeMemcachedCache($args);
		Garp_Cache_Manager::purgePluginLoaderCache();
		Garp_Cli::lineOut('All cache purged.');
		return true;
	}

	public function info() {
		Garp_Cache_Manager::info();
	}


	/**
 	 * Help
 	 */
	public function help() {
		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut('Clear all cache:');
		Garp_Cli::lineOut('  g Cache clear', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('Clear cache of model Foo and model Bar:');
		Garp_Cli::lineOut('  g Cache clear Model_Foo Model_Bar', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
		return true;
	}
}
