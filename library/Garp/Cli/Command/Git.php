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
 	 * Automatically pulls submodules as well.
 	 * @param Array $args No arguments required, passing some will result in error.
 	 * @return Void
 	 */
	public function pull($args = array()) {
		if (!empty($args)) {
			Garp_Cli::errorOut('Invalid option: '.$args[0]);
			return false;
		}
		passthru('git pull --recurse-submodules && git submodule foreach git pull');
	}
}
