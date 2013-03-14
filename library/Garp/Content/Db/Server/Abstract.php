<?php
/**
 * Garp_Content_Db_Server_Abstract
 * 
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
abstract class Garp_Content_Db_Server_Abstract implements Garp_Content_Db_Server_Protocol {
	const PATH_CONFIG_APP 		= '/configs/application.ini';
	const RESTORE_FILE 			= 'tmp_restore.sql';

	/**
	 * @var String $_environment The environment this server runs in.
	 */
	protected $_environment;

	/**
	 * @var Zend_Config_Ini $_appConfigParams 	Application configuration parameters (application.ini)
	 *											for this particular environment.
	 */
	protected $_appConfigParams;

	/**
	 * @var		String		Absolute path to directory where backup files should be written to.
	 */
	protected $_backupDir;


	/**
	 * @param String $_environment The environment this server runs in.
	 */
	public function __construct($environment) {
		$this->setEnvironment($environment);
		$this->setAppConfigParams($this->_fetchAppConfigParams());
		$this->setBackupDir($this->getBackupDir());
	}
	
	/**
	 * @return String
	 */
	public function getEnvironment() {
		return $this->_environment;
	}
	
	/**
	 * @return Zend_Config_Ini
	 */
	public function getAppConfigParams() {
		return $this->_appConfigParams;
	}

	/**
	 * @return Zend_Config_Ini
	 */
	public function getDbConfigParams() {
		$appConfigParams = $this->getAppConfigParams();
		return $appConfigParams->resources->db->params;
	}
		
	public function setAppConfigParams(Zend_Config_Ini $appConfigParams) {
		$this->_appConfigParams = $appConfigParams;
	}
	
	/**
	 * @param String $environment The id of the environment
	 */
	public function setEnvironment($environment) {
		$this->_environment = $environment;
	}

	/**
	 * @param String $path
	 */
	public function setBackupDir($path) {
		$this->_backupDir = $path;
	}	

	/**
	 * Retrieves the absolute path to the SQL dump that is to be restored.
	 * @return String Absolute path to the SQL dump file
	 */
	public function getRestoreFilePath() {
		$backupDir = $this->getBackupDir();
		return $backupDir . DIRECTORY_SEPARATOR . self::RESTORE_FILE;
	}

	/**
	 * Backs up the database and writes it to a file on the server itself.
	 */
	public function backup() {
		$dbConfigParams 	= $this->getDbConfigParams();
		$backupDir 			= $this->getBackupDir();
		$environment		= $this->getEnvironment();

		if (!$this->databaseExists()) {
			return;
		}

		$createBackupDir	= new Garp_Content_Db_ShellCommand_CreateDir($backupDir);
		$dumpToFile			= new Garp_Content_Db_ShellCommand_DumpToFile($dbConfigParams, $backupDir, $environment);
		
		$this->shellExec($createBackupDir);
		$this->shellExec($dumpToFile);

		/**
		 * @todo: verifiëren of:
		 *			- backupbestand bestaat
		 *			- backupbestand meer dan 0 bytes heeft
		 */
	}
	
	public function databaseExists() {
		$dbConfigParams = $this->getDbConfigParams();
		$dbExists 		= new Garp_Content_Db_ShellCommand_DatabaseExists($dbConfigParams);
		return (bool)$this->shellExec($dbExists);
	}
	
	/**
	 * Restores a database from a MySQL dump result, executing the contained SQL queries.
	 * @param String $dump The MySQL dump output
	 */
	public function restore($dump) {
		$dump 			= $this->_adjustDumpToEnvironment($dump);
		$dump			= $this->_lowerCaseTableAndViewNames($dump);
		$dump			= $this->_removeDefinerCalls($dump);
		$dbConfig 		= $this->getDbConfigParams();
		$restoreFile 	= $this->getRestoreFilePath();
		$restoreDir		= $this->getBackupDir();

		$executeFile 	= new Garp_Content_Db_ShellCommand_CreateDatabase($dbConfig);
		$this->shellExec($executeFile);

		if (!$this->_validateDump($dump)) {
			throw new Exception("The fetched database seems invalid.");
		}
		
		$createRestoreDir = new Garp_Content_Db_ShellCommand_CreateDir($restoreDir);
		$this->shellExec($createRestoreDir);

		if ($this->store($restoreFile, $dump)) {
			$executeFile = new Garp_Content_Db_ShellCommand_ExecuteFile($dbConfig, $restoreFile);
			$this->shellExec($executeFile);

			$removeFile = new Garp_Content_Db_ShellCommand_RemoveFile($restoreFile);
			$this->shellExec($removeFile);
		}
	}	

	/**
	 * Fetches an SQL dump for structure and content of this database.
	 * @return String The SQL statements, creating structure and importing content.
	 */
	public function fetchDump() {
		$dumpToString = new Garp_Content_Db_ShellCommand_DumpToString($this->getDbConfigParams());
		return $this->shellExec($dumpToString);
	}
	
	/**
	 * @param Garp_Content_Db_ShellCommand_Protocol $command Shell command
	 * @return Void
	 */
	public function shellExec(Garp_Content_Db_ShellCommand_Protocol $command) {
		$command = $this->_addShellCommandModulators($command);
		return $this->shellExecString($command->render());
	}
	
	public function _addShellCommandModulators(Garp_Content_Db_ShellCommand_Protocol $command) {
		$command = new Garp_Content_Db_ShellCommand_Decorator_Nice($command);

		$ioNiceCommand = new Garp_Content_Db_ShellCommand_IoNiceIsAvailable();
		$ioNiceIsAvailable = (int)$this->shellExecString($ioNiceCommand->render());

		if ($ioNiceIsAvailable) {
			$command = new Garp_Content_Db_ShellCommand_Decorator_IoNice($command);
		}

		return $command;
	}
	
	/**
	 * Replace the environment values in the given MySQL dump with the environment values for the target.
	 * @param 	String 	&$dump 	Output of MySQL dump.
	 * @return 	String 			The dump output, adjusted with target values instead of source values.
	 */
	protected function _adjustDumpToEnvironment(&$dump) {
		$dbParams = $this->getDbConfigParams();

		$patterns = array(
			'/(USE `)(?P<dbname>[\w-]+)(`;)/',
			'/(CREATE DATABASE [^`]+`)(?P<dbname>[\w-]+)(`)/'
		);

		$replacements = array(
			"$1{$dbParams->dbname}$3",
			"$1{$dbParams->dbname}$3"
		);

		return preg_replace($patterns, $replacements, $dump);
	}
	
	
	/**
	 * Lowercase the table and view names, for compatibility's sake.
	 * Can we deprecate this method already?
	 * @param 	String 	&$dump 	Output of MySQL dump.
	 * @return 	String 			The dump output, with adjusted casing.
	 */
	protected function _lowerCaseTableAndViewNames(&$dump) {
		$configDir 		= APPLICATION_PATH."/modules/default/models/config/";
		$extension 		= 'json';
		$patterns 		= array();
		$replacements 	= array();

		$hardcodedTables = array('AuthFacebook', 'AuthLocal', 'Video');
		foreach ($hardcodedTables as $hardcodedTable) {
			$patterns[] 		= "`{$hardcodedTable}`";
			$replacements[] 	= "`" . strtolower($hardcodedTable) ."`";
		}

		$modelConfig = new Garp_Model_Spawn_Config_Model_Set(
			new Garp_Model_Spawn_Config_Storage_File($configDir, $extension),
			new Garp_Model_Spawn_Config_Format_Json
		);
		$modelSet = new Garp_Model_Spawn_ModelSet($modelConfig);
		
		
		foreach ($modelSet as $model) {
			$lcModel			= strtolower($model->id);
			$patterns[] 		= "`{$model->id}`";
			$replacements[] 	= "`{$lcModel}`";
		
			$relations = $model->relations->getRelations();

			foreach ($relations as $relation) {
				if ($relation->type === 'hasAndBelongsToMany') {
					$bindingModel 	= $relation->getBindingModel();
					$bindingName	= '_' . $bindingModel->id;
					$lcRelation		= strtolower($bindingName);
					$patterns[] 	= "`{$bindingName}`";
					$replacements[] = "`{$lcRelation}`";
				} else {
					$lcRelation		= strtolower($relation->name);
					$patterns[] 	= "`{$relation->name}`";
					$replacements[] = "`{$lcRelation}`";
				}
			}
		}
		
		$dump = str_replace($patterns, $replacements, $dump);
		return $dump;
	}

	protected function _fetchAppConfigParams() {
		$config = new Zend_Config_Ini(APPLICATION_PATH . self::PATH_CONFIG_APP, $this->getEnvironment());
		return $config;
	}
	
	/**
	 * @param 	String 	$dump 	The MySQL dump output
	 * @return 	Bool			Whether this database dump is valid
	 */
	protected function _validateDump($dump) {
		if (strlen($dump) > 0) {
			return true;
		}
		
		return false;
	}
	
	protected function _removeDefinerCalls($dump) {
		/*!50013 DEFINER=`garp_remote`@`db.gargamel.nl` SQL SECURITY INVOKER */
		$pattern 		= '#([/*!\s\d]+DEFINER=`[\w-.]+`@`[\w-.]+`\s*(SQL SECURITY INVOKER)?\s*\*/)#';
		$replacement 	= '';
		return preg_replace($pattern, $replacement, $dump);
	}
}

