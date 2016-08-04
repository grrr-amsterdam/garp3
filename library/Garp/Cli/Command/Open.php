<?php
/**
 * Garp_Cli_Command_Open
 * Opens the site in the browser
 *
 * @package Garp_Cli_Command
 * @author Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Cli_Command_Open extends Garp_Cli_Command {
    public function main(array $args = array()) {
        $domain = isset(Zend_Registry::get('config')->app->domain) ?
            Zend_Registry::get('config')->app->domain : null;
        if (!$domain) {
            Garp_Cli::errorOut('No domain found. Please configure app.domain');
            return false;
        }
        `open http://$domain`;
        return true;
    }
}
