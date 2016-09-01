<?php
/**
 * Garp_Cli_Command_Config
 * Read configuration values
 *
 * @package Garp_Cli_Command
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Cli_Command_Config extends Garp_Cli_Command {

    public function get($args) {
        $key = $args[0];
        $env = isset($args[1]) ? $args[1] : APPLICATION_ENV;

        $application = Zend_Registry::get('application');
        $configFile = $application->getConfigFile();

        $application = new Garp_Application($env, $configFile);
        $conf = $application->getOptions();

        $bits = explode('.', $key);
        while (isset($bits[0]) && isset($conf[$bits[0]])) {
            $conf = $conf[$bits[0]];
            array_shift($bits);
        }
        Garp_Cli::lineOut(is_array($conf) ? print_r($conf, true) : $conf);

        return true;
    }

}
