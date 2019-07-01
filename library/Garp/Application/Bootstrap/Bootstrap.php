<?php
/**
 * Garp_Application_Bootstrap_Bootstrap
 * Common Bootstrap functionality
 *
 * @package Garp_Application_Bootstrap
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Application_Bootstrap_Bootstrap extends Zend_Application_Bootstrap_Bootstrap {
    /**
     * Init plugin loader cache
     *
     * @return void
     */
    protected function _initPluginLoaderCache() {
        $classFileIncCache = APPLICATION_PATH . '/data/cache/pluginLoaderCache.php';
        /**
         * Suppress include error, to save file_exists check.
         * If it's not there, it's no problem, so a simple "@" will do.
         */
        //@include_once $classFileIncCache;
        //Zend_Loader_PluginLoader::setIncludeFileCache($classFileIncCache);
    }

    /**
     * Load essential Garp Helpers
     *
     * @return void
     */
    protected function _initEssentialGarpHelpers() {
        // Action helpers
        Zend_Controller_Action_HelperBroker::addPath(
            GARP_APPLICATION_PATH .
            '/../library/Garp/Controller/Helper', 'Garp_Controller_Helper'
        );
        Zend_Controller_Action_HelperBroker::addPath(
            APPLICATION_PATH .
            '/../library/App/Controller/Helper', 'App_Controller_Helper'
        );

        // View helpers
        $this->bootstrap('View');
        $this->getResource('View')->addHelperPath(
            GARP_APPLICATION_PATH .
            '/modules/g/views/helpers', 'G_View_Helper'
        );
        $this->getResource('View')->addHelperPath(
            APPLICATION_PATH .
            '/modules/default/views/helpers', 'App_View_Helper'
        );
    }

    /**
     * Combine the static info found in application.ini with the
     * dynamic info found in the Info table.
     *
     * @return void
     */
    protected function _initConfig() {
        $this->bootstrap('db');
        $this->bootstrap('locale');
        if (!class_exists('Model_Info')) {
            return;
        }
        try {
            $staticConfig = Zend_Registry::get('config');
            $infoModel = $this->_getInfoModel();
            $dynamicConfig = $infoModel->fetchAsConfig(null, APPLICATION_ENV);

            // Very sneakily bypass 'readOnly'
            if ($staticConfig->readOnly()) {
                $staticConfig = new Zend_Config($staticConfig->toArray(), APPLICATION_ENV, true);
            }
            $staticConfig->merge($dynamicConfig);
            $staticConfig->setReadOnly();

            Zend_Registry::set('config', $staticConfig);
        } catch(Exception $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'Unknown database') === false
                && strpos($msg, "doesn't exist") === false
            ) {
                throw $e;
            }
        }
    }

    protected function _getInfoModel() {
        $infoModel = new Model_Info();
        if ($infoModel->isMultilingual()) {
            $infoModel = (new Garp_I18n_ModelFactory())->getModel('Info');
        }
        return $infoModel;
    }
}
