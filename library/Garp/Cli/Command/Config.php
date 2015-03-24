<?php
class Garp_Cli_Command_Config extends Garp_Cli_Command {
	public function get($args) {
		$key = $args[0];
		$conf = Zend_Registry::get('config');
		$bits = explode('.', $key);
		while (isset($bits[0]) && isset($conf->{$bits[0]})) {
			$conf = $conf->{$bits[0]};
			array_shift($bits);
		}
		Garp_Cli::lineOut($conf);
	}
}
