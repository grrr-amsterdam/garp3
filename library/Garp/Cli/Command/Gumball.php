<?php
/**
 * Garp_Cli_Command_Gumball
 * Create a packaged version of the project, including database and source files.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Garp_Cli_Command
 */
class Garp_Cli_Command_Gumball extends Garp_Cli_Command {
	const PROMPT_OVERWRITE = 'Existing gumball found for version %s. Do you wish to overwrite?';
	const PROMPT_SOURCE_DATABASE_ENVIRONMENT = 'Take database from which environment? (production)';
	const PROMPT_INCLUDE_DATABASE = 'Do you want to include a database with this gumball?';
	const DEFAULT_SOURCE_DATABASE_ENVIRONMENT = 'production';

	const ABORT_NO_OVERWRITE = 'Stopping gumball creation, existing gumball stays untouched.';
	const ABORT_CANT_MKDIR_gumballS = 'Error: cannot create gumballs directory';
	const ABORT_CANT_MKDIR_TARGET_DIRECTORY = 'Error: cannot create target directory';
	const ABORT_CANT_COPY_SOURCEFILES = 'Error: cannot copy source files to target directory';
	const ABORT_CANT_WRITE_ZIP = 'Error: cannot create zip file';
	const ABORT_DATADUMP_FAILED = 'Error: datadump failed';

	public function make($args = array()) {
		// @todo Superduperbonusmode: would be cool if you could go back in time and generate a
		// gumball for a given semver (using Git to grab the correct tag).
		// There would be no way to include that moment's data though.
		$version = new Garp_Semver();
		Garp_Cli::lineOut('Creating gumball ' . $version, Garp_Cli::PURPLE);

		$fromEnv = null;
		if ($useDb = Garp_Cli::confirm(self::PROMPT_INCLUDE_DATABASE)) {
			$fromEnv = Garp_Cli::prompt(self::PROMPT_SOURCE_DATABASE_ENVIRONMENT) ?:
				self::DEFAULT_SOURCE_DATABASE_ENVIRONMENT;
		}

		$gumball = new Garp_Gumball($version, array(
			'useDatabase' => $useDb,
			'databaseSourceEnvironment' => $fromEnv
		));

		if ($gumball->exists() &&
			!Garp_Cli::confirm(sprintf(self::PROMPT_OVERWRITE, $version))) {
			Garp_Cli::lineOut(self::ABORT_NO_OVERWRITE, Garp_Cli::YELLOW);
			exit(1);
		}

		$gumball->exists() && $gumball->remove();

		if (!$this->_createGumballDirectory()) {
			Garp_Cli::errorOut(self::ABORT_CANT_MKDIR_gumballS);
			exit(1);
		}

		try {
			$gumball->make();
		} catch (Garp_Gumball_Exception_CannotWriteTargetDirectory $e) {
			Garp_Cli::errorOut(self::ABORT_CANT_MKDIR_TARGET_DIRECTORY);
			exit(1);
		} catch (Garp_Gumball_Exception_CannotCopySourceFiles $e) {
			Garp_Cli::errorOut(self::ABORT_CANT_COPY_SOURCEFILES);
			exit(1);
		} catch (Garp_Gumball_Exception_CannotCreateZip $e) {
			Garp_Cli::errorOut(self::ABORT_CANT_WRITE_ZIP);
			exit(1);
		} catch (Garp_Gumball_Exception_DatadumpFailed $e) {
			Garp_Cli::errorOut(self::ABORT_DATADUMP_FAILED);
			exit(1);
		}

	}

	public function restore($args = array()) {

	}

	protected function _createGumballDirectory() {
		if (!file_exists($this->_getGumballDirectory())) {
			return mkdir($this->_getGumballDirectory());
		}
		return true;
	}

	protected function _getGumballDirectory() {
		return APPLICATION_PATH . '/../gumballs';
	}
}
