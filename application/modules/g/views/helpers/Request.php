<?php
/**
 * G_View_Helper_Request
 * Helps analyze the server request from inside a view.
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Helper
 */
class G_View_Helper_Request extends Zend_View_Helper_Abstract {
	/**
	 * Chain method.
	 * @return G_View_Helper_Request
	 */
	public function request() {
		return $this;
	}


	/**
	 * @return String The requested controller name
	 */
	public function getControllerName() {
		$ctrl = Zend_Controller_Front::getInstance();
		return $ctrl->getRequest()->getControllerName();
	}


	/**
	 * @return String The requested action name
	 */
	public function getActionName() {
		$ctrl = Zend_Controller_Front::getInstance();
		return $ctrl->getRequest()->getActionName();
	}	
}
