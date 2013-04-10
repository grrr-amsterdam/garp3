<?php
/**
 * Garp_ShellCommand_ExecuteFile
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_ShellCommand_ExecuteDatabaseDumpFile implements Garp_ShellCommand_Protocol {
	const COMMAND_QUERY = "mysql -u'%s' -p'%s' --host='%s' '%s' < '%s'";

	/**
	 * @var Zend_Config $_dbConfigParams
	 */
	protected $_dbConfigParams;
	
	/**
	 * @var String $_file
	 */
	protected $_file;
		
	
	public function __construct(Zend_Config $dbConfigParams, $file) {
		$this->setDbConfigParams($dbConfigParams);
		$this->setFile($file);
	}

	public function getDbConfigParams() {
		return $this->_dbConfigParams;
	}

	public function setDbConfigParams(Zend_Config $dbConfigParams) {
		$this->_dbConfigParams = $dbConfigParams;
	}

	/**
	 * @return String
	 */
	public function getFile() {
		return $this->_file;
	}
	
	/**
	 * @param String $file
	 */
	public function setFile($file) {
		$this->_file = $file;
	}

	public function render() {
		$dbConfig 	= $this->getDbConfigParams();
		$file 		= $this->getFile();

		$dumpCommand = sprintf(
			self::COMMAND_QUERY,
			$dbConfig->username,
			$dbConfig->password,
			$dbConfig->host,
			$dbConfig->dbname,
			$file
		);
		
		return $dumpCommand;
	}
	
}