<?php
define('INDENT', '        ');

function p($msg = '', $indent = true) {
	$msg = Garp_Model_Spawn_Util::addStringColoring($msg);
	echo ($indent ? INDENT : '').$msg."\n";
}


/**
 * Garp_Cli_Command_Spawn
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class Garp_Cli_Command_Spawn extends Garp_Cli_Command {
	protected $_configDir;
	protected $_extension = 'json';
	protected $_allowedFilters = array('files', 'db', 'js', 'php');
	
	/**
	 * @param Garp_Model_Spawn_ModelSet $_modelSet
	 */
	protected $_modelSet;


	/**
	 * Central start method
	 * @return Void
	 */
	public function main(array $args = array()) {
		if (array_key_exists(0, $args)) {
			if (strcasecmp($args[0], 'help') === 0) {
				$this->_displayHelp();
			} else {
				Garp_Cli::errorOut("Sorry, I do not know the '{$args[0]}' argument. Try 'garp Spawn help' for an overview of options.");
			}
			exit;
		}

		$this->_init();

		if (array_key_exists('showJsBaseModel', $args)) {
			$this->_showJsBaseModel($args['showJsBaseModel']);
		} else {
			$filter = null;

			if (array_key_exists('only', $args)) {
				$args['only'] = strtolower($args['only']);
				if (!in_array($args['only'], $this->_allowedFilters)) {
					Garp_Cli::errorOut("Sorry, '{$args['only']}' is not a valid value for the '--only' parameter. Try 'garp Spawn help' for an overview of options.");
					exit;
				} else $filter = $args['only'];
			}

			$this->_spawn($filter);
		}
	}

	
	protected function _init() {
		$this->_configDir = APPLICATION_PATH."/modules/default/models/config/";
		$this->_modelSet = new Garp_Model_Spawn_ModelSet(
			new Garp_Model_Spawn_Config_Model_Set(
				new Garp_Model_Spawn_Config_Storage_File($this->_configDir, $this->_extension),
				new Garp_Model_Spawn_Config_Format_Json
			)
		);
	}


	/**
	 * @param String $filter An optional filter to skip certain tasks. Has to be a value from $this->_allowedFilters.
	 */
	protected function _spawn($filter = null) {
		if ($filter !== 'db') {
			echo "\nFiles\n";
		
			switch ($filter) {
				case 'js':
					$totalActions = count($this->_modelSet) + 2;
				break;
				case 'php':
					$totalActions = count($this->_modelSet);
				break;
				default:
					$totalActions = (count($this->_modelSet) * 2) + 2;
			}


			$progress = Garp_Cli_Ui_ProgressBar::getInstance();
			$progress->init($totalActions);


			if ($filter !== 'php') {
				$progress->display("Cooking up base model goo.");
				$this->_modelSet->materializeCombinedBaseModel();
				$progress->advance();
		
				$progress->display("Including models in model loader");
				$this->_modelSet->includeInJsModelLoader();
				$progress->advance();
			}

			foreach ($this->_modelSet as $model) {
				if ($filter !== 'js') {
					$progress->display($model->id . " PHP models, %d to go.");
					$model->materializePhpModels($this->_modelSet);
					$progress->advance();
				}

				if ($filter !== 'php') {
					$progress->display($model->id . " extended models, %d to go.");
					$model->materializeExtendedJsModels($this->_modelSet);
					$progress->advance();
				}
			}
		
			$progress->display("√ Done");
			echo "\n";
		}
		
		if ($filter === 'db' || is_null($filter)) {
			echo "\nDatabase\n";
			$dbManager = new Garp_Model_Spawn_MySql_Manager($this->_modelSet);
			
			echo "\n\n";

			Garp_Cache_Manager::purge();
			echo "All cache purged.";
		};
		
		echo "\n";
	}


	protected function _showJsBaseModel($modelId) {
		if (array_key_exists($modelId, $this->_modelSet)) {
			$model = $this->_modelSet[$modelId];
			$minBaseModel = $model->renderBaseModel($this->_modelSet);
			require_once(APPLICATION_PATH.'/../library/Garp/3rdParty/JsBeautifier/jsbeautifier.php');
			echo js_beautify($minBaseModel) . "\n";
		} else {
			Garp_Cli::errorOut("I don't know the model {$modelId}.");
			Garp_Cli::lineOut("I do know " . implode(", ", array_keys((array)$this->_modelSet)) . '.');
		}
	}


	protected function _displayHelp() {
		Garp_Cli::lineOut("\n• Filtering");
		Garp_Cli::lineOut("garp Spawn --only=files");
		Garp_Cli::lineOut("\tOnly Spawn files, skip the database");
		echo "\n";
		Garp_Cli::lineOut("garp Spawn --only=js");
		Garp_Cli::lineOut("\tOnly Spawn Javascript files,");
		Garp_Cli::lineOut("\tskip PHP files and the database");
		echo "\n";
		Garp_Cli::lineOut("garp Spawn --only=php");
		Garp_Cli::lineOut("\tOnly Spawn PHP files,");
		Garp_Cli::lineOut("\tskip Javascript files and the database");
		echo "\n";
		Garp_Cli::lineOut("garp Spawn --only=db");
		Garp_Cli::lineOut("\tOnly Spawn database,");
		Garp_Cli::lineOut("\tskip file generation");
		echo "\n";
		Garp_Cli::lineOut("\n• Debugging");
		Garp_Cli::lineOut("garp Spawn --showJsBaseModel=YourModel");
		Garp_Cli::lineOut("\tShow the non-minified JS base model.");
		echo "\n";
		Garp_Cli::lineOut("garp Spawn --showJsBaseModel=YourModel > YourFile.json");
		Garp_Cli::lineOut("\tWrite the non-minified JS base model to a file.");

		echo "\n";
	}
}
