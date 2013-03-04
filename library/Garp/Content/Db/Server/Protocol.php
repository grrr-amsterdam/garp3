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
	public function getBackupPath();

	public function shellExec($command);
	
	/**
	 * Fetches an SQL dump for structure and content of this database.
	 * @return String The SQL statements, creating structure and importing content.
	 */
	public function fetchDump();
}