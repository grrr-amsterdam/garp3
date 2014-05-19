<?php
/**
 * Provides methods to display progress in a batch (non-interactive) context.
 * @author David Spreekmeester | Grrr.nl
 */
interface Garp_Cli_Ui_Protocol {

	public function init($totalValue);

	public function advance($newValue = null);
	    
	public function display($message = null);
	
	public function displayError($string);
}
