<?php
/**
 * Garp_Controller_Plugin_LayoutBroker
 * Sets the layout path to that of the current module.
 *
 * @author       $Author: harmen $
 * @modifiedby   $LastChangedBy: harmen $
 * @version      $LastChangedRevision: 6312 $
 * @package      Garp
 * @subpackage   Controller
 * @lastmodified $LastChangedDate: 2012-09-18 00:25:03 +0200 (Tue, 18 Sep 2012) $
 */
class Garp_Controller_Plugin_LayoutBroker extends Zend_Controller_Plugin_Abstract {

	public function preDispatch(Zend_Controller_Request_Abstract $request) {
		$request = $this->getRequest();
		$moduleName = $request->getModuleName();
		$frontController = Zend_Controller_Front::getInstance();
		$currentModuleDirectory = $frontController->getModuleDirectory($moduleName);
		$layout = Zend_Controller_Action_HelperBroker::getExistingHelper('layout');
		$layout->setLayoutPath($currentModuleDirectory.'/views/layouts')->setLayout('layout');
	}

}
