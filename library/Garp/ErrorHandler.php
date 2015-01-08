<?php
class Garp_ErrorHandler {
	/**
 	 * Handles premature exceptions thrown before the MVC ErrorHandler is initialized.
 	 * Exceptions of that kind will result in a blank page is displayErrors is off, instead of
 	 * redirected to an error page (which would be the case for exceptions thrown by a controller,
 	 * for instance).
 	 */
	public static function handlePrematureException(Exception $e) {
		$request = Zend_Controller_Front::getInstance()->getRequest();
		$request->setModuleName('default');
		$request->setControllerName('error');
		$request->setActionName('error');

		$error = new Zend_Controller_Plugin_ErrorHandler();
		$error->type = Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER;
		$error->request = clone $request;
		$error->exception = $e;
		$request->setParam('error_handler', $error);
	}
}
