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
	
	/**
	 * @param String $_sourceEnv The id of the source environment
	 */
	protected $_sourceEnv;

	/**
	 * @param String $_targetEnv The id of the target environment
	 */
	protected $_targetEnv;

	
	public function sync(array $args) {
		$this->_validateSyncArguments($args);
		$this->_setSourceEnv($args);
		$this->_setTargetEnv($args);

		// Garp_Cli::lineOut("\nSyncronizing {$this->_sourceEnv} → {$this->_targetEnv}\n");
		// 
		// $this->_syncUploads();
		
		Garp_Cli::lineOut("\n");

		$this->_syncDb();
		
		Garp_Cli::lineOut("\n");
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
		$progress = Garp_Cli_Ui_ProgressBar::getInstance();
		$progress->init(1);
		Garp_Cli::lineOut("Analyzing uploads");

		$mediator = new Garp_Content_Upload_Mediator($this->_sourceEnv, $this->_targetEnv);
		$transferList = $mediator->fetchDiff();

		if ($transferTotal = count($transferList)) {
			Garp_Cli::lineOut("\n\nTransferring {$transferTotal} files");

			/*	total * 2, because both fetching the source and storing
				on target count as an advance on the progressbar. */
			$progress->init($transferTotal * 2);

			$mediator->transfer($transferList);
			$progress->display("√ Transferred {$transferTotal} files.");
		} else {
			$progress->advance();
			$progress->display("√ Done, no files to transfer.");
		}
	}

	protected function _syncDb() {
		$progress = Garp_Cli_Ui_ProgressBar::getInstance();
		$progress->init(1);
		Garp_Cli::lineOut("Synchronizing database");

		$progress->display("Backing up existing database");
		
		$mediator = new Garp_Content_Db_Mediator($this->_sourceEnv, $this->_targetEnv);
		$mediator->transfer();
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
			exit;
		}
	}


	protected function _setSourceEnv(array $args) {
		$this->_sourceEnv = $args[0];
	}


	protected function _setTargetEnv(array $args) {
		$this->_targetEnv = $args[1];
	}
}