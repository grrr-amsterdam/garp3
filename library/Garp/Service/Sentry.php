<?php
/**
 * Garp_Service_Sentry
 * Error Monitoring through https://getsentry.com
 * Note! To be activated, this class requires the global $ravenClient.
 * @author David Spreekmeester <david@grrr.nl>
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Service
 * @lastmodified $Date: $
 */
class Garp_Service_Sentry {
    private static $instance;
    protected Raven_Client $_client;


    public static function getInstance() {
        global $ravenClient;
        
        if (null === static::$instance) {
            static::$instance = new static();
            $this->_client = $ravenClient;
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
        return (bool)$this->_client;
    }

    /**
     * Log an exception.
     */
    public function log(Exception $exception) {
        if (!$this->isActive()) {
            return;
        }

        $this->_client->setEnvironment(APPLICATION_ENV);
        $this->_addExtraVars();
        $this->_addUserContext();
        $this->_addReleaseVersion();
        $this->_addEnvTags();

        $this->_client->getIdent(
            $this->_client->captureException($exception)
        );
    }

    protected function _addExtraVars() {
        $extra = array(
            'extensions' => get_loaded_extensions()
        );

        $this->_client->extra_context($extra);
    }

    protected function _addEnvTags() {
        $this->_client->tags_context(array(
            'php_version' => phpversion(),
        ));
    }

    protected function _addReleaseVersion() {
        $version = new Garp_Semver();
        $this->_client->setRelease($version->getVersion());
    }

    /**
     * Add logged-in user data to the log.
     */ 
    protected function _addUserContext() {
        $auth = Garp_Auth::getInstance();
        $output = array();

        if (!$auth->isLoggedIn()) {
            return;
        };

        $this->_client->user_context($auth->getUserData());
    }
}
