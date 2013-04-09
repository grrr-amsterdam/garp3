<?php
/**
 * Garp_Content_Db_ShellCommand_IoNiceIsAvailable
 * Checks if the ionice command exists, and if permissions allow calling it.
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_Content_Db_ShellCommand_IoNiceIsAvailable implements Garp_Content_Db_ShellCommand_Protocol {
	const COMMAND_IONICE_IS_AVAILABLE = 'location=`type -P ionice`; if [ "${location}" ]; then output=`ionice -c3 2>&1`; if [ "${output}" = "" ]; then echo 1; else echo 0; fi; else echo 0; fi';

	public function render() {		
		return self::COMMAND_IONICE_IS_AVAILABLE;
	}
	
}