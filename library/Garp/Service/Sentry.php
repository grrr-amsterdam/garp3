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
        return Zend_Registry::isRegistered('RavenClient');
    }

    public function log(Exception $exception) {
        if (!$this->isActive()) {
            return;
        }

        $ravenClient = Zend_Registry::get('RavenClient');
        $event_id = $ravenClient->getIdent(
            $ravenClient->captureException(
                $exception,
                array(
                    'extra' => $this->_getBasicVars(),
                    'user' => $this->_getUserVars(),
                )
            )
        );
    }

    protected function _getBasicVars(): array {
        return array(
            'garp_version' => $this->_readGarpVersion(),
        );
    }

    protected function _getUserVars(): array {
        // Add logged in user data to log
        $auth = Garp_Auth::getInstance();
        return $auth->isLoggedIn() ? $auth->getUserData() : array();
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
