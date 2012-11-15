<?php
/**
 * Garp_Cli_Command_Git
 * Providing a user-friendly Git interface.
 *
 * @author       $Author:$
 * @modifiedby   $LastChangedBy:$
 * @version      $LastChangedRevision:$
 * @package      Garp
 * @subpackage   Cli
 * @lastmodified $LastChangedDate:$
 */
class Garp_Cli_Command_Git extends Garp_Cli_Command {
	/**
 	 * Setup Git as per Grrr conventions
 	 * @return Void
 	 */
	public function setup() {
		Garp_Cli::lineOut('Configuring Git...');
		// configure core.fileMode
		passthru('git config core.fileMode false');
		// configure color.ui
		passthru('git config color.ui auto');
		
		// setup git hook for incrementing version
		$hookSource = GARP_APPLICATION_PATH.'/../scripts/util/pre-commit';
		$hookTarget = APPLICATION_PATH.'/../.git/hooks/pre-commit';
		$performTheMove = true;
		if (file_exists($hookTarget)) {
			// Warn use about existing hook. Might be accidental
			$performTheMove = Garp_Cli::confirm('Pre-commit hook already in place. Overwrite?');
		}
		if ($performTheMove) {
			passthru("cp $hookSource $hookTarget");
			// Make hook executable
			passthru("chmod u+x $hookTarget");
		}
		Garp_Cli::lineOut('Done.');
	}


	/**
 	 * Automatically pulls submodules as well.
 	 * @param Array $args No arguments required, passing some will result in error.
 	 * @return Void
 	 */
	public function pull(array $args = array()) {
		if (!empty($args)) {
			Garp_Cli::errorOut('Invalid option: '.$args[0]);
			return false;
		}
		passthru('git pull --recurse-submodules && git submodule foreach git pull');
	}


	/**
 	 * Commit a submodule
 	 * @param Array $args
 	 * @return Void
 	 */
	public function commitSubmodule(array $args = array()) {
		if (empty($args[0])) {
			Garp_Cli::errorOut('Specify submodule as first argument.');
			return false;
		}
		if (empty($args['m'])) {
			Garp_Cli::errorOut('Aborting commit due to empty commit message');
			return false;
		}
		$submodule = $args[0];
		$submodule = rtrim($submodule, DIRECTORY_SEPARATOR);
		$commitMessage = $args['m'];

		$projectRoot = getcwd();

		// 1) Change into submodule
		chdir($submodule);

		// 2) Read status and organise index
		$gitStatus = `git status --porcelain`;
		$gitStatus = explode("\n", $gitStatus);
		foreach ($gitStatus as $statusLine) {
			if (!$statusLine) {
				continue;
			}
			$statusCodeIndex = $statusLine[0];
			$statusCodeWorkingCopy = $statusLine[1];
			$path = substr($statusLine, 3);

			// Status code checks
			$modifiedButNotAdded = $statusCodeWorkingCopy == 'M';
			$removedFile = $statusCodeWorkingCopy == 'D' && $statusCodeIndex == ' ';
			$untrackedFile = $statusCodeIndex == '?' && $statusCodeWorkingCopy == '?';
			if ($modifiedButNotAdded) {
				passthru('git add '.$path);
			} elseif ($removedFile) {
				$removeFile = Garp_Cli::prompt('Do you want to remove file '.$path.'? Yn');
				if (!$removeFile || strtolower($removeFile) == 'y') {
					passthru('git rm '.$path);
				}
			} elseif ($untrackedFile) {
				$addFile = Garp_Cli::prompt('Do you want to add untracked file '.$path.'? Yn');
				if (!$addFile || strtolower($addFile) == 'y') {
					passthru('git add '.$path);
				}
			}
		}

		// 3) Commit and push!
		passthru('git commit -m \''.$commitMessage.'\'');
		passthru('git push origin master');

		// 4) Change back to project root
		chdir($projectRoot);

		// 5) Add and commit the submodule
		passthru('git add '.$submodule);
		$submoduleCommitMessage = 'Updated '.basename($submodule);
		passthru('git commit -m \''.$submoduleCommitMessage.'\'');
	}


	/**
 	 * Help
 	 */
	public function help() {
		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut('Pull from remote and update submodules');
		Garp_Cli::lineOut('  g Git pull');
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('Commit a submodule');
		Garp_Cli::lineOut('  g Git commitSubmodule garp --m=\'<your commit message>\'');
		Garp_Cli::lineOut('');
	}
}
