<?php
/**
 * Garp_Cache_Ini
 * Factory for cachable ini files
 * 
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cache
 * @lastmodified $Date: $
 */
class Garp_Cache_Ini {
	/**
	 * Receive a config ini file
	 * @param String $filename
	 * @return Zend_Config_Ini
	 */
	public static function factory($filename) {
		$cache = Zend_Registry::get('CacheFrontend');
		$key = preg_replace('/[^0-9a-zA-Z_]/', '_', basename($filename));		
		$config = $cache->load('Ini_Config_'.$key);
		if (!$config) {
			$config = new Zend_Config_Ini($filename, APPLICATION_ENV);
			$cache->save($config, 'Ini_Config_'.$key);
		}
		return $config;
	}
}