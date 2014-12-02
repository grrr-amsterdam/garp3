<?php
/**
 * Garp_Shell_Command_PngQuantIsAvailable
 * Checks if the PngQuant command exists, and if permissions allow calling it.
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_Shell_Command_PngQuantIsAvailable extends Garp_Shell_Command_Abstract {
	const COMMAND_PNG_QUANT_IS_AVAILABLE = 'location=`type -P pngquant`; if [ "${location}" ]; then output=`pngquant --version 2> /dev/null`; if [ "${output}" = "" ]; then echo 0; else echo 1; fi; else echo 0; fi';

	public function render() {		
		return self::COMMAND_PNG_QUANT_IS_AVAILABLE;
	}
	
	public function executeLocally() {
		return (bool)parent::executeLocally();
	}
	
	public function executeRemotely(Garp_Shell_RemoteSession $session) {
		return (bool)parent::executeRemotely($session);
	}
}