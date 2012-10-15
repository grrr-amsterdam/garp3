<?php
/**
 * G_View_Helper_Config
 * Helper that assists in obtaining application config values.
 * 
 * Usage in case of parameter 'organization.facebook' in application.ini:
 * echo $this->config()->organization->facebook
 * 
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @lastmodified $Date: $
 */
class G_View_Helper_Config extends Zend_View_Helper_Abstract {


	public function config() {
		return $this;
	}


	public function __get($key) {
		$ini = Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/application.ini');
		return $ini->{$key};
	}
}