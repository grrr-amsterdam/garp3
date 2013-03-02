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
}