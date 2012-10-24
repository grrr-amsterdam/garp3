<?php
/**
 * Garp_Application
 * Provides the extra functionality of being able to cache config files.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @lastmodified $Date: $
 */
class Garp_Application extends Zend_Application {
	/**
	 * Load configuration file of options.
	 *
	 * Optionally will cache the configuration.
	 *
	 * @param  string $file
	 * @throws Zend_Application_Exception When invalid configuration file is provided
	 * @return array
	 */
	protected function _loadConfig($file) {
		$config = Garp_Cache_Ini::factory($file);
		// Save the configuration in the registry so it can be accessed from anywhere
		Zend_Registry::set('config', $config);
		return $config->toArray();
	}
}
