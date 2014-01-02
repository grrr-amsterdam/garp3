<?php
/**
 * Garp_Cli_Command_Cache
 * 
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class Garp_Cli_Command_Cache extends Garp_Cli_Command {
	/**
 	 * Clear all the cache
 	 * @param Array $args Tags.
 	 * @return Void
 	 */
	public function clear(array $args = array()) {
		Garp_Cache_Manager::purge($args);
		Garp_Cli::lineOut('All cache purged.');
	}


	public function getStoredUrls() {
		$urls = Garp_Cache_Manager::getStoredUrls();
		if (is_array($urls)) {
			print_r($urls);	
		} else {
			var_dump($urls);
		}
	}
}
