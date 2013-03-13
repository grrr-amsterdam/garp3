<?php
/**
 * Garp_Content_Db_ShellCommand_DumpToString
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_Content_Db_ShellCommand_DumpToString implements Garp_Content_Db_ShellCommand_Protocol {
	const COMMAND_DUMP = "mysqldump -u'%s' -p'%s' --host='%s' --databases %s --add-drop-table --skip-comments --single-transaction --quick";

	/**
	 * @var Zend_Config $_dbConfigParams
	 */
	protected $_dbConfigParams;


	public function __construct(Zend_Config $dbConfigParams) {
		$this->setDbConfigParams($dbConfigParams);
	}

	public function getDbConfigParams() {
		return $this->_dbConfigParams;
	}

	public function setDbConfigParams(Zend_Config $dbConfigParams) {
		$this->_dbConfigParams = $dbConfigParams;
	}

	public function render() {
		$dbConfig = $this->getDbConfigParams();
		
		$dumpCommand = sprintf(
			self::COMMAND_DUMP,
			$dbConfig->username,
			$dbConfig->password,
			$dbConfig->host,
			$dbConfig->dbname
		);

		return $dumpCommand;
	}
	
}