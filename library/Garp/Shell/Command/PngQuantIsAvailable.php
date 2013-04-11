<?php
/**
 * Garp_Shell_Command_IoNiceIsAvailable
 * Checks if the ionice command exists, and if permissions allow calling it.
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_Shell_Command_PngQuantIsAvailable extends Garp_Shell_Command_Abstract {
	const COMMAND_PNG_QUANT_IS_AVAILABLE = 'location=`type -P pngquant`; if [ "${location}" ]; then output=`pngquant --version 2> /dev/null`; if [ "${output}" = "" ]; then echo 0; else echo 1; fi; else echo 0; fi';

	public function render() {		
		return self::COMMAND_PNG_QUANT_IS_AVAILABLE;
	}
	
}