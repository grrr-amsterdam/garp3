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
	
	public function renderSql() {
		$modelId 			= $this->getModelId();
		$sql 				= array();

		$singularRelations 	= $this->_model->relations->getRelations('type', array('hasOne', 'belongsTo'))
		if (!$singularRelations) {
			return;
		}

		$sql[] = $this->_renderDropView();
		$sql[] = 
			"CREATE SQL SECURITY INVOKER VIEW {$modelId}_joint AS "
			. "SELECT `{$modelId}`.*,\n"
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
		
		$sql .= "\nFROM `{$modelId}`";
		
		foreach ($singularRelations as $relName => $rel) {
			$lcRelName 		= strtolower($relName);
			$lcRelModelId 	= strtolower($rel->model);
			$sql .= "\nLEFT JOIN `{$lcRelModelId}` AS `{$lcRelName}` ON `{$modelId}`.`{$rel->column}` = `{$lcRelName}`.`id`";
		}

		return $sql;
	}
}