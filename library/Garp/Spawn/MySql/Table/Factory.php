<?php
/**
 * Produces a Table instance from a model.
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Spawn
 */
class Garp_Spawn_MySql_Table_Factory {
	/**
	 * @var Garp_Spawn_Model_Abstract $_model
	 */
	protected $_model;
	
	/**	
	 * @param	Garp_Spawn_Model_Abstract	$model
	 */
	public function __construct(Garp_Spawn_Model_Abstract $model) {
		$this->setModel($model);
	}

	/**
	 * Produces a Garp_Spawn_MySql_Table_Abstract instance, based on the spawn configuration.
	 */
	public function produceConfigTable() {
		$model = $this->getModel();
		$createStatement = $this->_renderCreateFromConfig();
		return $this->_produceTable($createStatement);
	}

	/**
	 * Produces a Garp_Spawn_MySql_Table_Abstract instance, based on the live database.
	 */	
	public function produceLiveTable() {
		$model = $this->getModel();
		$createStatement = $this->_renderCreateFromLive();
		return $this->_produceTable($createStatement);
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
		
		return $this;
	}

	protected function _produceTable($createStatement) {
		$model 		= $this->getModel();
		$tableClass	= $this->_getTableClass($model);
		return new $tableClass($createStatement, $model);
	}

	protected function _renderCreateFromConfig() {
		$model 		= $this->getModel();
		$tableName 	= $this->_getTableName();
		$fields 	= $this->_getUnilingualFields();
		
		return $this->_renderCreateAbstract(
			$tableName,
			$fields,
			$model->relations->getRelations(),
			$model->unique
		);
	}

	protected function _getUnilingualFields() {
		$model = $this->getModel();
		
		$fields = $model->isMultilingual() ?
			$fields = $model->fields->getFields('multilingual', false) :
			$model->fields->getFields()
		;
		
		return $fields;
	}
	
	protected function _renderCreateFromLive() {
		$model 		= $this->getModel();
		$tableName 	= $this->_getTableName($model);
		$adapter 	= Zend_Db_Table::getDefaultAdapter();
		$liveTable 	= $adapter->fetchAll("SHOW CREATE TABLE `{$tableName}`;");
		$statement	= $liveTable[0]['Create Table'] . ';';

		return $statement;
	}

	protected function _getTableName() {
		$model = $this->getModel();

		switch (get_class($model)) {
			case 'Garp_Spawn_Model_Binding':
				return '_' . strtolower($model->id);
			break;
			default:
				return strtolower($model->id);
		}
	}

	/**
	 * @return 	String 	Class of the table type that is to be returned
	 */
	protected function _getTableClass() {
		$model = $this->getModel();
		
		switch (get_class($model)) {
			case 'Garp_Spawn_Model_Binding':
				return 'Garp_Spawn_MySql_Table_Binding';
			break;
			case 'Garp_Spawn_Model_I18n':
				return 'Garp_Spawn_MySql_Table_I18n';
			break;
			case 'Garp_Spawn_Model_Base':
				return 'Garp_Spawn_MySql_Table_Base';
			break;
			default:
				throw new Exception('I do not know which table type should be returned for ' . get_class($model));
		}
	}

	/**
	 * Abstract method to render a CREATE TABLE statement.
	 * @param String $modelId 	The table name, usually the Model ID.
	 * @param Array $fields 	Numeric array of Garp_Spawn_Field objects.
	 * @param Array $relations 	Associative array, where the key is the name
	 * 							of the relation, and the value a Garp_Spawn_Relation object,
	 * 							or at least an object with properties column, model, type.
	 * @param Array $unique		(optional) List of column names to be combined into a unique id.
	 *							This is model-wide and supersedes the 'unique' property per field.
	 */
	protected function _renderCreateAbstract($tableName, array $fields, array $relations, $unique) {
		$lines 		= array();

		foreach ($fields as $field) {
			$lines[] = Garp_Spawn_MySql_Column::renderFieldSql($field);
		}

		$primKeys = array();
		$uniqueKeys = array();

		if ($unique) {
			$uniqueKeys[] = $unique;
		}

		foreach ($fields as $field) {
			if ($field->primary)
				$primKeys[] = $field->name;
			if ($field->unique)
				$uniqueKeys[] = $field->name;
		}
		if ($primKeys) {
			$lines[] = Garp_Spawn_MySql_PrimaryKey::renderSqlDefinition($primKeys);
		}
		foreach ($uniqueKeys as $fieldName) {
			$lines[] = Garp_Spawn_MySql_UniqueKey::renderSqlDefinition($fieldName);
		}

		foreach ($relations as $rel) {
			if ($rel->type === 'hasOne' || $rel->type === 'belongsTo')
				$lines[] = Garp_Spawn_MySql_IndexKey::renderSqlDefinition($rel->column);
		}

		//	set indices that were configured in the Spawn model config
		foreach ($fields as $field) {
			if ($field->index) {
				$lines[] = Garp_Spawn_MySql_IndexKey::renderSqlDefinition($field->name);
			}
		}

		foreach ($relations as $relName => $rel) {
			if ($rel->type === 'hasOne' || $rel->type === 'belongsTo') {
				$fkName = Garp_Spawn_MySql_ForeignKey::generateForeignKeyName($tableName, $relName);
				$lines[] = Garp_Spawn_MySql_ForeignKey::renderSqlDefinition(
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
