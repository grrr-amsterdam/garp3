<?php
/**
 * Garp_Controller_Helper_LayoutBroker
 * This helper ensures the right layout folder is used from within a module.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Controller
 * @lastmodified $Date: $
 */
class Garp_Controller_Helper_LayoutBroker extends Zend_Controller_Action_Helper_Abstract {
	/**
	 * Initialize this helper. Set the layout path to /views/layouts in the current module directory
	 * @return Void
	 */
	public function preDispatch() {
		$request = $this->getRequest();
		$moduleName = $request->getModuleName();
		$layout = Zend_Controller_Action_HelperBroker::getExistingHelper('layout');
		$layout->setLayoutPath(APPLICATION_PATH.'/modules/'.$moduleName.'/views/layouts')->setLayout('layout');
	}
}