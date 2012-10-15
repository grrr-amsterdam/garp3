<?php
/**
 * Garp_Cli_Command_Db
 * Contains various database related methods.
 *  
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class Garp_Cli_Command_Db extends Garp_Cli_Command {
	/**
 	 * Help
 	 */
	public function help() {
		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut('Show table info:');
		Garp_Cli::lineOut('  g Db info <tablename>');
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('Sync database with database of different environment:');
		Garp_Cli::lineOut('  g Db sync <environment>');
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('Replace a string in the database, across tables and columns:');
		Garp_Cli::lineOut('  g Db replace');
		Garp_Cli::lineOut('');
	}


	/**
	 * Show table info (DESCRIBE query) for given table
	 * @param Array $args
	 * @return Void
	 */
	public function info(array $args = array()) {
		if (empty($args)) {
			Garp_Cli::errorOut('Insufficient arguments');
			Garp_Cli::lineOut('Usage: garp Db info <tablename>');
			return;
		}
		$db = new Zend_Db_Table($args[0]);
		print_r($db->info());
		Garp_Cli::lineOut('');
	}
	
	
	public function sync(array $args = array()) {
		$mem = new Garp_Util_Memory();
		$mem->useHighMemory();

		$sourceEnv = 'production';
		$targetEnv = APPLICATION_ENV;

		if ($args) {
			$requestedEnv = current($args);
			if (in_array($requestedEnv, array('development', 'integration', 'staging', 'production'))) {
				$sourceEnv = $requestedEnv;
			}
		}

		$warnAndDelay = function() {
			$delayInSeconds = 5;
			$clearLine = function() {
				echo "\033[2K";
				echo str_repeat(chr(8), 1000);
			};

			echo "Your local database will be \033[2;31mwiped out!\033[0m\n";

			for ($i = $delayInSeconds; $i > 0; $i--) {
				$clearLine();
				echo "\033[2;32mCTRL-C\033[0m within $i second" . ($i > 1 ? 's':''). " if that scares you.";
				sleep(1);
			}
			$clearLine();
			echo "\n";
		};
		
		$warnAndDelay();
		
		$sourceConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', $sourceEnv);
		$targetConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', $targetEnv);
		$sourceDb = $sourceConfig->resources->db->params;
		$targetDb = $targetConfig->resources->db->params;

		$dumpCommand = "mysqldump -u'{$sourceDb->username}' -p'{$sourceDb->password}' --add-drop-table --host='{$sourceDb->host}' --databases {$sourceDb->dbname}";

		Garp_Cli::lineOut("[Analyzing]");
		Garp_Cli::lineOut("Source environment: " . $sourceEnv);

		if (1 === 1) {
			/**
			* @todo: Ik heb op dit moment geen correcte methode om uit te vinden of er gessh'd moet worden.
			* Je weet niet of twee omgevingen op dezelfde fysieke server staan, of dat je er dan alsnog bij kunt
			* (bv staging en productie). Nu wordt er dus altijd van uit gegaan dat de server remote is.
			*/
			
			$setSSHParam = function($param, $sourceConfig, $sourceEnv) {
				if (isset($sourceConfig->ssh->$param) && $sourceConfig->ssh->$param) {
					return $sourceConfig->ssh->$param;
				} else {
					Garp_Cli::lineOut("Did you know you can set the remote $param as ssh.$param in application.ini?");
					return Garp_Cli::prompt("SSH $param for $sourceEnv:");
				}
			};
			
			$sshHost = $setSSHParam('host', $sourceConfig, $sourceEnv);
			$sshUser = $setSSHParam('user', $sourceConfig, $sourceEnv);

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

			/**	mysql still throws a 'note' about missing databases,
				even when you add 'if exists', so suppress those: */
			$querySuppressingDbIgnorance = function($query) {
				try {
					Zend_Db_Table::getDefaultAdapter()->query($query);
				} catch (Exception $e) {
					if (strpos($e->getMessage(), 'Unknown database') === false) {
						throw $e;
					}
				}				
			};

			Garp_Cli::lineOut("Dropping local database {$targetDb->dbname}"
				. ($targetDb->host !== 'localhost' ? '@ ' . $targetDb->host : '')
			);
			$dropQuery = "DROP DATABASE IF EXISTS `{$targetDb->dbname}`";
			$querySuppressingDbIgnorance($dropQuery);
			
			
			//	___________ Restore the new database
			Garp_Cli::lineOut("\n[Cloning]");
			Garp_Cli::lineOut("Recreating clone from $sourceEnv");

			$tmpName = sys_get_temp_dir() . 'garp-db-import-' . uniqid() . '.sql';
			file_put_contents($tmpName, $dumpResult);
			$restoreCommand = "mysql -u'{$targetDb->username}' -p'{$targetDb->password}' --host='{$sourceDb->host}' < " . $tmpName;
			`$restoreCommand`;
			unlink($tmpName);

			Garp_Cli::lineOut("\nDone.");
		} else throw new Exception("I got no answer back from the remote server. How rude.");	
	}


	/**
	 * Walks over every text column of every record of every table 
	 * and replaces references to $subject with $replacement.
	 * Especially useful since all images in Rich Text Editors are
	 * referenced with absolute paths including the domain. This method
	 * can be used to replace "old domain" with "new domain" in one go.
	 *
	 * @param Array $args
	 * @return Void
	 */
	public function replace(array $args = array()) {
		$subject = !empty($args[0]) ? $args[0] : Garp_Cli::prompt('What is the string you wish to replace?');
		$replacement = !empty($args[1]) ? $args[1] : Garp_Cli::prompt('What is the new string you wish to insert?');
		$subject = trim($subject);
		$replacement = trim($replacement);

		$models = Garp_Content_Api::getAllModels();
		foreach ($models as $model) {
			if (is_subclass_of($model->class, 'Garp_Model_Db')) {
				$this->_replaceString($model->class, $subject, $replacement);
			}
		}
	}


	/**
	 * Replace $subject with $replacement in all textual columns of the table.
	 * @param  String  $modelClass  The model classname
	 * @param  String  $subject	    The string that is to be replaced
	 * @param  String  $replacement The string that will take its place
	 * @return Void
	 */
	protected function _replaceString($modelClass, $subject, $replacement) {
		$model = new $modelClass();
		$columns = $this->_getTextualColumns($model);
		if ($columns) {
			$adapter = $model->getAdapter();
			$updateQuery = 'UPDATE '.$adapter->quoteIdentifier($model->getName()).' SET ';
			foreach ($columns as $i => $column) {
				$updateQuery .= $adapter->quoteIdentifier($column).' = REPLACE(';
				$updateQuery .= $adapter->quoteIdentifier($column).', ';
				$updateQuery .= $adapter->quoteInto('?, ', $subject);
				$updateQuery .= $adapter->quoteInto('?)', $replacement);
				if ($i < (count($columns)-1)) {
					$updateQuery .= ',';
				}
			}
			if ($response = $adapter->query($updateQuery)) {
				$affectedRows = $response->rowCount();
				Garp_Cli::lineOut('Model: '.$model->getName());
				Garp_Cli::lineOut('Affected rows: '.$affectedRows);
				Garp_Cli::lineOut('Involved columns: '.implode(', ', $columns)."\n");
			} else {
				Garp_Cli::errorOut('Error: update for table `'.$model->getName().'` failed.');
			}
		}
	}


	/**
	 * Get all textual columns from a table
	 * @param  Garp_Model_Db  $model  The model
	 * @return Array
	 */
	protected function _getTextualColumns(Garp_Model_Db $model) {
		$columns = $model->info(Zend_Db_Table::METADATA);
		foreach ($columns as $column => $meta) {
			if (!in_array($meta['DATA_TYPE'], array('varchar', 'text', 'mediumtext', 'longtext', 'tinytext'))) {
				unset($columns[$column]);
			}
		}
		return array_keys($columns);
	}
}
