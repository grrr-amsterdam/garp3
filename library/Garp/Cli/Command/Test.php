<?php

/**
 * Garp_Cli_Command_Test
 *
 * @package Garp_Cli
 * @author  David Spreekmeester <david@grrr.nl>
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Cli_Command_Test extends Garp_Cli_Command {
    protected $_garpPath = 'vendor/grrr-amsterdam/garp3/tests/';
    protected $_appPath = 'tests/';
    protected $_command = 'phpunit --verbose --colors ';

    /**
     * Central start method
     *
     * @param array $args
     * @return bool
     */
    public function main(array $args = array()) {
        if (1 === count($args) && !empty($args[0]) && 'help' === strtolower($args[0])) {
            $this->help();
            return true;
        }
        // check for illegal options
        $allowedArgs = array('module', 'group');
        foreach ($args as $key => $value) {
            if (!in_array($key, $allowedArgs)) {
                Garp_Cli::errorOut('Illegal option ' . $key);
                Garp_Cli::lineOut('Type \'g Test help\' for usage');
                return false;
            }
        }

        $command = $this->_command;
        if (!empty($args['group'])) {
            $command .= '--group=' . $args['group'] . ' ';
        }

        if (array_key_exists('module', $args) && $args['module']) {
            if ($args['module'] === 'garp') {
                $path = $this->_garpPath;
                $command .= '--bootstrap vendor/grrr-amsterdam/garp3/tests/TestHelper.php ';
                $command .= $path;
            } elseif ($args['module'] === 'default') {
                $path = $this->_appPath;
                $command .= '--bootstrap tests/TestHelper.php ';
                $command .= $path;
            } else {
                throw new Exception(
                    "Only 'garp' and 'default' are valid configurable " .
                    "modules for the test environment."
                );
            }
        } else {
            $command .= '--bootstrap tests/TestHelper.php ';
            $command .= $this->_appPath . ' && ' . $command . $this->_garpPath;
        }
        system($command, $returnValue);
        return $returnValue;
    }

    /**
     * Help method
     *
     * @return bool
     */
    public function help() {
        Garp_Cli::lineOut('Usage:');
        Garp_Cli::lineOut('Test everything:');
        Garp_Cli::lineOut('  g Test');
        Garp_Cli::lineOut('');
        Garp_Cli::lineOut('Only execute Garp tests:');
        Garp_Cli::lineOut('  g Test --module=garp');
        Garp_Cli::lineOut('');
        Garp_Cli::lineOut('Only execute App tests:');
        Garp_Cli::lineOut('  g Test --module=default');
        Garp_Cli::lineOut('');
        Garp_Cli::lineOut('Execute Garp tests within the group "Cache":');
        Garp_Cli::lineOut('  g Test --module=garp --group=Cache');
        Garp_Cli::lineOut('');
        return true;
    }
}
