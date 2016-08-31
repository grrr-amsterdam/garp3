<?php
/**
 * Garp_Cli_Command_Slack
 *
 * @package Garp_Cli_Command
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Cli_Command_Slack extends Garp_Cli_Command {
    const ERROR_EMPTY_SEND
        = "You didn't tell me *what* you'd like to send.";

    /**
     * Post a message in a Slack channel
     *
     * @param array $args
     * @return bool
     */
    public function send(array $args = array()) {
        if (!$args || !array_key_exists(0, $args) || empty($args[0])) {
            Garp_Cli::errorOut(self::ERROR_EMPTY_SEND);
            return false;
        }

        $slack = new Garp_Service_Slack();
        return $slack->postMessage($args[0]);
    }

    /**
     * Help
     *
     * @return bool
     */
    public function help() {
        Garp_Cli::lineOut('Usage:');
        Garp_Cli::lineOut('Send Slack message:');
        Garp_Cli::lineOut('  g slack send "Hello world"', Garp_Cli::BLUE);
        Garp_Cli::lineOut('');
        return true;
    }
}
