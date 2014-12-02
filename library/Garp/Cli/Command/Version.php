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
		$module = 'app';
		if (!empty($args)) {
			$module = $args[0];
		}
		$validModules = array('app', 'garp');
		if (!in_array($module, $validModules)) {
			throw new Garp_Cli_Command_Exception_InvalidParameter('The given module is invalid. Must be "app" or "garp"');
		}

		// Make sure we're in the project root before executing git rev-parse
		$gitRoot = $this->_getGitRoot($module);
		$oldWorkingDir = getcwd();
		chdir($gitRoot);

		// Fetch hash of HEAD revision
		$headGitHash = $this->_getGitHeadHash();

		// Change back into original dir
		chdir($oldWorkingDir);

		$versionConstantName = $this->_getVersionConstantName($module);
		$phpStatement = "<?php define('$versionConstantName', '$headGitHash');";
		$versionFilePath = $this->_getVersionFilePath($module);
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

	/**
 	 * Get the correct version file to update.
 	 * @param String $module 'app' or 'garp'
 	 * @return String
 	 */
	protected function _getVersionFilePath($module) {
		$prefix = APPLICATION_PATH;
 	   	if (strtolower($module) == 'garp') {
			$prefix = GARP_APPLICATION_PATH;
		}
		$path = $prefix.'/configs/version.php';
		return $path;
	}

	/**
 	 * Get the Git repo root
 	 * @param String $module 'app' or 'garp'
 	 * @return String
 	 */
	protected function _getGitRoot($module) {
		$path = APPLICATION_PATH.'/..';
		if ($module == 'garp') {
			$path = GARP_APPLICATION_PATH.'/..';
		}
		return $path;
	}

	/**
 	 * Get the constant name used to contain the version
 	 * @param String $module 'app' or 'garp'
 	 * @return String
 	 */
	protected function _getVersionConstantName($module) {
		$const = 'APP_VERSION';
		if ($module == 'garp') {
			$const = 'GARP_VERSION';
		}
		return $const;
	}

	/**
 	 * Get git HEAD hash
 	 * @return String
 	 */
	protected function _getGitHeadHash() {
		// Fetch hash of HEAD revision
		$headGitHash = `git rev-parse --verify --short HEAD`;
		
		// This might be the case when working with an exported repo
		// @todo Think about how to handle that
		if (is_null($headGitHash)) {
			$headGitHash = substr(uniqid(), 0, 7);
			Garp_Cli::errorOut('No git revision info available. Falling back to random id.');
		}
		$headGitHash = trim($headGitHash);
		return $headGitHash;
	}
}
