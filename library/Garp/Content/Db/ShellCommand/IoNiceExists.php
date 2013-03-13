<?php
/**
 * Garp_Content_Db_ShellCommand_IoNiceExists
 * Interface to the unix ionice modulator
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_Content_Db_ShellCommand_IoNiceExists implements Garp_Content_Db_ShellCommand_Protocol {
	const COMMAND_IONICE_EXISTS = "type -P ionice";


	public function render() {		
		return self::COMMAND_IONICE_EXISTS;
	}
	
}