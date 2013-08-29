<?php
/**
 * Garp_Content_Db_ShellCommand_CreateBackupDir
 * @author David Spreekmeester | Grrr.nl
 */
 class Garp_Content_Db_ShellCommand_CreateBackupDir implements Garp_Content_Db_ShellCommand_Protocol {
 	const COMMAND_CREATE_BACKUP_PATH = "mkdir -p -m 770 %s";	 
	 
	protected $_backupDir;
	 
	 
	public function __construct($backupDir) {
		 $this->setBackupDir($backupDir);
	}
	 
 	public function getBackupDir() {
 		return $this->_backupDir;
 	}

 	public function setBackupDir($backupDir) {
 		$this->_backupDir = $backupDir;
 	}
	 
	public function render() {
 		$backupDir = $this->getBackupDir();
	 	return sprintf(self::COMMAND_CREATE_BACKUP_PATH, $backupDir);
	}
 }