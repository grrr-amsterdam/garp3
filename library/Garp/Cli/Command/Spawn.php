<?php
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
	/**
	 * The command to dump a specific Javascript base model - since this
	 * is normally concatenated and minified with the other base models.
	 */
	const JS_BASE_MODEL_COMMAND = 'showJsBaseModel';
	
	/**
	 * The argument used to spawn only the database, or only files.
	 */
	const FILTER_MODULE_COMMAND = 'only';
	const FILTER_MODULE_PHP 	= 'php';
	const FILTER_MODULE_DB 		= 'db';
	const FILTER_MODULE_JS 		= 'js';
	const FILTER_MODULE_FILES 	= 'files';
	
	const ERROR_UNKNOWN_ARGUMENT = 
		"Sorry, I do not know the '%s' argument. Try 'garp spawn help' for an overview of options.";	
	const ERROR_ILLEGAL_MODULE_FILTER = 
		"Sorry, '%s' is not a valid value for the '--only' parameter. Try 'garp spawn help' for an overview of options.";


	/**
	 * @var Array $_allowedFilters
	 */
	protected $_allowedFilters = array('files', 'db', 'js', 'php');
	
	/**
	 * @var Garp_Spawn_Model_Set $_modelSet
	 */
	protected $_modelSet;
	
	/**
	 * @var Array $_args The arguments given when calling this command
	 */
	protected $_args;	


	/**
	 * Central start method
	 * @return Void
	 */
	public function main(array $args = array()) {
		if ($this->_isHelpRequested($args)) {
			$this->_displayHelp();
			exit;
		}

		$this->setArgs($args);

		$this->setModelSet($this->_initModelSet());

		array_key_exists(self::JS_BASE_MODEL_COMMAND, $args) ?
			$this->_showJsBaseModel($args[self::JS_BASE_MODEL_COMMAND]) :
			$this->_spawn()
		;
	}

	/**
	 * Returns the module that should be run, i.e. 'db' or 'files', or null if no filter is given.
	 */
	public function getModuleFilter() {
		$args 	= $this->getArgs();
		$only	= self::FILTER_MODULE_COMMAND;
		
		if (!array_key_exists($only, $args)) {
			return;
		}

		$filter = $args[$only];		
		return strtolower($filter);
	}
	
	/**
	 * @return Array
	 */
	public function getArgs() {
		return $this->_args;
	}
	
	/**
	 * @param Array $args
	 */
	public function setArgs($args) {
		$this->_validateArguments($args);
		$this->_args = $args;
	}
	
	/**
	 * @return Array
	 */
	public function getAllowedFilters() {
		return $this->_allowedFilters;
	}
	
	/**
	 * @param Array $allowedFilters
	 */
	public function setAllowedFilters($allowedFilters) {
		$this->_allowedFilters = $allowedFilters;
	}	
	
	/**
	 * @return Garp_Spawn_Model_Set
	 */
	public function getModelSet() {
		return $this->_modelSet;
	}
	
	/**
	 * @param Garp_Spawn_Model_Set $modelSet
	 */
	public function setModelSet($modelSet) {
		$this->_modelSet = $modelSet;
	}

	protected function _initModelSet() {
		$modelSet = new Garp_Spawn_Model_Set(new Garp_Spawn_Config_Model_Set());

		return $modelSet;
	}

	/**
	 * Spawn JS and PHP files and the database structure.
	 */
	protected function _spawn() {
		$dbShouldSpawn  		= $this->_shouldSpawnDb();
		$jsShouldSpawn			= $this->_shouldSpawnJs();
		$phpShouldSpawn			= $this->_shouldSpawnPhp();
		$someFilesShouldSpawn	= $jsShouldSpawn || $phpShouldSpawn;

		if ($someFilesShouldSpawn) {
			$this->_spawnFiles();
		}
		
		Garp_Cli::lineOut("\n");
		
		if ($dbShouldSpawn) {
			$this->_spawnDb();
		};
	}

	protected function _spawnDb() {
		$modelSet = $this->getModelSet();

		Garp_Cli::lineOut("\nDatabase");

		$dbManager = Garp_Spawn_MySql_Manager::getInstance();
		$dbManager->run($modelSet);
		
		Garp_Cli::lineOut("\n");

		Garp_Cache_Manager::purge();
		Garp_Cli::lineOut("All cache purged.");
	}
	
	protected function _spawnFiles() {
		$modelSet 		= $this->getModelSet();

		$totalActions 	= $this->_calculateTotalFileActions();
		$jsShouldSpawn	= $this->_shouldSpawnJs();
		$phpShouldSpawn	= $this->_shouldSpawnPhp();

		$progress 		= Garp_Cli_Ui_ProgressBar::getInstance();
		$progress->init($totalActions);
		
		Garp_Cli::lineOut("\nFiles");

		if ($jsShouldSpawn) {
			$progress->display("Cooking up base model goo.");
			$modelSet->materializeCombinedBaseModel();
			$progress->advance();
	
			$progress->display("Including models in model loader");
			$modelSet->includeInJsModelLoader();
			$progress->advance();
		}

		foreach ($modelSet as $model) {
			if ($phpShouldSpawn) {
				$progress->display($model->id . " PHP models, %d to go.");
				$model->materializePhpModels($model);
				$progress->advance();
			}

			if ($jsShouldSpawn) {
				$progress->display($model->id . " extended models, %d to go.");
				$model->materializeExtendedJsModels($modelSet);
				$progress->advance();
			}
		}
		
		$progress->display("√ Done");
	}
	
	protected function _calculateTotalFileActions() {
		$modelSet 			= $this->getModelSet();
		$jsShouldSpawn		= $this->_shouldSpawnJs();
		$phpShouldSpawn		= $this->_shouldSpawnPhp();

		$modelCount 		= count($modelSet);
		$multiplier			= $jsShouldSpawn + $phpShouldSpawn;
		$addition			= $jsShouldSpawn ? 2 : 0;
		
		$totalActions 		= ($modelCount * $multiplier) + $addition;
		
		return $totalActions;
	}

	protected function _shouldSpawnDb() {
		$filter = $this->getModuleFilter();
		
		return 
			$filter !== self::FILTER_MODULE_FILES &&
			$filter !== self::FILTER_MODULE_PHP &&
			$filter !== self::FILTER_MODULE_JS
		;
	}

	protected function _shouldSpawnPhp() {
		$filter = $this->getModuleFilter();

		return 
			$filter !== self::FILTER_MODULE_DB &&
			$filter !== self::FILTER_MODULE_JS
		;
	}

	protected function _shouldSpawnJs() {
		$filter = $this->getModuleFilter();

		return 
			$filter !== self::FILTER_MODULE_DB &&
			$filter !== self::FILTER_MODULE_PHP
		;
	}

	protected function _showJsBaseModel($modelId) {
		$modelSet = $this->getModelSet();
		
		if (array_key_exists($modelId, $modelSet)) {
			$model = $modelSet[$modelId];
			$minBaseModel = $model->renderJsBaseModel($modelSet);
			require_once(APPLICATION_PATH.'/../library/Garp/3rdParty/JsBeautifier/jsbeautifier.php');
			echo js_beautify($minBaseModel) . "\n";
		} else {
			Garp_Cli::errorOut("I don't know the model {$modelId}.");
			Garp_Cli::lineOut("I do know " . implode(", ", array_keys((array)$modelSet)) . '.');
		}
	}
	
	protected function _isFirstArgumentGiven(array $args) {
		$firstArgumentGiven = array_key_exists(0, $args);
		return $firstArgumentGiven;
	}
	
	protected function _isHelpRequested($args) {
		if (!$this->_isFirstArgumentGiven($args)) {
			return false;
		}
			
		$helpWasAsked = strcasecmp($args[0], 'help') === 0;
		return $helpWasAsked;
	}
	
	protected function _validateArguments(array $args) {
		if (!$this->_isFirstArgumentGiven($args)) {
			return;
		}
		
		$error = sprintf(self::ERROR_UNKNOWN_ARGUMENT, $args[0]);
		Garp_Cli::errorOut($error);
		exit;
		
		$this->_validateModuleFilterArgument($args);
	}
	
	/**
	 * 	Check if an allowed '--only=xx' command is called.
	 */
	protected function _validateModuleFilterArgument(array $args) {
		$only 			= self::FILTER_MODULE_COMMAND;
		$allowedFilters = $this->getAllowedFilters();
		
		if (
			array_key_exists($only, $args) &&
			$filter	= strtolower($args[$only]) &&
			!in_array($filter, $allowedFilters)
		) {
			$error = sprintf(self::ERROR_ILLEGAL_MODULE_FILTER, $args[$only]);
			Garp_Cli::errorOut($error);
			exit;
		}
	}

	protected function _displayHelp() {
		$lines = array(
			"\n",
			"• Filtering",
			"garp spawn --only=files",
			"\tOnly Spawn files, skip the database",
			"\n",
			"garp spawn --only=js",
			"\tOnly Spawn Javascript files,",
			"\tskip PHP files and the database",
			"\n",
			"garp spawn --only=php",
			"\tOnly Spawn PHP files,",
			"\tskip Javascript files and the database",
			"\n",
			"garp spawn --only=db",
			"\tOnly Spawn database,",
			"\tskip file generation",
			"\n",
			"\n• Debugging",
			"garp spawn --showJsBaseModel=YourModel",
			"\tShow the non-minified JS base model.",
			"\n",
			"garp spawn --showJsBaseModel=YourModel > YourFile.json",
			"\tWrite the non-minified JS base model to a file."
		);

		$out = implode("\n", $lines);
		Garp_Cli::lineOut($out);
	}
}
