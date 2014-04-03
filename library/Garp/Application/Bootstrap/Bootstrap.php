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
	 * Initiate autoloading of Models
	 * @return Void
	 */
	protected function _initAutoloadModels() {
		new Zend_Loader_Autoloader_Resource(array(
			'basePath' => APPLICATION_PATH.'/modules/default',
			'namespace' => '',
			'resourceTypes' => array(
				'model' => array(
					'path' => 'models/',
					'namespace' => 'Model_'
				)
			)
		));
		new Zend_Loader_Autoloader_Resource(array(
			'basePath' => APPLICATION_PATH.'/modules/g',
			'namespace' => '',
			'resourceTypes' => array(
				'model' => array(
					'path' => 'models/',
					'namespace' => 'G_Model_'
				)
			)
		));
	}


	/**
	 * Load essential Garp Helpers
	 * @return Void
	 */
	protected function _initEssentialGarpHelpers() {
		// Action helpers
		Zend_Controller_Action_HelperBroker::addHelper(new Garp_Controller_Helper_LayoutBroker());
		Zend_Controller_Action_HelperBroker::addHelper(new Garp_Controller_Helper_Auth());
		Zend_Controller_Action_HelperBroker::addHelper(new Garp_Controller_Helper_Download());
		Zend_Controller_Action_HelperBroker::addHelper(new Garp_Controller_Helper_FlashMessenger());
		
		// View helpers
		$this->bootstrap('View');
		$this->getResource('View')->addHelperPath(APPLICATION_PATH.'/modules/g/views/helpers', 'G_View_Helper');
	}
}
