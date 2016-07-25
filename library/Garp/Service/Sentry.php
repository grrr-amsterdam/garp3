<?php
/**
 * Garp_Service_Sentry
 * Error Monitoring through https://getsentry.com
 * @author David Spreekmeester <david@grrr.nl>
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Service
 * @lastmodified $Date: $
 */
class Garp_Service_Sentry {
    private static $instance;


    public static function getInstance() {
        if (null === static::$instance) {
            static::$instance = new static();
        }
        
        return static::$instance;
    }

    protected function __construct() {}
    private function __clone() {}
    private function __wakeup() {}

    /**
     * Returns whether the Raven client (needed for Sentry) is configured / enabled.
     */
    public function isActive() {
        global $ravenClient;

        return (bool)$ravenClient;
    }

    public function log(Exception $exception) {
        global $ravenClient;

        if (!$this->isActive()) {
            return;
        }

        $debugVars = $this->_getBasicVars();
        $debugVars += $this->_getUserVars();

        $varList = array('extra' => $debugVars);

        $event_id = $ravenClient->getIdent(
            $ravenClient->captureException($exception, $varList)
        );
    }

    protected function _getBasicVars() {
        return array(
            '_php_version' => phpversion(),
            '_garp_version' => Garp_Version::VERSION,
            'extensions' => get_loaded_extensions()
        );
    }

    protected function _getUserVars() {
        // Add logged in user data to log
        $auth = Garp_Auth::getInstance();
        $output = array();

        if ($auth->isLoggedIn()) {
            $output['_user_data'] = $auth->getUserData();
        };

        return $output;
    }
}
