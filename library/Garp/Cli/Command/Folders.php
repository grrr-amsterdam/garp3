<?php
/**
 * Garp_Cli_Command_Folders
 * Create some required folders.
 * They are required but older projects might have inadvertently blocked them in .gitignore.
 *
 * @package Garp_Cli_Command
 * @author Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Cli_Command_Folders extends Garp_Cli_Command {

    public function createRequired(array $args = array()) {
        if (!file_exists('public/cached')) {
            mkdir('public/cached');
        }
        if (!file_exists('application/data/cache')) {
            mkdir('application/data/cache');
        }
        if (!file_exists('application/data/cache/tags')) {
            mkdir('application/data/cache/tags');
        }
        return true;
    }

}
