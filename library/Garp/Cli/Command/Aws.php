<?php
/**
 * Garp_Cli_Command_S3
 * Wrapper around awscmd
 *
 * @package Garp_Cli_Command
 * @author Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Cli_Command_Aws extends Garp_Cli_Command {
    const AWS_CONFIG_LOCATION = '.aws/config';

    /**
     * The profile used by the current environment
     */
    protected $_profile;

    /**
     * Set and/or create profile in awscmd.
     * Also check wether this project actually uses Amazon.
     *
     * @param array $args
     * @return bool
     */
    public function main(array $args = array()) {
        if (!$this->_usesAmazon()) {
            Garp_Cli::errorOut(
                'Clearly this environment does not ' .
                'use Amazon services. Get outta here!'
            );
            return false;
        }

        $this->_setProfile();

        if (!$this->_profileExists()) {
            $this->_createProfile();
        }
        return parent::main($args);
    }

    /**
     * Execute s3 methods
     *
     * @param str $cmd The command (for instance 'ls')
     * @param array $args Various arguments
     * @return bool
     */
    public function s3($cmd, $args) {
        $this->_exec('s3', $cmd, $args);
        return true;
    }

    /**
     * Execute s3api methods
     *
     * @param str $cmd The command
     * @param array $args Various arguments
     * @return bool
     */
    public function s3api($cmd, $args) {
        $this->_exec('s3api', $cmd, $args);
        return true;
    }

    /**
     * Display help
     *
     * @return void
     */
    public function help() {
        Garp_Cli::lineOut('This is a wrapper around Amazon awscmd.', Garp_Cli::YELLOW);
        Garp_Cli::lineOut(
            "Make sure it's " .
            "installed on your machine (go to http://aws.amazon.com/cli/ for instructions)"
        );
        Garp_Cli::lineOut('Note that this wrapper manages your awscmd profiles for you.');

        Garp_Cli::lineOut('');
        Garp_Cli::lineOut(
            'Use S3 accounts of ' .
            'various environments by adding the usual --e parameter:'
        );
        Garp_Cli::lineOut(' g aws s3 ls uploads/ --e=production', Garp_Cli::BLUE);
    }

    /**
     * Execute awscmd function
     *
     * @param str $group For instance s3, or ec2
     * @param str $subCmd For instance 'ls' or 'cp'
     * @param array $args Further commandline arguments
     * @return bool
     */
    protected function _exec($group, $subCmd, $args) {
        $keys = array_keys($args);
        $cmd = "aws $group $subCmd";
        foreach ($keys as $key) {
            $cmd .= is_numeric($key) ? ' ' : " --{$key}";
            $cmd .= true === $args[$key] ? '' : ' ' . $args[$key];
        }

        $cmd .= " --profile {$this->_profile}";
        return passthru($cmd);
    }

    /**
     * Set the current profile
     *
     * @return void
     */
    protected function _setProfile() {
        $projectName = $this->_toolkit->getCurrentProject();
        $profileName = $projectName . '_' . APPLICATION_ENV;

        $this->_profile = $profileName;
    }

    /**
     * Check if the current profile exists
     *
     * @return bool
     */
    protected function _profileExists() {
        $homeDir = trim(`echo \$HOME`);
        $config = file_get_contents($homeDir . DIRECTORY_SEPARATOR . self::AWS_CONFIG_LOCATION);
        return strpos($config, "[profile {$this->_profile}]") !== false;
    }

    /**
     * Create the currently used profile
     *
     * @return void
     */
    protected function _createProfile() {
        $config = Zend_Registry::get('config');

        $confStr = "[profile {$this->_profile}]\n";
        $confStr .= "aws_access_key_id = {$config->cdn->s3->apikey}\n";
        $confStr .= "aws_secret_access_key = {$config->cdn->s3->secret}\n";
        $confStr .= "output = json\n";
        $confStr .= "region = eu-west-1\n\n";

        $homeDir = trim(`echo \$HOME`);
        file_put_contents(
            $homeDir . DIRECTORY_SEPARATOR . self::AWS_CONFIG_LOCATION,
            $confStr, FILE_APPEND
        );
    }

    /**
     * Check wether environment actually uses Amazon
     *
     * @return bool
     */
    protected function _usesAmazon() {
        $config = Zend_Registry::get('config');
        return !empty($config->cdn->s3->apikey) && !empty($config->cdn->s3->secret);
    }
}
