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


	/****/
	// public function create() {
	// 	Zend_Debug::dump($this->renderSql());
	// 	exit;
	// }	
	/****/
	
	public function getName() {
		return $this->_getTableName() . self::POSTFIX;
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
		$statements[] 	= $this->_renderSelect($singularRelations);

		$sql 			= implode("\n", $statements);
		$sql	 		= $this->_renderCreateView($sql);

		return $sql;
	}
	
	protected function _getRelationTableName($modelName) {
		$model			= $this->_getModelFromModelName($modelName);
		$tableName 		= $model->getName();

		return $tableName;
	}
	
	protected function _getModelFromModelName($modelName) {
		$modelClass 	= 'Model_' . $modelName;
		$model 			= new $modelClass();

		return $model;
	}
	
	protected function _getTranslatedViewName() {
		$model 		= $this->getModel();
		$locale 	= Garp_I18n::getDefaultLocale();
		$i18nView 	= new Garp_Model_Spawn_MySql_View_I18n($model, $locale);
		$viewName 	= $i18nView->getName();
		
		return $viewName;
	}
	
	protected function _renderSelect(array $singularRelations) {
		$model = $this->getModel();
		
		$tableName = $model->isMultilingual() ?
			$this->_getTranslatedViewName() :
			$this->_getTableName()
		;

		$select 			= "SELECT `{$tableName}`.*,\n";

		$relNodes = array();
		foreach ($singularRelations as $relName => $rel) {
			$lcRelName		= strtolower($relName);
			$relModel		= $this->_getModelFromModelName($rel->model);
			$relNodes[] 	= $relModel->getRecordLabelSql($lcRelName) . " AS `{$lcRelName}`";
		}

		$select .= implode(",\n", $relNodes);
		$select .= "\nFROM `{$tableName}`";
		
		foreach ($singularRelations as $relName => $rel) {
			$lcRelName 		= strtolower($relName);
			$relTableName	= $this->_getRelationTableName($rel->model);
			$select .= "\nLEFT JOIN `{$relTableName}` AS `{$lcRelName}` ON `{$tableName}`.`{$rel->column}` = `{$lcRelName}`.`id`";
		}
		
		return $select;
	}
}