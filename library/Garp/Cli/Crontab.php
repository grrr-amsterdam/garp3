<?php
/**
 * Garp_Cli_Crontab
 * Manages crontab files
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class Garp_Cli_Crontab {
	/**
 	 * Class constructor
 	 * @return Void
 	 */
	public function __construct() {
	}


	/**
 	 * Fetch current jobs
 	 * @return Array
 	 */
	public function fetchAll() {
		$crontab = $this->_exec('crontab -l');
		$crontab = trim($crontab);
		if (preg_match('/no crontab for/', $crontab)) {
			return null;
		} else {
			$cronjobs = explode("\n", $crontab);
			return $cronjobs;
		}
	}


	/**
 	 * Execute command lines
 	 * @param String $command
 	 * @return String
 	 */
	protected function _exec($command) {
		return shell_exec($command);
	}
}
