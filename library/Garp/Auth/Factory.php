<?php
/**
 * Garp_Auth_Factory
 * Create instances of Zend_Auth_Adapter_Interface by using Garp_Auth_Factory_Interface objects.
 * These objects prepare the adapter so it can be directly used with Zend_Auth::authenticate().
 * Note that this factory needs a list of supported adapters in application.ini, under 
 * auth.adapters. 
 * 
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Auth
 * @lastmodified $Date: $
 */
class Garp_Auth_Factory {
	/**
	 * Retrieve a specified Zend_Auth_Adapter_Interface object.
	 * @param String $key The authentication key. An adapter must be stored under auth.adapters.{$key}
	 * 					  in application.ini.
	 * @param Array $postData Submitted login data, to be passed along to the adapter factory
	 * @return Garp_Auth
	 */
	public static function getAdapter($key) {
		$config	= Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/application.ini', APPLICATION_ENV);
		if (!$config->auth || !$config->auth->adapters) {
			throw new Garp_Auth_Exception('auth.adapters not found in application.ini,'.
											' or it is not an array');
		}
		
		if (!$config->auth->adapters->{$key}) {
			throw new Garp_Auth_Exception("Adapter with key $key not found in auth.adapters".
											" in application.ini");
		}
		
		$classKey = $config->auth->adapters->{$key}->class;
		if (!$classKey) {
			$classKey = $key;
		}
		$className = strpos($classKey, '_') === false ? 'Garp_Auth_Adapter_'.ucfirst($classKey) : $classKey;
		$obj = new $className();
		if (!$obj instanceof Garp_Auth_Adapter_Abstract) {
			throw new Garp_Auth_Exception("Class $className does not implement Garp_Auth_Adapter.");
		}
		return $obj;
	}
}
