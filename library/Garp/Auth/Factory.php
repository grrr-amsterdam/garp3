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
	const EXCEPTION_NO_ADAPTERS_CONFIGURED =
		'auth.adapters not found in application.ini, or it is not an array';
	const EXCEPTION_KEY_NOT_FOUND =
		'Adapter with key %s not found in auth.adapters in application.ini';
	const EXCEPTION_INVALID_CLASS = 'Class %s does not implement Garp_Auth_Adapter.';

	const AUTH_NAMESPACE = 'Garp_Auth_Adapter_';

	/**
	 * Retrieve a specified Zend_Auth_Adapter_Interface object.
	 * @param String $key The authentication key. An adapter must be stored under
	 *                    auth.adapters.{$key}.
	 * @return Garp_Auth
	 */
	public static function getAdapter($key) {
		$config = Zend_Registry::get('config');
		if (!$config->auth || !$config->auth->adapters) {
			throw new Garp_Auth_Exception(self::EXCEPTION_NO_ADAPTERS_CONFIGURED);
		}

		$key = strtolower($key);
		if (!$config->auth->adapters->{$key}) {
			throw new Garp_Auth_Exception(sprintf(self::EXCEPTION_KEY_NOT_FOUND, $key));
		}

		$classKey = $config->auth->adapters->{$key}->class;
		if (!$classKey) {
			$classKey = $key;
		}
		$className = strpos($classKey, '_') === false ?
			self::AUTH_NAMESPACE . ucfirst($classKey) : $classKey;
		$obj = new $className();
		if (!$obj instanceof Garp_Auth_Adapter_Abstract) {
			throw new Garp_Auth_Exception(sprintf(self::EXCEPTION_INVALID_CLASS, $className));
		}
		return $obj;
	}
}
