<?php
/**
 * Garp_Job_Background
 * Provides functionality for starting background jobs on the server.
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 *
 * Usage example:
 *
 * 	new Garp_Job_Background('Cluster run');
 *
 */
class Garp_Job_Background {
	/**
	 * @param String $command 	The Garp Cli command. For instance: 'Cluster run', 'Cache clear'.
	 * 							Don't include the environment parameter or the php executable.
	 */
	public function __construct($command) {
		$scriptPath = realpath(APPLICATION_PATH . '/../garp/scripts/garp.php');
		$phpCmd     = "(php {$scriptPath} {$command} --e=" . APPLICATION_ENV . ' &> /dev/null &)';

		// Tests wether SCL is available, and if so executes php thru it.
		// Otherwise executes php directly.
		exec("if command -v scl >/dev/null 2>&1; then scl enable php54 \"{$phpCmd}\" ; " .
			"else {$phpCmd}; fi;", $output, $status);

		if (!$this->_commandWasSuccessful($status)) {
			throw new Garp_Job_Background_Exception('php not available');
		}
	}

	protected function _commandWasSuccessful($status) {
		return $status === 0;
	}
}
