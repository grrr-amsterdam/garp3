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
		if (!Zend_Registry::isRegistered('config')) {
			throw new Exception('config is not found in the registry.');
		}
		$ini = Zend_Registry::get('config');
		return $ini->{$key};
	}
}
