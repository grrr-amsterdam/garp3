<?php
/**
 * Garp_Cli_Command_BuildProject
 * Builds a new Garp project
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class Garp_Cli_Command_BuildProject extends Garp_Cli_Command {
	/**
	 * Central start method
	 * @param Array $args Various options. Must contain;
	 * @return Void
	 */
	public function main(array $args = array()) {
		if (empty($args[0])) {
			Garp_Cli::errorOut('Insufficient arguments.');
			$this->help();
		} elseif (strtolower($args[0]) === 'help') {
			$this->help();
			return;
		} else {
			if (array_key_exists('svn', $args)) {
				$versionControl = 'svn';
			} else {
				$versionControl = 'git';
			}
			
			$projectName = $args[0];
			$projectRepo = isset($args[1]) ? $args[1] : null;
			$strategyClassName = 'Garp_Cli_Command_BuildProject_Strategy_'.ucfirst(strtolower($versionControl));
			$strategy = new $strategyClassName($projectName, $projectRepo);
			return $strategy->build();
		}
	}


	/**
 	 * Help
 	 */
	public function help() {
		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut('  g BuildProject <projectname> <repository>');
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('For projects that use Subversion, add option --svn:');
		Garp_Cli::lineOut('  g BuildProject <projectname> <repository> --svn');
		Garp_Cli::lineOut('');
	}
}
