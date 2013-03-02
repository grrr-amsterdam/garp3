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


	public function getBackupPath() {
		$backupPath = APPLICATION_PATH . self::PATH_BACKUP;
		return $backupPath;
	}
	
	
	/**
	 * @param String $command Shell command
	 */
	public function shellExec($command) {
		$output = exec($command);
		return $output;
	}
}