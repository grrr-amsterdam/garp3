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
		$suffix      = pathinfo($file, PATHINFO_EXTENSION);
        $suffix      = ($suffix === 'dist')
                     ? pathinfo(basename($file, ".$suffix"), PATHINFO_EXTENSION)
                     : $suffix;
		if ($suffix == 'ini') {
			$config = Garp_Cache_Ini::factory($file)->toArray();
		} else {
			$config = parent::_loadConfig($file);
		}
		return $config;
	}

	public function bootstrap($resource = null) {
		Zend_Registry::set('config', new Zend_Config($this->getOptions()));
		return parent::bootstrap();
	}
}
