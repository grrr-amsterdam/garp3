<?php
/**
 * Generic extendable singleton class to deal with Cli output and input.
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_Cli_Ui {
	public static function getInstance() {
		static $ui = null;
		if ($ui === null) {
			$uiClass = get_called_class();
			$ui = new $uiClass();
		}
		return $ui;
	}
	

	protected function __construct() {}


	public function display($string) {
	  return Garp_Cli::lineOut($string);
	}


	public function displayError($string) {
		return Garp_Cli::errorOut($string);
	}
}