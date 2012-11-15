<?php
/**
 * Garp_Cli_Command_Version
 * Increment Version constant. To be used by deploy script.
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
	public function increment(array $args = array()) {
		$versionFilePath = APPLICATION_PATH.'/configs/version.php';
		$version = 0;

		// read current version
		if (defined('APP_VERSION')) {
			$version = APP_VERSION;
		} elseif (is_readable($versionFilePath)) {
			require_once $versionFilePath;
			if (defined('APP_VERSION')) {
				$version = APP_VERSION;
			}
		}

		// write incremented version
		++$version;
		$phpStatement = '<?php define(\'APP_VERSION\', '.$version.');';
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
