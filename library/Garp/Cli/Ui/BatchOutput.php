<?php
/**
 * Provides methods to display progress in a batch (non-interactive) context.
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_Cli_Ui_BatchOutput {
	public function display($string) {
	  return Garp_Cli::lineOut($string);
	}

}
