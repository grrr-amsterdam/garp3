<?php
/**
 * Garp_Cli_Command_Cdn
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class Garp_Cli_Command_Cdn extends Garp_Cli_Command {
	
	/**
	 * Distributes the public assets on the local server to the configured CDN servers.
	 */
	public function distribute(array $args) {
		$distributor 		= new Garp_Content_Distributor();
		$filterString 		= array_key_exists(2, $args) ? $args[2] : null;
		$assetList 			= $distributor->select($filterString);
		$allEnvironments 	= $distributor->getEnvironments();

		if ($assetList) {
			$assetCount = count($assetList);
			$summary = $assetCount === 1 ? $assetList[0] : $assetCount . ' assets.';
			Garp_Cli::lineOut("Distributing {$summary}\n");
			
			$environments = array_key_exists('to', $args) ?
				(array)$args['to'] :
				$allEnvironments
			;
			
			foreach ($environments as $env) {
				$distributor->distribute($env, $assetList, $assetCount);
			}
		} else Garp_Cli::errorOut("No files to distribute.");
	}


	public function help() {
		Garp_Cli::lineOut("☞  U s a g e :\n");
		Garp_Cli::lineOut("Distributing all assets to the CDN servers:");
		Garp_Cli::lineOut("\tgarp.php Cdn distribute");
		Garp_Cli::lineOut("");
		
		Garp_Cli::lineOut("Examples of distributing a specific set of assets to the CDN servers:");
		Garp_Cli::lineOut("\tgarp.php Cdn distribute main.js");
		Garp_Cli::lineOut("\tgarp.php Cdn distribute css");
		Garp_Cli::lineOut("\tgarp.php Cdn distribute css/icons");
		Garp_Cli::lineOut("\tgarp.php Cdn distribute logos");
		Garp_Cli::lineOut("");
		
		Garp_Cli::lineOut("Distributing to a specific environment:");
		Garp_Cli::lineOut("\tgarp.php Cdn distribute --to=development");
		Garp_Cli::lineOut("\tgarp.php Cdn distribute main.js --to=staging");
		Garp_Cli::lineOut("");
	}
}