<?php
/**
 * Garp_Cli_Command_Gumball
 * Create a packaged version of the project, including database and source files.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Garp_Cli_Command
 */
class Garp_Cli_Command_Gumball extends Garp_Cli_Command {
    const PROMPT_OVERWRITE = 'Existing gumball found for version %s. Do you wish to overwrite?';
    const PROMPT_SOURCE_DATABASE_ENVIRONMENT = 'Take database from which environment? (production)';
    const PROMPT_INCLUDE_DATABASE = 'Do you want to include a database with this gumball?';
    const DEFAULT_SOURCE_DATABASE_ENVIRONMENT = 'production';

    const ABORT_NO_OVERWRITE = 'Stopping gumball creation, existing gumball stays untouched.';
    const ABORT_CANT_MKDIR_gumballS = 'Error: cannot create gumballs directory';
    const ABORT_CANT_MKDIR_TARGET_DIRECTORY = 'Error: cannot create target directory';
    const ABORT_CANT_COPY_SOURCEFILES = 'Error: cannot copy source files to target directory';
    const ABORT_CANT_WRITE_ZIP = 'Error: cannot create zip file';
    const ABORT_DATADUMP_FAILED = 'Error: datadump failed';

    const ERROR_SOURCE_ENV_NOT_CONFIGURED = 'Error: the database source environment was not configured. Cannot migrate data.';

    public function make($args = array()) {
        $mem = new Garp_Util_Memory();
        $mem->useHighMemory();

        // @todo Superduperbonusmode: would be cool if you could go back in time and generate a
        // gumball for a given semver (using Git to grab the correct tag).
        // There would be no way to include that moment's data though.
        $version = new Garp_Semver();
        Garp_Cli::lineOut('Creating gumball ' . $version, Garp_Cli::PURPLE);

        $fromEnv = null;
        if ($useDb = Garp_Cli::confirm(self::PROMPT_INCLUDE_DATABASE)) {
            $fromEnv = Garp_Cli::prompt(self::PROMPT_SOURCE_DATABASE_ENVIRONMENT) ?:
                self::DEFAULT_SOURCE_DATABASE_ENVIRONMENT;
        }

        $gumball = new Garp_Gumball($version, array(
            'useDatabase' => $useDb,
            'databaseSourceEnvironment' => $fromEnv
        ));

        if ($gumball->exists() &&
            !Garp_Cli::confirm(sprintf(self::PROMPT_OVERWRITE, $version))) {
            Garp_Cli::lineOut(self::ABORT_NO_OVERWRITE, Garp_Cli::YELLOW);
            exit(1);
        }

        $gumball->exists() && $gumball->remove();

        if (!$this->_createGumballDirectory()) {
            Garp_Cli::errorOut(self::ABORT_CANT_MKDIR_gumballS);
            exit(1);
        }

        try {
            $gumball->make();
        } catch (Garp_Gumball_Exception_CannotWriteTargetDirectory $e) {
            Garp_Cli::errorOut(self::ABORT_CANT_MKDIR_TARGET_DIRECTORY);
            exit(1);
        } catch (Garp_Gumball_Exception_CannotCopySourceFiles $e) {
            Garp_Cli::errorOut(self::ABORT_CANT_COPY_SOURCEFILES);
            exit(1);
        } catch (Garp_Gumball_Exception_CannotCreateZip $e) {
            Garp_Cli::errorOut(self::ABORT_CANT_WRITE_ZIP);
            exit(1);
        } catch (Garp_Gumball_Exception_DatadumpFailed $e) {
            Garp_Cli::errorOut(self::ABORT_DATADUMP_FAILED);
            exit(1);
        }

    }

    public function restore($args = array()) {
        $mem = new Garp_Util_Memory();
        $mem->useHighMemory();

        $version = new Garp_Semver();
        Garp_Cli::lineOut('Restoring gumball ' . $version, Garp_Cli::PURPLE);
        $gumball = new Garp_Gumball($version);
        try {
            $gumball->restore();
            $this->_broadcastGumballInstallation($version);
            Garp_Cli::lineOut('Done!', Garp_Cli::GREEN);
        } catch (Garp_Gumball_Exception_SourceEnvNotConfigured $e) {
            Garp_Cli::errorOut(self::ERROR_SOURCE_ENV_NOT_CONFIGURED);
            exit(1);
        } catch (Exception $e) {
            Garp_Cli::errorOut('Error: ' . $e->getMessage());
            exit(1);
        }
    }

    protected function _broadcastGumballInstallation($version) {
        $this->_broadcastByMail($version);
        $this->_broadcastToSlack($version);
    }

    protected function _broadcastByMail($version) {
        $config = Zend_Registry::get('config');
        if (!isset($config->gumball->notificationEmail) || !$config->gumball->notificationEmail) {
            return;
        }

        $mailer = new Garp_Mailer();
        $mailer->send(array(
            'to' => $config->gumball->notificationEmail,
            'subject' => sprintf($this->_getRestoreEmailSubject(), $config->app->name),
            'message' => sprintf($this->_getRestoreEmailMessage(),
                $config->app->name,
                APPLICATION_ENV,
                $version,
                $config->app->domain
            )
        ));
    }

    protected function _broadcastToSlack($version) {
        $slack = new Garp_Service_Slack();

        $slack->postMessage('', array(
            'attachments' => array(
                array(
                    'pretext' => 'A new gumball was deployed',
                    'color' => '#7CD197',
                    'fields' => array(
                        array(
                            'title' => 'Project',
                            'value' => Zend_Registry::get('config')->app->name,
                            'short' => false
                        ),
                        array(
                            'title' => 'Environment',
                            'value' => APPLICATION_ENV,
                            'short' => false
                        ),
                        array(
                            'title' => 'Version',
                            'value' => (string)$version,
                            'short' => false
                        )
                    )
                )
            )
        ));
    }

    protected function _createGumballDirectory() {
        if (!file_exists($this->_getGumballDirectory())) {
            return mkdir($this->_getGumballDirectory());
        }
        return true;
    }

    protected function _getGumballDirectory() {
        return APPLICATION_PATH . '/../gumballs';
    }

    /**
     * Note: these used to be snippets but in multilingual environments snippets are not loaded for
     * this command in the CLI environment.
     * Anyways. It's not that important, for the time being this is a fine message.
     */
    protected function _getRestoreEmailSubject() {
        return '[%s] Een nieuwe versie staat live';
    }

    protected function _getRestoreEmailMessage() {
        return "Hallo, \n\n" .
            "Een nieuwe versie van %s is zojuist live gezet.\n\n" .
            "Omgeving: %s\n" .
            "Versie: %s\n\n" .
            "Bekijk hier: %s";
    }
}
