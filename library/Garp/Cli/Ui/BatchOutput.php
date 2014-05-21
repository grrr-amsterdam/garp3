<?php
/**
 * Provides methods to display progress in a batch (non-interactive) context.
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_Cli_Ui_BatchOutput extends Garp_Cli_Ui {
	public function display($message = null, $itemsLeftMessage = null) {
		echo $message . '. ';
	}

	public function displayHeader($string) {
		echo $string . ': ';
	}

	public function displayError($string) {
		return Garp_Cli::errorOut($string);
	}

}
