<?php
/**
 * Garp_Cli_Command_Ssh
 * Connects to server
 *
 * @package Garp_Cli_Command
 * @author Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Cli_Command_Ssh extends Garp_Cli_Command {

    public function staging() {
        $this->_openConnection('staging');
    }

    public function production() {
        $this->_openConnection('production');
    }

    public function _openConnection($environment) {
        $config = new Garp_Deploy_Config();

        $params = $config->getParams($environment);

        if (!$params) {
            Garp_Cli::errorOut(
                'No settings found for environment ' . $environment
            );
        }

        if (empty($params['server'])) {
            Garp_Cli::errorOut("'server' is a required setting.");
            return false;
        }

        // To provide a bit of backward-compatibility, convert to array
        if (!is_array($params['server'])) {
            $params['server'] = array(
                array(
                    'server' => $params['server'],
                    'user' => $params['user']
                )
            );
        }

        if (count($params['server']) === 1) {
            $this->_executeSshCommand($params['server'][0]['server'], $params['server'][0]['user']);
            return true;
        }

        $choice = Garp_Cli::prompt(
            "Choose a server to use: \n" . array_reduce(
                $params['server'],
                function ($output, $server) {
                    $number = count(explode("\n", $output));
                    $output .= "$number) {$server['server']}\n";
                    return $output;
                }, ''
            )
        );
        if (!array_key_exists($choice-1, $params['server'])) {
            Garp_Cli::errorOut('Invalid choice: ' . $choice);
            return false;
        }
        $this->_executeSshCommand(
            $params['server'][$choice-1]['server'],
            $params['server'][$choice-1]['user']
        );
    }

    protected function _executeSshCommand($server, $user) {
        passthru(
            'ssh ' . escapeshellarg($user) .
            '@' . escapeshellarg($server)
        );
    }

    public function help() {
        Garp_Cli::lineOut('Usage:');
        Garp_Cli::lineOut(' g ssh staging', Garp_Cli::BLUE);
        return true;
    }
}
