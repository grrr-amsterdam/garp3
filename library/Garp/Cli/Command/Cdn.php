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
	const FILTER_DATE_PARAM 			= 'since';
	const FILTER_DATE_VALUE_NEGATION 	= 'forever';
	const FILTER_ENV_PARAM 				= 'to';
	
	
	/**
	 * Distributes the public assets on the local server to the configured CDN servers.
	 */
	public function distribute(array $args) {
		$distributor 		= new Garp_Content_CDN_Distributor();
		$filterString 		= $this->_getFilterString($args);
		$filterDate			= $this->_getFilterDate($args);
		$filterEnvironments = $this->_getFilterEnvironments($args);
			
		$assetList 			= $distributor->select($filterString, $filterDate);

		if ($assetList) {
			$assetCount = count($assetList);
			$summary = $assetCount === 1 ? $assetList[0] : $assetCount . ' assets.';
			Garp_Cli::lineOut("Distributing {$summary}\n");
						
			foreach ($filterEnvironments as $env) {
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
		
		Garp_Cli::lineOut("Default only recently modified files will be distributed.");
		Garp_Cli::lineOut("To distribute all files:");
		Garp_Cli::lineOut("\tgarp.php Cdn distribute --since=forever");
		Garp_Cli::lineOut("");
		
		Garp_Cli::lineOut("To distribute files modified since a specific date (use a 'strtotime' compatible argument):");
		Garp_Cli::lineOut("\tgarp.php Cdn distribute --since=yesterday");
		Garp_Cli::lineOut("");
	}
	
	
	protected function _getFilterString(array $args) {
		return array_key_exists(0, $args) ? $args[0] : null;
	}
	
	
	protected function _getFilterDate(array $args) {
		return array_key_exists(self::FILTER_DATE_PARAM, $args) ?
			($args[self::FILTER_DATE_PARAM] === self::FILTER_DATE_VALUE_NEGATION ?
				false :
				$args[self::FILTER_DATE_PARAM]
			) :
			null
		;
	}
	
	
	protected function _getFilterEnvironments(array $args) {
		$allEnvironments 	= $distributor->getEnvironments();
		$environments 		= array_key_exists(self::FILTER_ENV_PARAM, $args) ?
			(array)$args[self::FILTER_ENV_PARAM] :
			$allEnvironments
		;
			
		return $environments;
	}
}