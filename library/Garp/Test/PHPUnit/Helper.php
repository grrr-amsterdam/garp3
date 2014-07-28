<?php
/**
 * Garp_Test_PHPUnit_Helper
 * Collection of handy unit testing helper methods.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Garp_Test_PHPUnit
 */
class Garp_Test_PHPUnit_Helper {
		
	/**
 	 * Force certain config values @ runtime
 	 * @param Array $dynamicConfig
 	 * @return Void 
 	 */
	public function injectConfigValues(array $dynamicConfig) {
		$config = Zend_Registry::get('config');
		// Very sneakily bypass 'readOnly'
		if ($config->readOnly()) {
			$config = new Zend_Config($config->toArray(), APPLICATION_ENV, true);
		}
		$config->merge(new Zend_Config($dynamicConfig));
		$config->setReadOnly();

		Zend_Registry::set('config', $config);
	}

}
