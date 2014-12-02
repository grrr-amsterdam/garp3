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
		$phpPath = (
			array_key_exists('_', $_SERVER) &&
			$_SERVER['_']
		) ?
			$_SERVER['_'] :
			'/usr/bin/php'
		;

		exec("{$phpPath} {$scriptPath} {$command} --e=" . APPLICATION_ENV . " &> /dev/null &");
	}
}
