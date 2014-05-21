<?php
/**
 * Garp_Cli_Command_Spawn_Filter
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class Garp_Cli_Command_Spawn_Filter {
	/**
	 * The argument used to spawn only the database, or only files.
	 */
	const FILTER_MODULE_COMMAND = 'only';
	const FILTER_MODULE_PHP 	= 'php';
	const FILTER_MODULE_DB 		= 'db';
	const FILTER_MODULE_JS 		= 'js';
	const FILTER_MODULE_FILES 	= 'files';

	const ERROR_ILLEGAL_MODULE_FILTER = 
		"Sorry, '%s' is not a valid value for the '--only' parameter. Try 'garp spawn help' for an overview of options.";

	/**
	 * @var Array $_allowedFilters
	 */
	protected $_allowedFilters = array('files', 'db', 'js', 'php');

	/**
 	 * @var Array $_args
 	 */
	protected $_args;


	/**
 	 * @param Array $args Array of commandline arguments provided
 	 */
	public function __construct(Array $args) {
		$this->_validateModuleFilterArgument($args);
		$this->setArgs($args);
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

	public function getArgs() {
		return $this->_args;
	}

	public function setArgs(array $args) {
		$this->_args = $args;
	}

	public function shouldSpawnDb() {
		$filter = $this->_getModuleFilter();
		
		return 
			$filter !== self::FILTER_MODULE_FILES &&
			$filter !== self::FILTER_MODULE_PHP &&
			$filter !== self::FILTER_MODULE_JS
		;
	}

	public function shouldSpawnPhp() {
		$filter = $this->_getModuleFilter();

		return 
			$filter !== self::FILTER_MODULE_DB &&
			$filter !== self::FILTER_MODULE_JS
		;
	}

	public function shouldSpawnJs() {
		$filter = $this->_getModuleFilter();

		return 
			$filter !== self::FILTER_MODULE_DB &&
			$filter !== self::FILTER_MODULE_PHP
		;
	}
	/**
	 * Returns the module that should be run, i.e. 'db' or 'files', or null if no filter is given.
	 */
	protected function _getModuleFilter() {
		$args 	= $this->getArgs();
		$only	= self::FILTER_MODULE_COMMAND;
		
		if (!array_key_exists($only, $args)) {
			return;
		}

		$filter = $args[$only];		
		return strtolower($filter);
	}

	/**
	 * 	Check if an allowed '--only=xx' command is called.
	 */
	protected function _validateModuleFilterArgument(array $args) {
		$only 			= self::FILTER_MODULE_COMMAND;
		$allowedFilters = $this->getAllowedFilters();
		$filter = array_key_exists($only, $args)
			? strtolower($args[$only])
			: null
		;	
		
		if ($filter && !in_array($filter, $allowedFilters)) {
			$error = sprintf(self::ERROR_ILLEGAL_MODULE_FILTER, $args[$only]);
			$error .= "\nTry: " . implode(", ", $this->getAllowedFilters());
			Garp_Cli::errorOut($error);
			exit;
		}
	}
}
