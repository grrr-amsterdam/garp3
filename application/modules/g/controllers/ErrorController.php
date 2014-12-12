<?php
/**
 * G_ErrorController
 * Handles display and logging of errors
 * @author Harmen Janssen, David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Controllers
 */
class G_ErrorController extends Garp_Controller_Action {
	const ERROR_REPORT_MAIL_ADDRESS_FALLBACK = 'garp@grrr.nl';
	const SLACK_CHANNEL = '#garp-errors';
	const SLACK_USERNAME = 'Golem';

	public function indexAction() {
		$this->_forward('error');
	}

	public function errorAction() {
		$errors = $this->_getParam('error_handler');
		if ($this->getRequest()->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout();
		}
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

			$errorMessage = $this->_composeFullErrorMessage($errors);
			$this->_logError($errorMessage);

			if (!$this->_logToSlack($errors)) {
				$this->_mailAdmin($errorMessage);
			}
		}
	}

	protected function _logToSlack(ArrayObject $errors) {
		$slack = new Garp_Service_Slack();

		if (!$slack->isEnabled()) {
			return false;
		}

		$shortErrorMessage = $this->_composeShortErrorMessage($errors);

		$params = array(
			'channel' => self::SLACK_CHANNEL,
			'icon_emoji' => ':squirrel:',
			'username' => self::SLACK_USERNAME
		);

		//	Add first occurrence and StackTrace as attachments
		$trace = $this->_filterBasePath(
			str_replace('->', '::', $errors->exception->getTraceAsString())
		);
		$params['attachments'] = array(
			array(
				'title' => $this->_getExceptionClass($errors),
				'text' => $slack->wrapCodeMarkup(
					$errors->exception->getMessage()
					. "\n"
					. $this->_filterBasePath($errors->exception->getFile())
					. ': '
					. $errors->exception->getLine()
				),
				'color' => '#bb5555',
				'mrkdwn_in' => array('text'),
				'short' => true
			),
			array(
				'title' => 'StackTrace',
				'text' => $slack->wrapCodeMarkup($trace),
				'color' => '#6666ee',
				'mrkdwn_in' => array('text'),
				'short' => true
			)
		);

		$slack->postMessage($shortErrorMessage, $params);

		return true;
	}

	/**
 	 *	Rewrites full paths to relative paths (in StackTrace)
 	 */
	protected function _filterBasePath($stringWithFullPaths) {
		return str_replace(BASE_PATH, '', $stringWithFullPaths);
	}

	protected function _startsWithVowel($string) {
		$vowels = array('a', 'e', 'i', 'o', 'u');
		return in_array(strtolower($string[0]), $vowels);
	}

	protected function _getExceptionClass(ArrayObject $errors) {
		return get_class($errors->exception);
	}

	protected function _composeShortErrorMessage(ArrayObject $errors) {
		$appName = $this->_getApplicationName();

		$exceptionType = $this->_getExceptionClass($errors);
		$article = $this->_startsWithVowel($exceptionType) ? 'an' : 'a';
		$message = "Found {$article} `{$exceptionType}` in project `"
			. $appName . "` :neutral_face:\n";

		// Add user information
		$auth = Garp_Auth::getInstance();
		if ($auth->isLoggedIn()) {
			$userData = $auth->getUserData();
			$message .= 'Caused by '
				. $userData['role'] . ' '
				. $userData['name']
				. ' (' . $userData['email'] . ").\n"
			;
		}

		// Add environment and IP
		$message .= 'On `' . APPLICATION_ENV . '`';
		if (!empty($_SERVER['REMOTE_ADDR'])) {
			$message .= " ({$_SERVER['SERVER_NAME']}, "
					. "{$_SERVER['REMOTE_ADDR']})";
		}
		$message .= "\n";

		// Add url
		$fullUrl = new Garp_Util_FullUrl();
		$message .= "Url: <{$fullUrl}|{$errors->request->getRequestUri()}>";


		return $message;
	}


	protected function _composeFullErrorMessage(ArrayObject $errors) {
		$errorMessage = "Application: {$this->_getApplicationName()}\n\n";
		$errorMessage .= "Exception: {$errors->exception->getMessage()}\n\n";
		$errorMessage .= "Stacktrace: {$errors->exception->getTraceAsString()}\n\n";
		$errorMessage .= "Request URL: {$errors->request->getRequestUri()}\n\n";
		// Referer
		if (!empty($_SERVER['HTTP_REFERER'])) {
			$errorMessage .= "Referer: {$_SERVER['HTTP_REFERER']}\n\n";
		} else {
			$errorMessage .= "Referer: n/a\n\n";
		}
		// IP Addr
		if (!empty($_SERVER['REMOTE_ADDR'])) {
			$errorMessage .= "IP address: {$_SERVER['REMOTE_ADDR']}\n\n";
		} else {
			$errorMessage .= "IP address: n/a\n\n";
		}
		// User agent
		if (!empty($_SERVER['HTTP_USER_AGENT'])) {
			$errorMessage .= "User agent: {$_SERVER['HTTP_USER_AGENT']}\n\n";
		} else {
			$errorMessage .= "User agent: n/a\n\n";
		}
		// Request params
		$errorMessage .= 'Request parameters: '.print_r($errors->request->getParams(), true)."\n\n";
		// User data
		$errorMessage .= 'User data: ';

		$auth = Garp_Auth::getInstance();
		if ($auth->isLoggedIn()) {
			$errorMessage .= print_r($auth->getUserData(), true);
		} else {
			$errorMessage .= 'n/a';
		}
		$errorMessage .= "\n\n";

		return $errorMessage;
	}


	protected function _getApplicationName() {
		$deployConfig = new Garp_Deploy_Config();
		$appName = $deployConfig->getParam('production', 'application');

		return $appName;
	}

	/**
 	 * Log that pesky error
 	 * @param String $message
 	 * @return Void
 	 */
	protected function _logError($message) {
		$stream = fopen(APPLICATION_PATH.'/data/logs/errors.log', 'a');
		$writer = new Zend_Log_Writer_Stream($stream);
		$logger = new Zend_Log($writer);
		$logger->log($message, Zend_Log::ALERT);
	}

	/**
 	 * Mail an error to an admin
 	 * @param String $message
 	 * @return Void
 	 */
	protected function _mailAdmin($message) {
		$subjectPrefix = '';
		if (isset($_SERVER) && !empty($_SERVER['HTTP_HOST'])) {
			$subjectPrefix = '['.$_SERVER['HTTP_HOST'].'] ';
		}

		$ini = Zend_Registry::get('config');
		$to = (isset($ini->app) && isset($ini->app->errorReportEmailAddress) && $ini->app->errorReportEmailAddress) ?
			$ini->app->errorReportEmailAddress :
			self::ERROR_REPORT_MAIL_ADDRESS_FALLBACK
		;

		mail(
			$to,
			$subjectPrefix.'An application error occurred',
			$message,
			'From: ' . self::ERROR_REPORT_MAIL_ADDRESS_FALLBACK
		);
	}
}
