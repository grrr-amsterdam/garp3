<?php
/**
 * Garp_Cli_Command_Security
 * class description
 *
 * @package Garp_Cli_Command
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Cli_Command_Security extends Garp_Cli_Command {

    public function generateSalt() {
        $authFile = APPLICATION_PATH . '/configs/auth.ini';
        if (!file_exists($authFile)) {
            return;
        }

        $authIni = file_get_contents($authFile);

        // Update salt for cookies
        $authIni = preg_replace(
            '/auth\.salt = "(.*)"/',
            'auth.salt = "' . $this->_getRandomSalt() . '"',
            $authIni
        );

        // Update salt for passwords
        $authIni = preg_replace(
            '/auth\.adapters\.db\.salt = "(.*)"/',
            'auth.adapters.db.salt = "' . $this->_getRandomSalt() . '"',
            $authIni
        );

        file_put_contents($authFile, $authIni);
        return true;
    }

    protected function _getRandomSalt() {
        return bin2hex(openssl_random_pseudo_bytes(10));
    }
}

