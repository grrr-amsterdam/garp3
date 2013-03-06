<?php
/**
 * Garp_Content_Db_ShellCommand_DumpToString
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_Content_Db_ShellCommand_DumpToFile extends Garp_Content_Db_ShellCommand_DumpToString implements Garp_Content_Db_ShellCommand_Protocol {
	/**
	 * @var String $_backupDir
	 */
	protected $_backupDir;

	/**
	 * @var String $_environment
	 */
	protected $_environment;
			


	public function __construct(Zend_Config $dbConfigParams, $backupDir, $environment) {
		parent::__construct($dbConfigParams);

		$this->setBackupDir($backupDir);
		$this->setEnvironment($environment);
	}

	public function getBackupDir() {
		return $this->_backupDir;
	}

	public function setBackupDir($backupDir) {
		$this->_backupDir = $backupDir;
	}

	public function getEnvironment() {
		return $this->_environment;
	}
	
	public function setEnvironment($environment) {
		$this->_environment = $environment;
	}	

	public function render() {
		$dumpToString		= parent::render();

		$dbName 			= $this->getDbConfigParams()->dbname;
		$environment		= $this->getEnvironment();
		$date				= date('Y-m-d-His');
		$backupDir			= $this->getBackupDir();

		$shellCommand		= $dumpToString . ' > ' . $backupDir . '/'
							. $dbName . '-' . $environment . '-' . $date . '.sql';

		return $shellCommand;
	}
	
}