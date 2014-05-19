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

	const ERROR_UNKNOWN_ARGUMENT = 
		"Sorry, I do not know the '%s' argument. Try 'garp spawn help' for an overview of options.";	

	/**
	 * @var Garp_Spawn_Model_Set $_modelSet
	 */
	protected $_modelSet;
	
	/**
	 * @var Array $_args The arguments given when calling this command
	 */
	protected $_args;	

	/**
 	 * @var Garp_Cli_Command_Spawn_Filter $_filter
 	 */
	protected $_filter;

	/**
 	 * @var Garp_Cli_Command_Spawn_Feedback $_feedback
 	 */
	protected $_feedback;


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
		$this->setFilter(new Garp_Cli_Command_Spawn_Filter($args));
		$this->setFeedback(new Garp_Cli_Command_Spawn_Feedback($args));
		$this->setModelSet($this->_initModelSet());

		$feedback = $this->getFeedback();
		$feedback->shouldSpawn()
			? $this->_spawn()
			: $this->_showJsBaseModel($args[Garp_Cli_Command_Spawn_Feedback::JS_BASE_MODEL_COMMAND])
		;
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
	
	public function setFilter(Garp_Cli_Command_Spawn_Filter $filter) {
		$this->_filter = $filter;
	}

	/**
 	 * @return Garp_Cli_Command_Spawn_Filter
 	 */
	public function getFilter() {
		return $this->_filter;
	}

	public function setFeedback(Garp_Cli_Command_Spawn_Feedback $feedback) {
		$this->_feedback = $feedback;
	}

	/**
 	 * @return Garp_Cli_Command_Spawn_Feedback
 	 */
	public function getFeedback() {
		return $this->_feedback;
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
		$modelSet = Garp_Spawn_Model_Set::getInstance();

		return $modelSet;
	}

	/**
	 * Spawn JS and PHP files and the database structure.
	 */
	protected function _spawn() {
		$filter = $this->getFilter();

		$dbShouldSpawn  		= $filter->shouldSpawnDb();
		$jsShouldSpawn			= $filter->shouldSpawnJs();
		$phpShouldSpawn			= $filter->shouldSpawnPhp();
		$someFilesShouldSpawn	= $jsShouldSpawn || $phpShouldSpawn;

		if ($someFilesShouldSpawn) {
			$this->_spawnFiles();
		}

		if ($this->getFeedback()->isInteractive()) {
			Garp_Cli::lineOut("\n");
		}
		
		if ($dbShouldSpawn) {
			$this->_spawnDb();
		};
	}

	protected function _spawnDb() {
		$modelSet = $this->getModelSet();

		if ($this->getFeedback()->isInteractive()) {
			Garp_Cli::lineOut("\nDatabase");
		}

		$dbManager = Garp_Spawn_MySql_Manager::getInstance();
		$dbManager->run($modelSet);
		
		if ($this->getFeedback()->isInteractive()) {
			Garp_Cli::lineOut("\n");
		}

		$cacheDir = $this->_getCacheDir();
		Garp_Cache_Manager::purgeStaticCache(null, $cacheDir);
		Garp_Cache_Manager::purgeMemcachedCache(null);

		if ($this->getFeedback()->isInteractive()) {
			Garp_Cli::lineOut("All cache purged.");
		}
	}
	
	protected function _spawnFiles() {
		$modelSet 		= $this->getModelSet();

		$totalActions 	= $this->_calculateTotalFileActions();
		$jsShouldSpawn	= $this->getFilter()->shouldSpawnJs();
		$phpShouldSpawn	= $this->getFilter()->shouldSpawnPhp();

		$progress 		= $this->getFeedback()->isInteractive()
			? Garp_Cli_Ui_ProgressBar::getInstance()
			: Garp_Cli_Ui_BatchOutput::getInstance()
		;

		$progress->init($totalActions);
			
		if ($this->getFeedback()->isInteractive()) {
			Garp_Cli::lineOut("\nFiles");
		}

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
		$jsShouldSpawn		= $this->getFilter()->shouldSpawnJs();
		$phpShouldSpawn		= $this->getFilter()->shouldSpawnPhp();

		$modelCount 		= count($modelSet);
		$multiplier			= $jsShouldSpawn + $phpShouldSpawn;
		$addition			= $jsShouldSpawn ? 2 : 0;
		
		$totalActions 		= ($modelCount * $multiplier) + $addition;
		
		return $totalActions;
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
	}
	

	protected function _displayHelp() {
		$lines = array(
			"",
			"• Batch",
			"garp spawn -b",
			"\tRun in batch mode (non-interactive)",
			"",
			"• Filtering",
			"garp spawn --only=files",
			"\tOnly Spawn files, skip the database",
			"",
			"garp spawn --only=js",
			"\tOnly Spawn Javascript files,",
			"\tskip PHP files and the database",
			"",
			"garp spawn --only=php",
			"\tOnly Spawn PHP files,",
			"\tskip Javascript files and the database",
			"",
			"garp spawn --only=db",
			"\tOnly Spawn database,",
			"\tskip file generation",
			"",
			"• Debugging",
			"garp spawn --showJsBaseModel=YourModel",
			"\tShow the non-minified JS base model.",
			"",
			"garp spawn --showJsBaseModel=YourModel > YourFile.json",
			"\tWrite the non-minified JS base model to a file."
		);

		$out = implode("\n", $lines);
		Garp_Cli::lineOut($out);
	}

	/**
 	 * @return String
 	 */
	protected function _getCacheDir() {
		$app = Zend_Registry::get('application');
		$bootstrap = $app->getBootstrap();
		$cacheDir = false;
		if ($bootstrap && $bootstrap->getResource('cachemanager')) {
			$cacheManager = $bootstrap->getResource('cachemanager');
			$cache = $cacheManager->getCache(Zend_Cache_Manager::PAGECACHE);
			$cacheDir = $cache->getBackend()->getOption('public_dir');
		}
		return $cacheDir;
	}
}
