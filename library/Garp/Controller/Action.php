<?php
/**
 * Garp_Controller_Action
 * Base controller class.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Controller
 * @lastmodified $Date: $
 */
class Garp_Controller_Action extends Zend_Controller_Action {	
	/**
	 * Pre-dispatch routines
	 *
	 * Called before action method. If using class with
	 * {@link Zend_Controller_Front}, it may modify the
	 * {@link $_request Request object} and reset its dispatched flag in order
	 * to skip processing the current action.
	 *
	 * @return void
	 */
	public function preDispatch() {
		parent::preDispatch();
		$this->_setLayout();
		$this->_setFlashMessage();
	}
	

	/**
	 * Post-dispatch routines
	 *
	 * Called after action method execution. If using class with
	 * {@link Zend_Controller_Front}, it may modify the
	 * {@link $_request Request object} and reset its dispatched flag in order
	 * to process an additional action.
	 *
	 * Common usages for postDispatch() include rendering content in a sitewide
	 * template, link url correction, setting headers, etc.
	 *
	 * @return void
	 */
	public function postDispatch() {
		parent::postDispatch();
	}
	
	
	/**
	 * Make sure the layout from the current module is used.
	 * @return Void
	 */
	protected function _setLayout() {
		$request = $this->getRequest();
		$moduleName = $request->getModuleName();
		// make sure the layout directory defaults to /views/layouts in the correct module directory
		$this->_helper->layout->setLayoutPath(APPLICATION_PATH.'/modules/'.$moduleName.'/views/layouts')->setLayout('layout');
	}


	/**
	 * Make flash messages known to the view, but only when store.type is Session.
	 * When it is cookie Javascript handles the display (and removal from storage).
	 * @return Void
	 */
	protected function _setFlashMessage() {
		$ini = Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/application.ini');
		if (!isset($ini->store->type) || $ini->store->type == 'Session') {
			$this->view->flashMessages = $this->getHelper('FlashMessenger')->getMessages();
		}
	}
}
