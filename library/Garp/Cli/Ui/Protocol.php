<?php
/**
 * Provides methods to display progress in a batch (non-interactive) context.
 * @author David Spreekmeester | Grrr.nl
 */
interface Garp_Cli_Ui_Protocol {

	/**
 	 * Displays a message about the progress, and an optional message about the number of items left.
 	 * @param String $message
 	 * @param String $itemsLeftMessage	Optional message to indicate number of items left.
 	 *									Use '%d' for the number. For instance: "%d items left".
 	 */
	public function display($message = null, $itemsLeftMessage = null);
	
	public function displayError($string);

	public function displayHeader($string);

	public function isInteractive();
}
