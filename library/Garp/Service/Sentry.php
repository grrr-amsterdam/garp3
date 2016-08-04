<?php
/**
 * Garp_Service_Sentry
 * Error Monitoring through https://getsentry.com
 *
 * @package Garp_Service
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Service_Sentry {
    private static $_instance;

    public static function getInstance() {
        if (null === static::$_instance) {
            static::$_instance = new static();
        }

        return static::$_instance;
    }

    protected function __construct() {
    }

    private function __clone() {
    }

    private function __wakeup() {
    }

    /**
     * Returns whether the Raven client (needed for Sentry) is configured / enabled.
     *
     * @return bool
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
            '_garp_version' => $this->_readGarpVersion(),
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

    /**
     * Read Garp version from composer.lock. This will be available when Garp is installed as
     * dependency of some app.
     *
     * @return string
     */
    protected function _readGarpVersion() {
        $versionInCaseOfError = 'v0.0.0';
        $lockFilePath = APPLICATION_PATH . '/../composer.lock';
        if (!file_exists($lockFilePath)) {
            return $versionInCaseOfError;
        }
        $lockFile = json_decode(file_get_contents($lockFilePath), true);
        $packages = $lockFile['packages'];

        return array_reduce(
            $packages,
            function ($prevVersion, $package) {
                // Found Garp? Return its version
                if ($package['name'] === 'grrr-amsterdam/garp3') {
                    return $package['version'];
                }
                // Otherwise return whatever version we previously got
                return $prevVersion;
            },
            // Initial value
            $versionInCaseOfError
        );
    }
}
