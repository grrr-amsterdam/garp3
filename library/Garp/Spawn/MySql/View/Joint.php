<?php
/**
 * A representation of a MySQL view that includes the labels of related hasOne and belongsTo records.
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 * @group Spawn
 */
class Garp_Spawn_MySql_View_Joint extends Garp_Spawn_MySql_View_Abstract {
	const POSTFIX = '_joint';
	
	public function getName() {
		return $this->getTableName(false) . self::POSTFIX;
	}
	
	public function getTableName($localized = true) {
		return (!$localized || !$this->getModel()->isMultilingual()) ?
			parent::getTableName() :
			$this->_getTranslatedViewName()
		;
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
	
	protected function _getOtherTableName($modelName) {
		$model			= $this->_getModelFromModelName($modelName);

		if ($model->isMultilingual()) {
			return $this->_getTranslatedViewName($model);
		}

		$factory = new Garp_Spawn_MySql_Table_Factory($model);
		$table = $factory->produceConfigTable();

		return $table->name;
	}
	
	/**
	 * @param	String						$modelName
	 * @return 	Garp_Spawn_Model_Abstract 	$model
	 */
	protected function _getModelFromModelName($modelName) {
		$modelSet = Garp_Spawn_Model_Set::getInstance();
		return $modelSet[$modelName];
	}
	
	protected function _getTranslatedViewName(Garp_Spawn_Model_Abstract $model = null) {
		if (!$model) {
			$model 		= $this->getModel();
		}

		$locale 	= Garp_I18n::getDefaultLocale();
		$i18nView 	= new Garp_Spawn_MySql_View_I18n($model, $locale);
		$viewName 	= $i18nView->getName();
		
		return $viewName;
	}
	
	protected function _renderSelect(array $singularRelations) {
		$model 		= $this->getModel();
		$tableName 	= $this->getTableName();
		
		$select = "SELECT `{$tableName}`.*,\n";

		$relNodes = array();
		foreach ($singularRelations as $relName => $rel) {
			$relNodes[] = $this->_getRecordLabelSqlForRelation($relName, $rel);
		}

		$select .= implode(",\n", $relNodes);
		$select .= "\nFROM `{$tableName}`";
		
		foreach ($singularRelations as $relName => $rel) {
			$lcRelName 		= strtolower($relName);
			$relTableName	= $this->_getOtherTableName($rel->model);
			$select .= "\nLEFT JOIN `{$relTableName}` AS `{$lcRelName}` ON `{$tableName}`.`{$rel->column}` = `{$lcRelName}`.`id`";
		}
		
		return $select;
	}
	
	/**
	 * 
	 */
	protected function _getRecordLabelSqlForRelation($relationName, $relation) {
		$tableAlias = strtolower($relationName);
		$sql = $this->_getRecordLabelSqlForModel($tableAlias, $relation->model) . " AS `{$tableAlias}`";

		return $sql;
	}

	/**
	 * Compose the method to fetch composite columns as a string in a MySql query
	 * to use as a label to identify the record. These have to be columns in the provided table,
	 * to be able to be used flexibly in another query.
	 */
	protected function _getRecordLabelSqlForModel($tableAlias, $modelName) {
		$model = $this->_getModelFromModelName($modelName);

		$tableName 				= $this->_getOtherTableName($modelName);
		$recordLabelFieldDefs 	= $this->_getRecordLabelFieldDefinitions($tableAlias, $model);
		
		$labelColumnsListSql 	= implode(', ', $recordLabelFieldDefs);
		$glue 					= $this->_modelHasFirstAndLastNameListFields($model) ? ' ' : ', ';
		$sql 					= "CONVERT(CONCAT_WS('{$glue}', " . $labelColumnsListSql . ') USING utf8)';
		
		return $sql;
	}
	
	
	/**
	 * @param String $tableAlias 		The alias used to refer to this table, i.e. the relation name
	 * @param Garp_Spawn_Model_Abstract
	 */
	protected function _getRecordLabelFieldDefinitions($tableAlias, Garp_Spawn_Model_Abstract $model) {
		$listFieldNames = $model->fields->listFieldNames;
		$fieldDefs 		= array();

		$self = $this;
		$isSuitable = function($item) use ($self, $model) {
			return $self->isSuitableListFieldName($model, $item);
		};

		$suitableFieldNames = array_filter($listFieldNames, $isSuitable);
		if (!$suitableFieldNames) {
			$suitableFieldNames = array('id');
		}

		$addFieldLabelDefinition 	= $this->_createAddFieldLabelDefinitionFn($tableAlias);
		$fieldDefs 					= array_map($addFieldLabelDefinition, $suitableFieldNames);

		return $fieldDefs;
	}
	
	public function isSuitableListFieldName(Garp_Spawn_Model_Abstract $model, $listFieldName) {	
		try {
			$field = $model->fields->getField($listFieldName);
		} catch (Exception $e) {
			return;
		}

		if ($field && $field->isSuitableAsLabel()) {
			return true;
		}
	}
	
	/**
	 * Creates closure for callback
	 */
	protected function _createAddFieldLabelDefinitionFn($tableAlias) {
		return function ($columnName) use ($tableAlias) {
			return "IF(`{$tableAlias}`.`{$columnName}` <> \"\", `{$tableAlias}`.`{$columnName}`, NULL)";
		};
	}

	protected function _modelHasFirstAndLastNameListFields(Garp_Spawn_Model_Abstract $model = null) {
		if (!$model) {
			$model = $this->getModel();
		}

		try {
			return 
				$model->fields->getField('first_name') &&
				$model->fields->getField('last_name')
			;
		} catch (Exception $e) {}

		return false;
	}
	
}