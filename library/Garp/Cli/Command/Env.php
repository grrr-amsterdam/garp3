<?php
/**
 * Garp_Cli_Command_Env
 * Sets up the environment after deploying.
 * Override this command in the App namespace to do project-specific setup.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp_Cli_Command
 */
class Garp_Cli_Command_Env extends Garp_Cli_Command {
	public function setup(array $args = array()) {
		// Perform app-specific tasks
		$this->_init();

		// This one's free: inserting required snippets
		$snippetCmd = new Garp_Cli_Command_Snippet();
		$snippetCmd->create(array('from', 'file'));
	}

	protected function _init() {
		// overwrite in App namespace
	}

	/**
 	 * Help
 	 */
	public function help() {
		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut(' g Env setup', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
	}
}
