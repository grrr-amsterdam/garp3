<?php
/**
 * Garp_Cli_Command_Figlet
 * Prints a figlet.
 *
 * @package Garp_Cli_Command
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Cli_Command_Figlet extends Garp_Cli_Command {
    /**
     * Display a figlet
     *
     * @param array $args
     * @return bool
     */
    public function display(array $args = array()) {
        if (empty($args)) {
            Garp_Cli::errorOut('The least you can do is provide a text...');
        } else {
            $text = implode(' ', $args);
            $figlet = new Zend_Text_Figlet();
            Garp_Cli::lineOut($figlet->render($text));
        }
        return true;
    }

    /**
     * Help
     *
     * @return bool
     */
    public function help() {
        Garp_Cli::lineOut('Usage:');
        Garp_Cli::lineOut('Display "Eat My Shorts":');
        Garp_Cli::lineOut(' g Figlet display Eat My Shorts');
        Garp_Cli::lineOut('');
        return true;
    }
}
