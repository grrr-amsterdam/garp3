<?php
/**
 * Garp_Application_Bootstrap_Bootstrap
 * Common Bootstrap functionality
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Bootstrap
 * @lastmodified $Date: $
 */
class Garp_Application_Bootstrap_Bootstrap extends Zend_Application_Bootstrap_Bootstrap {
	/**
 	 * Init plugin loader cache
 	 * @return Void
 	 */
	protected function _initPluginLoaderCache() {
		$classFileIncCache = APPLICATION_PATH.'/data/cache/pluginLoaderCache.php';
		/**
 		 * Suppress include error, to save file_exists check.
 		 * If it's not there, it's no problem, so a simple "@" will do.
 		 */
		//@include_once $classFileIncCache;
		//Zend_Loader_PluginLoader::setIncludeFileCache($classFileIncCache);
	}


	/**
	 * Load essential Garp Helpers
	 * @return Void
	 */
	protected function _initEssentialGarpHelpers() {
		// Action helpers
		Zend_Controller_Action_HelperBroker::addPrefix('App_Controller_Helper');
		Zend_Controller_Action_HelperBroker::addPrefix('Garp_Controller_Helper');

		// View helpers
		$this->bootstrap('View');
		$this->getResource('View')->addHelperPath(APPLICATION_PATH.'/modules/default/views/helpers', 'App_View_Helper');
		$this->getResource('View')->addHelperPath(GARP_APPLICATION_PATH.'/modules/g/views/helpers', 'G_View_Helper');
	}


	/**
 	 * Combine the info found in application.ini with the info found in the Info table
 	 * @return Void
 	 */
	protected function _initConfig() {
		return;
		$this->bootstrap('db');
		$loader = Garp_Loader::getInstance();
		if ($loader->isLoadable('Model_Info')) {
			$config = Zend_Registry::get('config');
			$infoModel = new Model_Info();
			$data = $infoModel->fetchAsConfig();
		}
	}
}
