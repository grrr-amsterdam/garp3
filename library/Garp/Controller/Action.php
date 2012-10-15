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
		$this->_setFlashMessage();
	}


	/**
	 * Make flash messages known to the view, but only when store.type is Session.
	 * When it is cookie Javascript handles the display (and removal from storage).
	 * @return Void
	 */
	protected function _setFlashMessage() {
		$ini = Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/application.ini');
		if (!isset($ini->store->type) || strtolower($ini->store->type) == 'session') {
			$this->view->flashMessages = $this->getHelper('FlashMessenger')->getMessages();
		}
	}
}
