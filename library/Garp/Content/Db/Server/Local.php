<?php
/**
 * Garp_Content_Db_Server_Local
 * 
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Garp_Content_Db_Server_Local extends Garp_Content_Db_Server_Abstract {
	const PATH_BACKUP = '/data/sql';


	public function getBackupDir() {
		$backupPath = APPLICATION_PATH . self::PATH_BACKUP;
		return $backupPath;
	}
	
	/**
	 * @param Garp_Content_Db_ShellCommand_Protocol $command Shell command
	 */
	public function shellExec(Garp_Content_Db_ShellCommand_Protocol $command) {
		$output = null;
		exec($command->render(), $output);
		$output = implode("\n", $output);
		return $output;
	}

	/**
	 * Stores data in a file.
	 * @param String $path 	Absolute path within the server to a file where the data should be stored.
	 * @param String $data 	The data to store.
	 * @return Boolean		Success status of the storage process.
	 */
	public function store($path, $data) {
		if (false === file_put_contents($path, $data)) {
			throw new Exception("Could not store data at {$path}");
		}
		
		return true;
	}		
}