<?php
/**
 * Garp_Cli_Command_Content
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class Garp_Cli_Command_Content extends Garp_Cli_Command {
	protected $_environments = array('development', 'integration', 'staging', 'production');

	
	public function sync(array $args) {
		$this->_validateSyncArguments($args);
		$sourceEnv = $this->_getSourceEnv($args);
		$targetEnv = $this->_getTargetEnv($args);
		
		$sourceFileList = Garp_Content_Upload_FileList_Factory::create($sourceEnv);
		
		Zend_Debug::dump((array)$sourceFileList);
		exit;
	}


	public function help() {
		Garp_Cli::lineOut("☞  U s a g e :\n");
		Garp_Cli::lineOut("Synchronizing content:");
		Garp_Cli::lineOut("\tg content sync [source environment] [target environment]");
		Garp_Cli::lineOut("");
		
		Garp_Cli::lineOut("Example of synchronizing all content from staging to production:");
		Garp_Cli::lineOut("\tg content sync staging production");
		Garp_Cli::lineOut("");
	}
	
	
	protected function _validateSyncArguments(array $args) {
		$valid = false;
		$argCount = count($args);

		if (!array_key_exists(0, $args)) {
			Garp_Cli::errorOut("No source environment provided.");
		} elseif (!array_key_exists(1, $args)) {
			Garp_Cli::errorOut("No target environment provided.");
		} elseif (!in_array($args[0], $this->_environments)) {
			Garp_Cli::errorOut("Source environment is invalid. Try: " . Garp_Util_String::humanList($this->_environments, null, 'or') . '.');
		} elseif (!in_array($args[1], $this->_environments)) {
			Garp_Cli::errorOut("Target environment is invalid. Try: " . Garp_Util_String::humanList($this->_environments, null, 'or') . '.');
		} else {
			$valid = true;
		}
		
		if (!$valid) {
			$this->help();
		}
	}


	protected function _getSourceEnv(array $args) {
		return $args[0];
	}


	protected function _getTargetEnv(array $args) {
		return $args[1];
	}
}