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
		$this->setModel($model);
		$this->_adapter = Zend_Db_Table::getDefaultAdapter();
	}
	
	/**
	 * Deletes all views in the database with given postfix.
	 * @param String $postfix The postfix for this type of view, f.i. '_joint'
	 */
	public static function deleteAll($postfix) {
		$adapter 	= Zend_Db_Table::getDefaultAdapter();
		$config 	= Zend_Registry::get('config');
		$dbName 	= $config->resources->db->params->dbname;

		$queryTpl	= "SELECT table_name FROM information_schema.views WHERE table_schema = '%s' and table_name like '%%%s';";
		$statement 	= sprintf($queryTpl, $dbName, $postfix);

		$views = $adapter->fetchAll($statement);
		foreach ($views as $view) {
			$viewName = $view['table_name'];
			$dropStatement = "DROP VIEW IF EXISTS {$viewName};";
			$adapter->query($dropStatement);
		}
	}

	/**
	 * @return Boolean Result of this creation query.
	 */
	public function create() {
		$sql = $this->renderSql();
		
		if (!$sql) {
			return false;
		}
		
		$statements = explode(";", $sql);
		foreach ($statements as $statement) {
			$this->_adapter->query($statement);
		}
		return true;
	}

	public function getModelId() {
		$model = $this->getModel();
		$lcModelId = strtolower($model->id);
		return $lcModelId;
	}
	
	/**
	 * @return String
	 */
	public function getModel() {
		return $this->_model;
	}
	
	/**
	 * @param String $model
	 */
	public function setModel($model) {
		$this->_model = $model;
	}
	
	
	
	protected function _renderDropView($viewName) {
		return "DROP VIEW IF EXISTS {$viewName};";
	}
}