<?php
/**
 * Garp_Shell_Command_DumpToString
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_Shell_Command_DumpDatabaseToString extends Garp_Shell_Command_Abstract {
	const COMMAND_DUMP = "mysqldump -u'%s' -p'%s' --host='%s' --databases %s --add-drop-table --skip-comments --single-transaction --quick --routines=0 --triggers=0 --events=0";

	/**
	 * @var Bool $_isThrottled Whether running this command should be throttled on server load.
	 */
	protected $_isThrottled = true;

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