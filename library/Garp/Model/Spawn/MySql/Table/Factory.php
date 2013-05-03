<?php
/**
 * Produces a Table instance from a model.
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Spawn
 */
class Garp_Model_Spawn_MySql_Table_Factory {

	/**
	 * Produces a Garp_Model_Spawn_MySql_Table_Abstract instance, based on the spawn configuration.
	 */
	public function produceConfigTable(Garp_Model_Spawn_Model_Abstract $model) {
		$createStatement = $this->_renderCreateFromConfig($model);
		return $this->_produceTable($createStatement, $model);
	}

	/**
	 * Produces a Garp_Model_Spawn_MySql_Table_Abstract instance, based on the live database.
	 */	
	public function produceLiveTable(Garp_Model_Spawn_Model_Abstract $model) {
		$createStatement = $this->_renderCreateFromLive($model);
		return $this->_produceTable($createStatement, $model);
	}

	protected function _produceTable($createStatement, Garp_Model_Spawn_Model_Abstract $model) {
		$tableClass	= $this->_getTableClass($model);
		return new $tableClass($createStatement, $model);
	}

	protected function _renderCreateFromConfig(Garp_Model_Spawn_Model_Abstract $model) {
		$tableName = $this->_getTableName($model);
		
		return $this->_renderCreateAbstract(
			$tableName,
			$model->fields->getFields(),
			$model->relations->getRelations()
		);
	}

	protected function _renderCreateFromLive(Garp_Model_Spawn_Model_Abstract $model) {
		$tableName = $this->_getTableName($model);

		$adapter 	= Zend_Db_Table::getDefaultAdapter();
		$liveTable 	= $adapter->fetchAll("SHOW CREATE TABLE `{$tableName}`;");
		return $liveTable[0]['Create Table'] . ';';
	}

	protected function _getTableName(Garp_Model_Spawn_Model_Abstract $model) {
		switch (get_class($model)) {
			case 'Garp_Model_Spawn_Model_Binding':
				return '_' . strtolower($model->id);
			break;
			default:
				return strtolower($model->id);
		}
	}

	/**
	 * @return 	String 	Class of the table type that is to be returned
	 */
	protected function _getTableClass(Garp_Model_Spawn_Model_Abstract $model) {
		switch (get_class($model)) {
			case 'Garp_Model_Spawn_Model_Binding':
				return 'Garp_Model_Spawn_MySql_Table_Binding';
			break;
			case 'Garp_Model_Spawn_Model_I18n':
				return 'Garp_Model_Spawn_MySql_Table_I18n';
			break;
			case 'Garp_Model_Spawn_Model':
				return 'Garp_Model_Spawn_MySql_Table_Base';
			break;
			default:
				throw new Exception('I do not know which table type should be returned for ' . get_class($model));
		}
	}

	/**
	 * Abstract method to render a CREATE TABLE statement.
	 * @param String $modelId 	The table name, usually the Model ID.
	 * @param Array $fields 	Numeric array of Garp_Model_Spawn_Field objects.
	 * @param Array $relations 	Associative array, where the key is the name
	 * 							of the relation, and the value a Garp_Model_Spawn_Relation object,
	 * 							or at least an object with properties column, model, type.
	 */
	protected function _renderCreateAbstract($tableName, array $fields, array $relations) {
		$lines 		= array();

		foreach ($fields as $field) {
			$lines[] = Garp_Model_Spawn_MySql_Column::renderFieldSql($field);
		}

		$primKeys = array();
		$uniqueKeys = array();

		foreach ($fields as $field) {
			if ($field->primary)
				$primKeys[] = $field->name;
			if ($field->unique)
				$uniqueKeys[] = $field->name;
		}
		if ($primKeys) {
			$lines[] = Garp_Model_Spawn_MySql_PrimaryKey::renderSqlDefinition($primKeys);
		}
		foreach ($uniqueKeys as $fieldName) {
			$lines[] = Garp_Model_Spawn_MySql_UniqueKey::renderSqlDefinition($fieldName);
		}

		foreach ($relations as $rel) {
			if ($rel->type === 'hasOne' || $rel->type === 'belongsTo')
				$lines[] = Garp_Model_Spawn_MySql_IndexKey::renderSqlDefinition($rel->column);
		}

		//	set indices that were configured in the Spawn model config
		foreach ($fields as $field) {
			if ($field->index) {
				$lines[] = Garp_Model_Spawn_MySql_IndexKey::renderSqlDefinition($field->name);
			}
		}

		foreach ($relations as $relName => $rel) {
			if ($rel->type === 'hasOne' || $rel->type === 'belongsTo') {
				$fkName = Garp_Model_Spawn_MySql_ForeignKey::generateForeignKeyName($tableName, $relName);
				$lines[] = Garp_Model_Spawn_MySql_ForeignKey::renderSqlDefinition(
					$fkName, $rel->column, $rel->model, $rel->type
				);
			}
		}

		$out = "CREATE TABLE `{$tableName}` (\n";
		$out.= implode(",\n", $lines);
		$out.= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

		return $out;
	}
	
}