<?php
/**
 * Garp_Cli_Command_Version
 * Increment Version constant. To be used by deploy script and Git hooks.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp
 * @subpackage   Cli
 */
class Garp_Cli_Command_Version extends Garp_Cli_Command {
	/**
 	 * Increment version
 	 */
	public function update(array $args = array()) {
		$versionFilePath = APPLICATION_PATH.'/configs/version.php';
		$headGitHash = `git rev-parse --verify --short HEAD`;
		// This might be the case when working with an exported repo
		// @todo Think about how to handle that
		if (is_null($headGitHash)) {
			$headGitHash = substr(uniqid(), 0, 7);
		}
		$headGitHash = trim($headGitHash);
		$phpStatement = '<?php define(\'APP_VERSION\', \''.$headGitHash.'\');';
		if (false === file_put_contents($versionFilePath, $phpStatement)) {
			Garp_Cli::errorOut('Could not write contents to file.');
			return false;
		}
		Garp_Cli::lineOut('Done.');
		return true;
	}

	/**
 	 * Show usage
 	 */
   	public function help(array $args = array()) {
   		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut('  g Version increment');
		return true;
   	}
}
