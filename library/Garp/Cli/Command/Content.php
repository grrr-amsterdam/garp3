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
	
	protected $_sourceEnv;
	protected $_targetEnv;

	/**
	 * Garp_Content_Upload_Mediator
	 */		
	protected $_mediator;

	
	public function sync(array $args) {
		$this->_validateSyncArguments($args);
		$this->_setSourceEnv($args);
		$this->_setTargetEnv($args);
		$this->_setMediator();
		
		$this->_syncUploads();
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
	
	
	protected function _syncUploads() {
		$transferList = $this->_mediator->fetchDiff();
		$this->_mediator->transfer($transferList);

		echo 'Transferred:';
		Zend_Debug::dump((array)$transferList);
	}

	
	protected function _setMediator() {
		$this->_mediator = new Garp_Content_Upload_Mediator($this->_sourceEnv, $this->_targetEnv);
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


	protected function _setSourceEnv(array $args) {
		$this->_sourceEnv = $args[0];
	}


	protected function _setTargetEnv(array $args) {
		$this->_targetEnv = $args[1];
	}
}