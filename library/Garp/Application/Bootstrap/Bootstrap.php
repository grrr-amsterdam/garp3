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
     * Get the plugin loader for resources
     * Overwritten to support Garp's own PluginLoader
     *
     * @return Zend_Loader_PluginLoader_Interface
     */
    public function getPluginLoader() {
        if ($this->_pluginLoader === null) {
            $options = array(
                'Zend_Application_Resource'  => 'Zend/Application/Resource',
                'ZendX_Application_Resource' => 'ZendX/Application/Resource'
            );
			// Only the following rule is changed from Zend_Application_Bootstrap_BootstrapAbstract
            $this->_pluginLoader = new Garp_Loader_PluginLoader($options);
        }

        return $this->_pluginLoader;
    }


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
		@include_once $classFileIncCache;
		Garp_Loader_PluginLoader::setIncludeFileCache($classFileIncCache);
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
}
