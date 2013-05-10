<?php
/**
 * Generated PHP model
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Spawn
 */
abstract class Garp_Spawn_Php_Model_Abstract implements Garp_Spawn_Php_Model_Protocol {
	/**
	 * @var Garp_Spawn_Model_Abstract $_model
	 */
	protected $_model;

	
	public function __construct(Garp_Spawn_Model_Abstract $model) {
		$this->setModel($model);
	}

	/**
	 * @return Garp_Spawn_Model_Abstract
	 */
	public function getModel() {
		return $this->_model;
	}
	
	/**
	 * @param Garp_Spawn_Model_Abstract $model
	 */
	public function setModel($model) {
		$this->_model = $model;
	}
	
	public function getTableName() {
		$model 			= $this->getModel();
		$tableFactory 	= new Garp_Spawn_MySql_Table_Factory($model);
		$table 			= $tableFactory->produceConfigTable();
		
		return $table->name;
	}
	
	/**
	 * Render line with tabs and newlines
	 */
	protected function _rl($content, $tabs = 0, $newlines = 1) {
		return str_repeat("\t", $tabs).$content.str_repeat("\n", $newlines);
	}
}