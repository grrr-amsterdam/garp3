<?php
/**
 * Garp_Cli_Command_Image
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class Garp_Cli_Command_Cluster extends Garp_Cli_Command {
	/**
	 * Central start method
	 * @param Array $args Various options. Must contain;
	 * ['t']	String	Table name.
	 * @return Void
	 */
	public function main(array $args = array()) {
		if (empty($args)) {
			Garp_Cli::lineOut($this->_getHelpText());
		} else {
			if (!array_key_exists(1, $args)) {
				Garp_Cli::lineOut("Please indicate what you want me to do.\n");
				$this->_exit($this->_getHelpText());
			} else {
				$command = $args[1];
			
				if (method_exists($this, '_'.$command)) {
					$this->{'_'.$command}($args);
				} else {
					$this->_exit("Sorry, I don't know the command '{$command}'.\n\n".$this->_getHelpText());
				}
			}
		}
	}


	protected function _getHelpText() {
		return "Usage:\n"
			."  garp.php Cluster run\n\n"
			."  From a cronjob, APPLICATION_ENV needs to be set explicitly:\n"
			."  garp.php Cluster run --APPLICATION_ENV=development";
	}
	
	
	protected function _run($args) {
		try {
			$cluster = new Garp_Cache_Store_Cluster();
			$cluster->executeDueJobs();
		} catch (Exception $e) {
			throw new Exception('Error during execution of cluster clear job. '.$e->getMessage());
		}
		
		if (is_array($cluster->clearedTags)) {
			if (empty($cluster->clearedTags))
				Garp_Cli::lineOut('Clustered cache purged for all models.');
			else
				Garp_Cli::lineOut('Clustered cache purged for models '.implode(', ', $cluster->clearedTags));
		} elseif (is_bool($cluster->clearedTags) && !$cluster->clearedTags) {
			Garp_Cli::lineOut('No clustered cache purge jobs to run.');
		} else {
			throw new Exception("Error in clearing clustered cache.");
		}
	}


	protected function _exit($msg) {
		Garp_Cli::lineOut($msg);
		exit;
	}
}