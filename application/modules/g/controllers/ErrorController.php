<?php
/**
 * G_ErrorController
 * Handles display and logging of errors
 * @author Harmen Janssen, David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Controllers
 */
class G_ErrorController extends Garp_Controller_Action {
    public function indexAction() {
        $this->_forward('error');
    }

    public function errorAction() {
        Zend_Registry::set('CMS', false);
        $errors = $this->_getParam('error_handler');
        $this->_setLayoutForErrorResponse();
        if (!$errors) {
            return;
        }
        if (!$this->view) {
            $bootstrap = Zend_Registry::get('application')->getBootstrap();
            $this->view = $bootstrap->getResource('View');
        }

        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                // 404 error -- controller or action not found
                $this->getResponse()->setHttpResponseCode(404);
                $this->view->httpCode = 404;
                $this->view->message = 'Page not found';
            break;
            default:
                // application error
                $this->getResponse()->setHttpResponseCode(500);
                $this->view->httpCode = 500;
                $this->view->message = 'Application error';
            break;
        }

        $this->view->exception = $errors->exception;
        $this->view->request   = $errors->request;
        $displayErrorsConfig = ini_get('display_errors');
        $this->view->displayErrors = $displayErrorsConfig;

        if ($displayErrorsConfig) {
            if ($errors->exception instanceof Zend_Db_Exception) {
                $profiler = Zend_Db_Table::getDefaultAdapter()->getProfiler();
                if ($profiler && $profiler->getLastQueryProfile()) {
                    $this->view->lastQuery = $profiler->getLastQueryProfile()->getQuery();
                }
            }
        } else {
            // Oh dear, this is the production environment. This is serious.
            // Better log the error and mail a crash report to a nerd somewhere.
            if ($this->getResponse()->getHttpResponseCode() != 500) {
                return;
            }

            Garp_ErrorHandler::logErrorToFile($errors);

            if (!Garp_ErrorHandler::logErrorToSlack($errors)) {
                Garp_ErrorHandler::mailErrorToAdmin($errors);
            }
        }
    }

    protected function _setLayoutForErrorResponse() {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            return;
        }

        // Disable layout by default in XHR context
        $this->_helper->layout->disableLayout();

        // Look for JSON as primary accepted type
        $acceptTypes = $this->getRequest()->getHeader('Accept');
        if (!$acceptTypes) {
            return;
        }

        $acceptTypes = explode(',', $acceptTypes);
        if (strpos($acceptTypes[0], 'json') !== -1) {
            // In the case of XHR being true, and JSON being the primary accepted type, render a
            // Garp view with a nicely laid out error response.
            $this->_helper->layout->setLayoutPath(GARP_APPLICATION_PATH.'/modules/g/views/layouts');
            $this->_helper->layout->setLayout('json');
            $this->view->setScriptPath(GARP_APPLICATION_PATH . '/modules/g/views/scripts/');
            $this->_helper->viewRenderer('error/json', null, true);
        }
    }
}
