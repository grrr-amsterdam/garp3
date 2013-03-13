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
		$lcModelId = strtolower($this->_model->id);

		if ($singularRelations = $this->_model->relations->getRelations('type', array('hasOne', 'belongsTo'))) {
			$sql = 
				"DROP VIEW IF EXISTS {$lcModelId}_joint;\n"
				. "CREATE SQL SECURITY INVOKER VIEW {$lcModelId}_joint AS "
				. "SELECT `{$lcModelId}`.*,\n"
			;

			$relNodes = array();
			foreach ($singularRelations as $relName => $rel) {
				$lcRelName		= strtolower($relName);
				$lcRelModelId 	= strtolower($rel->model);
				$modelName 		= 'Model_' . $rel->model;
				$relModel 		= new $modelName;
				$relNodes[] 	= $relModel->getRecordLabelSql($lcRelName) . " AS `{$lcRelName}`";
			}
			$sql .= implode(",\n", $relNodes);
			
			$sql .= "\nFROM `{$lcModelId}`";
			
			foreach ($singularRelations as $relName => $rel) {
				$lcRelName 		= strtolower($relName);
				$lcRelModelId 	= strtolower($rel->model);
				$sql .= "\nLEFT JOIN `{$lcRelModelId}` AS `{$lcRelName}` ON `{$lcModelId}`.`{$rel->column}` = `{$lcRelName}`.`id`";
			}

			return $sql;
		}
	}
}