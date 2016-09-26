<?php
/**
 * Garp_ErrorHandler
 * class description
 *
 * @package Garp
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_ErrorHandler {
    const ERROR_REPORT_MAIL_ADDRESS_FALLBACK = 'garp@grrr.nl';

    /**
     * Handles premature exceptions thrown before the MVC ErrorHandler is initialized.
     * Exceptions of that kind will result in a blank page if displayErrors is off, instead of
     * redirected to an error page (which would be the case for exceptions thrown by a controller,
     * for instance).
     *
     * @param Exception $e
     * @return void
     */
    public static function handlePrematureException(Exception $e) {
        $error = new Zend_Controller_Plugin_ErrorHandler();
        $error->type = Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER;
        $error->exception = $e;

        $request = Zend_Controller_Front::getInstance()->getRequest();
        if (!$request) {
            return;
        }
        $request->setModuleName('default');
        $request->setControllerName('error');
        $request->setActionName('error');
        $request->setParam('error_handler', $error);

        $error->request = clone $request;
    }

    /**
     * Log that pesky error to a file
     *
     * @param ArrayObject $errors
     * @return void
     */
    public static function logErrorToFile($errors) {
        $errorMessage = self::_composeFullErrorMessage($errors);

        $stream = fopen(APPLICATION_PATH . '/data/logs/errors.log', 'a');
        $writer = new Zend_Log_Writer_Stream($stream);
        $logger = new Zend_Log($writer);
        $logger->log($errorMessage, Zend_Log::ALERT);
    }

    /**
     * Log error to Slack.
     * Parameter is expected to be an ArrayObject as formatted by
     * Zend_Controller_Plugin_ErrorHandler::_handleError
     *
     * @param ArrayObject $errors
     * @return bool
     */
    public static function logErrorToSlack(ArrayObject $errors) {
        try {
            $slack = new Garp_Service_Slack();
        } catch (Exception $e) {
            return false;
        }

        $shortErrorMessage = self::_composeShortErrorMessage($errors);

        //  Add first occurrence and StackTrace as attachments
        $trace = self::_filterBasePath(
            str_replace('->', '::', $errors->exception->getTraceAsString())
        );
        $params['attachments'] = array(
            array(
                'title' => self::_getExceptionClass($errors),
                'text' => $slack->wrapCodeMarkup(
                    $errors->exception->getMessage()
                    . "\n"
                    . self::_filterBasePath($errors->exception->getFile())
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
     * Mail an error to an admin
     *
     * @param ArrayObject $errors
     * @return void
     */
    public static function mailErrorToAdmin(ArrayObject $errors) {
        $errorMessage = self::_composeFullErrorMessage($errors);
        $subjectPrefix = '';
        if (isset($_SERVER) && !empty($_SERVER['HTTP_HOST'])) {
            $subjectPrefix = '[' . $_SERVER['HTTP_HOST'] . '] ';
        }

        $ini = Zend_Registry::get('config');
        $to = (
            isset($ini->app) &&
            isset($ini->app->errorReportEmailAddress) &&
            $ini->app->errorReportEmailAddress
        )
            ? $ini->app->errorReportEmailAddress
            : self::ERROR_REPORT_MAIL_ADDRESS_FALLBACK
        ;

        $mailer = new Garp_Mailer();
        return $mailer->send(
            array(
            'to' => $to,
            'subject' => $subjectPrefix . 'An application error occurred',
            'message' => $errorMessage
            )
        );
    }

    protected static function _composeShortErrorMessage(ArrayObject $errors) {
        $appName = self::_getApplicationName();

        $exceptionType = self::_getExceptionClass($errors);
        $article = self::_startsWithVowel($exceptionType) ? 'an' : 'a';
        $message = "Found {$article} `{$exceptionType}` in project `"
            . $appName . "` :neutral_face:\n";

        // Add user information
        $auth = Garp_Auth::getInstance();
        if ($auth->isLoggedIn()) {
            $userData = $auth->getUserData();
            $message .= 'Caused by '
                . $userData['role'] . ' '
                . new Garp_Util_FullName($userData)
                . (isset($userData['email']) ? ' (' . $userData['email'] . ')' : '')
                . "\n"
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
        if (isset($errors->request)) {
            $fullUrl = new Garp_Util_FullUrl($errors->request->getRequestUri());
            $message .= "Url: <{$fullUrl}|{$errors->request->getRequestUri()}>";
        }

        return $message;
    }

    /**
     *  Rewrites full paths to relative paths (in StackTrace)
     *
     *  @param string $stringWithFullPaths
     *  @return string
     */
    protected static function _filterBasePath($stringWithFullPaths) {
        return str_replace(BASE_PATH, '', $stringWithFullPaths);
    }

    protected static function _getExceptionClass(ArrayObject $errors) {
        return get_class($errors->exception);
    }

    protected static function _getApplicationName() {
        $deployConfig = new Garp_Deploy_Config();
        try {
            $appName = $deployConfig->getParam('production', 'application');
        } catch (Exception $e) {
            return isset(Zend_Registry::get('config')->app->name) ?
                Zend_Registry::get('config')->app->name : 'anonymous application';
        }

        return $appName;
    }

    protected static function _startsWithVowel($string) {
        $vowels = array('a', 'e', 'i', 'o', 'u');
        return in_array(strtolower($string[0]), $vowels);
    }

    protected static function _composeFullErrorMessage(ArrayObject $errors) {
        $appName = self::_getApplicationName();
        $errorMessage = "Application: {$appName}\n\n";
        $errorMessage .= "Exception: {$errors->exception->getMessage()}\n\n";
        $errorMessage .= "Stacktrace: {$errors->exception->getTraceAsString()}\n\n";
        if (isset($errors->request)) {
            $errorMessage .= "Request URL: {$errors->request->getRequestUri()}\n\n";
        }
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
        $errorMessage .= 'Request parameters: ' .
            print_r($errors->request->getParams(), true) . "\n\n";
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
}
