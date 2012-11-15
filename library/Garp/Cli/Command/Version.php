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
		// Make sure we're in the project root before executing git rev-parse
		$oldWorkingDir = getcwd();
		chdir(APPLICATION_PATH.'/..');

		// Fetch hash of HEAD revision
		$headGitHash = `git rev-parse --verify --short HEAD`; //  2> /dev/null

		// Change back into original dir
		chdir($oldWorkingDir);

		// This might be the case when working with an exported repo
		// @todo Think about how to handle that
		if (is_null($headGitHash)) {
			$headGitHash = substr(uniqid(), 0, 7);
			Garp_Cli::errorOut('No git revision info available. Falling back to random id.');
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
