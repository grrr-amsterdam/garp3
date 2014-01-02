<?php

system("clear");

define('INDENT', '        ');

function p($msg = '', $indent = true) {
	$msg = Garp_Model_Spawn_Util::addStringColoring($msg);
	echo ($indent ? INDENT : '').$msg."\n";
}


/**
 * Garp_Cli_Command_Spawn
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class Garp_Cli_Command_Spawn extends Garp_Cli_Command {
	/**
	 * Central start method
	 * @return Void
	 */
	public function main(array $args = array()) {
		p("Garp Model Spawner", false);
		p("__________________\n", false);
		p("\033[2;31m\033\★  Firing up...\033[0m\n\n\n", false);


		$models = Garp_Model_Spawn_Models::getInstance();

		p("■ F I L E S", false);
		p();
		foreach ($models as $model) {
			$model->realize();
		}

		p();
		p();
		p("■ D A T A B A S E", false);
		p();
		$dbManager = new Garp_Model_Spawn_MySql_Manager($models);

		p("\n\nDone.", false);
	}
}