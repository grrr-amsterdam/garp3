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
	const PATH_CONFIG_APP 			= '/configs/application.ini';
	const RESTORE_FILE 				= 'tmp_restore.sql';
	
	const SQL_USE_STATEMENT			= 'USE `%s`;';
	const SQL_CREATE_DB_STATEMENT	= 'CREATE DATABASE /*!32312 IF NOT EXISTS*/ `%s`';
	const SQL_DEFINER_STATEMENT		= 'DEFINER=`%s`@`%s`';

	/**
	 * @var String $_environment The environment this server runs in.
	 */
	protected $_environment;

	/**
	 * @var String $_environment The environment this server runs against.
	 */	
	protected $_otherEnvironment;

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
	 * @param String $_environment 		The environment this server runs in.
	 * @param String $otherEnvironment 	The environment of the counterpart server
	 * 									(i.e. target if this is source, and vice versa).
	 */
	public function __construct($environment, $otherEnvironment) {
		$this->setEnvironment($environment);
		$this->setOtherEnvironment($otherEnvironment);
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
	
	/**
	 * Retrieves the absolute path to the SQL dump that is to be restored.
	 * @return String Absolute path to the SQL dump file
	 */
	public function getRestoreFilePath() {
		$backupDir = $this->getBackupDir();
		return $backupDir . DIRECTORY_SEPARATOR . self::RESTORE_FILE;
	}
	
	/**
	 * @return String
	 */
	public function getOtherEnvironment() {
		return $this->_otherEnvironment;
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
	 * @param String $otherEnvironment
	 */
	public function setOtherEnvironment($otherEnvironment) {
		$this->_otherEnvironment = $otherEnvironment;
	}
	
	/**
	 * @param String $path
	 */
	public function setBackupDir($path) {
		$this->_backupDir = $path;
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

		$createBackupDir	= new Garp_ShellCommand_CreateDir($backupDir);
		$dumpToFile			= new Garp_ShellCommand_DumpDatabaseToFile($dbConfigParams, $backupDir, $environment);
		
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
		$dbExists 		= new Garp_ShellCommand_DatabaseExists($dbConfigParams);
		return (bool)$this->shellExec($dbExists);
	}
	
	/**
	 * Restores a database from a MySQL dump result, executing the contained SQL queries.
	 * @param String $dump The MySQL dump output
	 */
	public function restore(&$dump) {
		$this->_adjustDumpToEnvironment($dump);
		//$dump			= $this->_lowerCaseTableAndViewNames($dump);
		$this->_removeDefinerCalls($dump);
		$dbConfig 		= $this->getDbConfigParams();
		$restoreFile 	= $this->getRestoreFilePath();
		$restoreDir		= $this->getBackupDir();

		$executeFile 	= new Garp_ShellCommand_CreateDatabase($dbConfig);
		$this->shellExec($executeFile);

		if (!$this->_validateDump($dump)) {
			throw new Exception("The fetched database seems invalid.");
		}
		
		$createRestoreDir = new Garp_ShellCommand_CreateDir($restoreDir);
		$this->shellExec($createRestoreDir);

		if ($this->store($restoreFile, $dump)) {
			$executeFile = new Garp_ShellCommand_ExecuteDatabaseDumpFile($dbConfig, $restoreFile);
			$this->shellExec($executeFile);

			$removeFile = new Garp_ShellCommand_RemoveFile($restoreFile);
			$this->shellExec($removeFile);
		}
	}	

	/**
	 * Fetches an SQL dump for structure and content of this database.
	 * @return String The SQL statements, creating structure and importing content.
	 */
	public function fetchDump() {
		$dumpToString = new Garp_ShellCommand_DumpDatabaseToString($this->getDbConfigParams());
		return $this->shellExec($dumpToString);
	}
	
	/**
	 * @param Garp_ShellCommand_Protocol $command Shell command
	 * @return Void
	 */
	public function shellExec(Garp_ShellCommand_Protocol $command) {
		$command = $this->_addGarp_ShellCommandModulators($command);
		return $this->shellExecString($command->render());
	}
	
	public function _addGarp_ShellCommandModulators(Garp_ShellCommand_Protocol $command) {
		$command = new Garp_ShellCommand_Decorator_Nice($command);

		$ioNiceCommand = new Garp_ShellCommand_IoNiceIsAvailable();
		$ioNiceIsAvailable = (int)$this->shellExecString($ioNiceCommand->render());

		if ($ioNiceIsAvailable) {
			$command = new Garp_ShellCommand_Decorator_IoNice($command);
		}

		return $command;
	}
	
	/**
	 * Replace the environment values in the given MySQL dump with the environment values for the target.
	 * @param 	String 	&$dump 	Output of MySQL dump.
	 * @return 	Void 			The dump input is changed and not returned, for 
	 * 							the sake of memory conservation.
	 */
	protected function _adjustDumpToEnvironment(&$dump) {
		$dbParams 		= $this->getDbConfigParams();
		$thisDbName		= $dbParams->dbname;
		$otherDbParams 	= $this->_fetchOtherDatabaseParams();
		$otherDbName	= $otherDbParams->dbname;

		// Firstly, adjust the USE DATABASE statements.
		$oldUseDbSql 	= sprintf(self::SQL_USE_STATEMENT, $otherDbName);
		$newUseDbSql 	= sprintf(self::SQL_USE_STATEMENT, $thisDbName);
		$dump 			= str_replace($oldUseDbSql, $newUseDbSql, $dump);
		
		// Then adjust the CREATE DATABASE queries.
		$oldCreateDbSql = sprintf(self::SQL_CREATE_DB_STATEMENT, $otherDbName);
		$newCreateDbSql = sprintf(self::SQL_CREATE_DB_STATEMENT, $thisDbName);
		$dump 			= str_replace($oldCreateDbSql, $newCreateDbSql, $dump);

		//	preg_replace seems to be way too demanding for large (180 MB) mysqldump files. Using str_replace now.
	}
	
	/**
	 * Returns the database name of the counterpart server,
	 * i.e. source if this is target, and vice versa.
	 * @return String The other database name
	 */
	protected function _fetchOtherDatabaseParams() {
		$otherEnvironment 	= $this->getOtherEnvironment();
		$appConfigParams 	= $this->_fetchAppConfigParams($otherEnvironment);
		$params 			= $appConfigParams->resources->db->params;
		
		return $params;
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

	protected function _fetchAppConfigParams($environment = null) {
		if (is_null($environment)) {
			$environment = $this->getEnvironment();
		}
		$config = new Zend_Config_Ini(APPLICATION_PATH . self::PATH_CONFIG_APP, $environment);
		return $config;
	}
	
	/**
	 * @param 	String 	$dump 	The MySQL dump output
	 * @return 	Bool			Whether this database dump is valid
	 */
	protected function _validateDump(&$dump) {
		if (strlen($dump) > 0) {
			return true;
		}
		
		return false;
	}
	
	protected function _removeDefinerCalls(&$dump) {
		$otherDbParams 		= $this->_fetchOtherDatabaseParams();
		$otherDbUsername	= $otherDbParams->username;
		$otherDbHost		= $otherDbParams->host;
		
		$oldDefinerString 	= sprintf(self::SQL_DEFINER_STATEMENT, $otherDbUsername, $otherDbHost);
		$dump 				= str_replace($oldDefinerString, '', $dump);

		/*!50013 DEFINER=`garp_remote`@`db.gargamel.nl` SQL SECURITY INVOKER */
		// $pattern 		= '#([/*!\s\d]+DEFINER=`[\w-.]+`@`[\w-.]+`\s*(SQL SECURITY INVOKER)?\s*\*/)#';
		// $replacement 	= '';
		// $dump = preg_replace($pattern, $replacement, $dump);
	}
}

