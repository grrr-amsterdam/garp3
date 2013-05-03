<?php
/**
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Spawn
 */
class Garp_Model_Spawn_MySql_Table_Base extends Garp_Model_Spawn_MySql_Table_Abstract {
	public function create() {
		$outcome = parent::create();
		
		$dbManager = Garp_Model_Spawn_MySql_Manager::getInstance();
		$dbManager->onI18nTableFork($this->getModel());
		
		return $outcome;
	}
}