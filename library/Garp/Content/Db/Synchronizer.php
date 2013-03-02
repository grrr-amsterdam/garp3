<?php
/**
 * Garp_Db_Synchronizer
 * Contains various database related methods.
 *  
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class Garp_Db_Synchronizer {
	const PATH_CONFIG_APP = '/configs/application.ini';
	const PATH_CONFIG_DEPLOY = '/configs/deploy.rb';

	const COMMAND_DUMP = "mysqldump -u'%s' -p'%s' --add-drop-table --host='%s' --databases %s";
	const COMMAND_RESTORE = "mysql -u'%s' -p'%s' --host='%s' < %s";
	
	const QUERY_DROP = "DROP DATABASE IF EXISTS `%s`";
	
	const MSG_SERVER_NOT_ANSWERING = "I got no answer back from the remote server. How rude.";


	public function __construct($sourceEnv = null, $targetEnv = null) {		
		$mem = new Garp_Util_Memory();
		$mem->useHighMemory();

		$this->_synchronize($sourceEnv, $targetEnv);
	}


	protected function _synchronize($sourceEnv, $targetEnv) {
		$sourceConfig = new Zend_Config_Ini(APPLICATION_PATH . self::PATH_CONFIG_APP, $sourceEnv);
		$targetConfig = new Zend_Config_Ini(APPLICATION_PATH . self::PATH_CONFIG_APP, $targetEnv);
		$sourceDb = $sourceConfig->resources->db->params;
		$targetDb = $targetConfig->resources->db->params;

		$dumpCommand = sprintf(self::COMMAND_DUMP, $sourceDb->username, $sourceDb->password, $sourceDb->host, $sourceDb->dbname);

		Garp_Cli::lineOut("[Analyzing]");
		Garp_Cli::lineOut("Source environment: " . $sourceEnv);

		if (1 === 1) {
			/**
			* @todo: Ik heb op dit moment geen correcte methode om uit te vinden of er gessh'd moet worden.
			* Je weet niet of twee omgevingen op dezelfde fysieke server staan, of dat je er dan alsnog bij kunt
			* (bv staging en productie). Nu wordt er dus altijd van uit gegaan dat de server remote is.
			*/

			$sshHost = $this->_getSSHParam('host', $sourceEnv);
			$sshUser = $this->_getSSHParam('user', $sourceEnv);

			$dumpCommand = "ssh $sshUser@$sshHost " . $dumpCommand;

			Garp_Cli::lineOut("Host: {$sshHost}\nDatabase: {$sourceDb->dbname}");
		} else {
			Garp_Cli::lineOut("Local database: {$sourceDb->dbname}");
		}

		Garp_Cli::lineOut("\n[Connecting]");
		$dumpResult = `$dumpCommand`;

		if ($dumpResult) {
			$dumpResult = str_replace($sourceDb->dbname, $targetDb->dbname, $dumpResult);
			$dumpResult = preg_replace('/(DEFINER=`)\w+(`)/', "$1{$targetDb->username}$2", $dumpResult);

			//	___________ Drop the old database
			Garp_Cli::lineOut("\n[Making room]");

			Garp_Cli::lineOut("Dropping local database {$targetDb->dbname}"
				. ($targetDb->host !== 'localhost' ? '@ ' . $targetDb->host : '')
			);
			$dropQuery = sprintf(self::QUERY_DROP, $targetDb->dbname);
			$this->_querySuppressingDbIgnorance($dropQuery);
			
			
			//	___________ Restore the new database
			Garp_Cli::lineOut("\n[Cloning]");
			Garp_Cli::lineOut("Recreating clone from $sourceEnv");

			$tmpName = sys_get_temp_dir() . 'garp-db-import-' . uniqid() . '.sql';
			file_put_contents($tmpName, $dumpResult);
			$restoreCommand = sprintf(self::COMMAND_RESTORE, $targetDb->username, $targetDb->password, $sourceDb->host, $tmpName);
			`$restoreCommand`;
			unlink($tmpName);

			Garp_Cli::lineOut("\nDone.");
		} else throw new Exception(self::MSG_SERVER_NOT_ANSWERING);
	}
	
	
	/**
	 * Fetches SSH configuration parameters from the app's deployment configuration.
	 */
	protected function _getSSHParam($paramName, $env) {
		$deployConfig = file_get_contents(APPLICATION_PATH . self::PATH_CONFIG_DEPLOY);
		$deployConfigLines = explode("\n", $deployConfig);

		$environmentOffset = null;
		foreach ($deployConfigLines as $lineNumber => $line) {
			if (preg_match("/task\s+:{$env}\s+do/i", $line)) {
				$environmentOffset = $lineNumber;
			}
		}
		
		if (!is_null($environmentOffset)) {
			foreach ($deployConfigLines as $lineNumber => $line) {
				if ($lineNumber > $environmentOffset) {
					switch ($paramName) {
						case 'host':
							if ($value = preg_filter("/server\s+\"(\w.+)\", :app,\s+:web,\s+:db,\s+:primary => true/i", '$1', $line)) {
								return trim($value);
							}
						break;
						case 'user':
							if ($value = preg_filter("/set :user,\s+\"(\w+)\"/i", '$1', $line)) {
								return trim($value);
							}
						break;
						default:
							throw new Exception("I have no clue how to find information on '{$paramName}' in the deploy configuration file.");
					}
				}
			}
		} else throw new Exception("I could not find the deploy configuration for the '{$env}' environment.");
	}
	
	
	/**	mysql still throws a 'note' about missing databases,
		even when you add 'if exists', so suppress those: */
	protected function _querySuppressingDbIgnorance($query) {
		try {
			Zend_Db_Table::getDefaultAdapter()->query($query);
		} catch (Exception $e) {
			if (strpos($e->getMessage(), 'Unknown database') === false) {
				throw $e;
			}
		}				
	}

}