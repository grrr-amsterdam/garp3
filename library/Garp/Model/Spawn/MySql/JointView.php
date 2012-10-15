<?php
/**
 * A representation of a MySQL view that includes the labels of related hasOne and belongsTo records.
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 * @group Spawn
 */
class Garp_Model_Spawn_MySql_JointView {
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


	protected function _renderSql() {
		if ($singularRelations = $this->_model->relations->getRelations('type', array('hasOne', 'belongsTo'))) {
			$sql = 
				"DROP VIEW IF EXISTS {$this->_model->id}_joint;\n"
				. "CREATE SQL SECURITY INVOKER VIEW {$this->_model->id}_joint AS "
				. "SELECT `{$this->_model->id}`.*,\n"
			;

			$relNodes = array();
			foreach ($singularRelations as $relName => $rel) {
				$modelName = 'Model_' . $rel->model;
				$relModel = new $modelName;
				$relNodes[] = $relModel->getRecordLabelSql($relName) . " AS `{$relName}`";
			}
			$sql .= implode(",\n", $relNodes);
			
			$sql .= "\nFROM `{$this->_model->id}`";
			
			foreach ($singularRelations as $relName => $rel) {
				$sql .= "\nLEFT JOIN `{$rel->model}` AS `{$relName}` ON `{$this->_model->id}`.`{$rel->column}` = `{$relName}`.`id`";
			}

			return $sql;
		}
	}
}