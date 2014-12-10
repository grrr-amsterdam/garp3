<?php
/**
 * Garp_Cli_Command_Slack
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class Garp_Cli_Command_Slack extends Garp_Cli_Command {
	const ERROR_EMPTY_SEND =
 	   "You didn't tell me *what* you'd like to send.";


	/**
	 * Test if Slack is enabled for this project
	 * @return Void
	 */
	public function test() {
		$slack = new Garp_Service_Slack();
		$works = $slack->isEnabled();

		if (!$works) {
			Garp_Cli::errorOut("Slack is not set up for this project.");
			Garp_Cli::lineOut($slack->getConfigInstruction());
			exit(1);
		}

		Garp_Cli::lineOut("√ Slack seems to be enabled.", Garp_Cli::GREEN);
	}

	/**
	 * Post a message in a Slack channel
	 * @return Void
	 */
	public function send(array $args = array()) {
		if (!$args || !array_key_exists(0, $args) || empty($args[0])) {
			Garp_Cli::errorOut(self::ERROR_EMPTY_SEND);
			exit(1);
		}

		$slack = new Garp_Service_Slack();
		$slack->postMessage($args[0]);
	}

	/**
 	 * Help
 	 */
	public function help() {
		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut('Check if Slack is enabled:');
		Garp_Cli::lineOut('  g slack test', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('Send Slack message:');
		Garp_Cli::lineOut('  g slack send "Hello world"', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
		return true;
	}
}
