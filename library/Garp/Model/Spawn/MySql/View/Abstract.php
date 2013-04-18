<?php
/**
 * A representation of a MySQL view that includes the labels of related hasOne and belongsTo records.
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 * @group Spawn
 */
abstract class Garp_Model_Spawn_MySql_View_Abstract implements Garp_Model_Spawn_MySql_View_Protocol {
	/** @param Garp_Model_Spawn_Model $_model */
	protected $_model;

	protected $_adapter;


	public function __construct(Garp_Model_Spawn_Model $model) {
		$this->_model = $model;
		$this->_adapter = Zend_Db_Table::getDefaultAdapter();
	}
	

	/**
	 * @return Boolean Result of this creation query.
	 */
	public function create() {
		$sql = $this->_renderSql();
		$statements = explode(";", $sql);
		foreach ($statements as $statement) {
			$this->_adapter->query($statement);
		}
		return true;
	}

	public function getModelId() {
		$lcModelId = strtolower($this->_model->id);
		return $lcModelId;
	}
	
	protected function _renderDropView($viewName) {
		return "DROP VIEW IF EXISTS {$viewName};";
	}
}