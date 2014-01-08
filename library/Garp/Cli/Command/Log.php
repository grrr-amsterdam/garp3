<?php
/**
 * Garp_Cli_Command_Log
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class Garp_Cli_Command_Log extends Garp_Cli_Command {
	/**
 	 * Help
 	 */
	public function help() {
		Garp_Cli::lineOut('Cleans all log files');
		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut('  g Log clean');
		Garp_Cli::lineOut('Options:');
		Garp_Cli::lineOut('  --root      Defaults to APPLICATION_PATH/data/logs');
		Garp_Cli::lineOut('  --patten    Defaults to /\.log$/i');
		Garp_Cli::lineOut('  --threshold Defaults to "1 month ago"');
		Garp_Cli::lineOut('  --verbose   Defaults to FALSE');
		Garp_Cli::lineOut('');
	}


	/**
 	 * Cleanup log files
 	 * @param Array $args
 	 * @return Void
 	 */
	public function clean(array $args = array()) {
		// Resolve parameters
		$logRoot   = !empty($args['root'])      ? $args['root']      : APPLICATION_PATH.'/data/logs';
		$pattern   = !empty($args['pattern'])   ? $args['pattern']   : '/\.log$/i';
		$threshold = !empty($args['threshold']) ? $args['threshold'] : '1 month ago';
		$verbose   = !empty($args['verbose'])   ? $args['verbose']   : false;

		$count      = 0;
		$leanOut    = '';
		$verboseOut = '';

		if ($handle = opendir($logRoot)) {
			while (false !== ($entry = readdir($handle))) {
				$isLogFile = preg_match($pattern, $entry);
				$isTooOld  = filemtime($logRoot.'/'.$entry) < strtotime($threshold);
				if ($isLogFile && $isTooOld) {
					@unlink($logRoot.'/'.$entry);
					++$count;
					$verboseOut .= " - $entry\n";
				}
			}
			$leanOut = "$count log files successfully removed";
			$verboseOut = $leanOut.":\n".$verboseOut;
			closedir($handle);
		}

		if ($count) {
			if ($verbose) {
				Garp_Cli::lineOut($verboseOut);
			} else {
				Garp_Cli::lineOut($leanOut);
			}
		} else {
			Garp_Cli::lineOut('No log files matched the given criteria.');
		}
	}
}
