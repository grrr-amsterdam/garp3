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
	const PATH_CONFIG_APP = '/configs/application.ini';
	
	const RESTORE_FILE = 'tmp_restore.sql';
	
	// const QUERY_DROP = "DROP DATABASE IF EXISTS `%s`";
	//	waarom apart droppen? Zit al in de query.


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
	 * @return String Absolute path to the SQL dump file.
	 */
	public function getRestoreFilePath() {
		$backupDir = $this->getBackupDir();
		return $backupDir . DIRECTORY_SEPARATOR . self::RESTORE_FILE;
	}

	/**
	 * Backs up the database and writes it to a file on the server itself.
	 */
	public function backup() {
		$backupDir 		= $this->getBackupDir();
		$dbConfigParams = $this->getDbConfigParams();
		$environment	= $this->getEnvironment();
		
		$commands = array(
			new Garp_Content_Db_ShellCommand_CreateBackupDir($backupDir),
			new Garp_Content_Db_ShellCommand_DumpToFile($dbConfigParams, $backupDir, $environment)
		);


		/**
		 * @todo: verifiëren of:
		 *			- backupbestand bestaat
		 *			- backupbestand meer dan 0 bytes heeft
		 */

		foreach ($commands as $command) {
			$this->shellExec($command);
		}
	}
	
	/**
	 * Restores a database from a MySQL dump result, executing the contained SQL queries.
	 * @param String $dump The MySQL dump output
	 */
	public function restore($dump) {
		$dump 			= $this->_adjustDumpToEnvironment($dump);
		$dump			= $this->_lowerCaseTableAndViewNames($dump);
		$dbConfig 		= $this->getDbConfigParams();

		$restoreFile 	= $this->getRestoreFilePath();
		if ($this->store($restoreFile, $dump)) {
			$executeFile = new Garp_Content_Db_ShellCommand_ExecuteFile($dbConfig, $restoreFile);
			$this->shellExec($executeFile);
			
			$removeFile = new Garp_Content_Db_ShellCommand_RemoveFile($restoreFile);
			$this->shellExec($removeFile);
		}

		/**
		 * @todo
		 * - verifieer filesize van dump?
		 */
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
	 * Replace the environment values in the given MySQL dump with the environment values for the target.
	 * @param 	String 	&$dump 	Output of MySQL dump.
	 * @return 	String 			The dump output, adjusted with target values instead of source values.
	 */
	protected function _adjustDumpToEnvironment(&$dump) {
		$dbParams = $this->getDbConfigParams();

		$patterns = array(
			'/(USE `)(?P<dbname>[\w-]+)(`;)/',
			'/(CREATE DATABASE [^`]+`)(?P<dbname>[\w-]+)(`)/',
			'/(DEFINER=`)(?P<user>[\w-]+)(`@`)(?P<host>[\w-]+)(`)/'
		);

		$replacements = array(
			"$1{$dbParams->dbname}$3",
			"$1{$dbParams->dbname}$3",
			"$1{$dbParams->username}\${3}{$dbParams->host}$5"
		);

		return preg_replace($patterns, $replacements, $dump);
	}
	
	
	/**
	 * Lowercase the table and view names, for compatibility's sake.
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

}