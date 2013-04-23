<?php
/**
 * A representation of a MySQL view that includes the labels of related hasOne and belongsTo records.
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 * @group Spawn
 */
class Garp_Model_Spawn_MySql_View_Joint extends Garp_Model_Spawn_MySql_View_Abstract {
	const POSTFIX = '_joint';

	
	public function getName() {
		return $this->getModelId() . self::POSTFIX;
	}
	
	public static function deleteAll() {
		parent::deleteAllByPostfix(self::POSTFIX);
	}
	
	public function renderSql() {
		$singularRelations 	= $this->_model->relations->getRelations('type', array('hasOne', 'belongsTo'));
		if (!$singularRelations) {
			return;
		}

		$statements 	= array();
		// $statements[] 	= $this->_renderDropView();
		$statements[] 	= $this->_renderSelect($singularRelations);

		$sql 			= implode("\n", $statements);
		$sql	 		= $this->_renderCreateView($sql);

		return $sql;
	}
	
	protected function _renderSelect(array $singularRelations) {
		$modelId 			= $this->getModelId();
		$select 			= "SELECT `{$modelId}`.*,\n";

		$relNodes = array();
		foreach ($singularRelations as $relName => $rel) {
			$lcRelName		= strtolower($relName);
			$lcRelModelId 	= strtolower($rel->model);
			$modelName 		= 'Model_' . $rel->model;
			$relModel 		= new $modelName;
			$relNodes[] 	= $relModel->getRecordLabelSql($lcRelName) . " AS `{$lcRelName}`";
		}

		$select .= implode(",\n", $relNodes);
		$select .= "\nFROM `{$modelId}`";
		
		foreach ($singularRelations as $relName => $rel) {
			$lcRelName 		= strtolower($relName);
			$lcRelModelId 	= strtolower($rel->model);
			$select .= "\nLEFT JOIN `{$lcRelModelId}` AS `{$lcRelName}` ON `{$modelId}`.`{$rel->column}` = `{$lcRelName}`.`id`";
		}
		
		return $select;
	}
}