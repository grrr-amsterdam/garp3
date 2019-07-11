<?php
use Sentry\State\Hub;

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
    public function isActive(): bool {
        return !is_null(Hub::getCurrent()->getClient());
    }

    public function log(Exception $exception): void {
        if (!$this->isActive()) {
            return;
        }
        \Sentry\configureScope(function (\Sentry\State\Scope $scope): void {
            $scope->setUser($this->_getUserVars());
            $scope->setTag('garp_version', $this->_readGarpVersion());
        });
        \Sentry\captureException($exception);
    }

    protected function _getUserVars(): array {
        // Add logged in user data to log
        $auth = Garp_Auth::getInstance();
        return $auth->isLoggedIn() ? $auth->getUserData() : [];
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
