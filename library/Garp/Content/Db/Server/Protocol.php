<?php
/**
 * Garp_Content_Db_Server_Protocol
 * 
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
interface Garp_Content_Db_Server_Protocol {
	/**
	 * @return String 	Absolute path within the environment to the directory where
	 * 					database backups should be stored.
	 */
	public function getBackupDir();

	/**
	 * @param Garp_Content_Db_ShellCommand_Protocol $command Shell command
	 */
	public function shellExec(Garp_Content_Db_ShellCommand_Protocol $command);

	/**
	 * @param String $command Shell command
	 */
	public function shellExecString($command);
	
	/**
	 * Fetches an SQL dump for structure and content of this database.
	 * @return String The SQL statements, creating structure and importing content.
	 */
	public function fetchDump();
	
	public function backup();

	/**
	 * Restores a database from a MySQL dump result, executing the contained SQL queries.
	 * @param String $dump The MySQL dump output
	 */
	public function restore($dump);
	
	/**
	 * Stores data in a file.
	 * @param String $path Absolute path within the server to a file where the data should be stored.
	 * @param String $data The data to store.
	 * @return Boolean		Success status of the storage process.
	 */
	public function store($path, $data);
}