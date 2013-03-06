<?php
/**
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 */
class Garp_Model_Spawn_MySql_Table {
	/** @var String $name The table name */
	public $name;

	/** @var Array $columns Numeric array of Garp_Model_Spawn_MySql_Column objects */
	public $columns = array();

	/** @var Garp_Model_Spawn_MySql_Keys $keys */
	public $keys;
		
	/** @var String $_createStatement MySQL 'CREATE TABLE' query. */
	protected $_createStatement;
	
	protected $_adapter;



	public function __construct($createStatement, Garp_Model_Spawn_Model $model) {
		if (
			!is_string($createStatement) ||
			substr($createStatement, 0, 6) !== 'CREATE'
		) throw new Exception("The provided argument has to be a MySQL 'CREATE' statement.");

		$this->_adapter = Zend_Db_Table::getDefaultAdapter();
		$this->_createStatement = $createStatement;
		
		// set name, keys and columns
		$this->_setPropsByCreateStatement($model);
	}
	
	
	static public function exists($tableName) {
		$adapter = Zend_Db_Table::getDefaultAdapter();
		$dbConfig = $adapter->getConfig();
		return (bool)$adapter->query(
			'SELECT * '
			.'FROM information_schema.tables '
			."WHERE table_schema = '{$dbConfig['dbname']}' "
			."AND table_name = '{$tableName}'"
		)->fetch();
	}
	
	
	public function create() {
		$success = false;
		$this->_adapter->query('SET FOREIGN_KEY_CHECKS = 0;');
		$success = $this->_adapter->query($this->_createStatement);
		$this->_adapter->query('SET FOREIGN_KEY_CHECKS = 1;');
		return $success;
	}


	/**
	 * Returns this table with another table and try to resolve any conflicts.
	 * @param Garp_Model_Spawn_MySql_Table 	$liveTable
	 * @param Garp_Model_Spawn_Model 		[$bindingModel] 	Provide a binding model if the sync should not handle
	 *															the local base model, but the given model instead.
	 */
	public function syncModel(Garp_Model_Spawn_MySql_Table $liveTable, Garp_Model_Spawn_Model $bindingModel = null) {
		$keysInSync = true;
		$configuredKeys = $bindingModel == null ?
			$this->keys :
			new Garp_Model_Spawn_MySql_Keys(
				explode("\n", $this->_createStatement),
				$liveTable->name,
				$bindingModel
			)
		;
		
		if (!$configuredKeys->removeKeys($liveTable->keys)) {
			$keysInSync = false;
		}
		
		$colsInSync = $this->_syncColumns($liveTable);
		
		if (
			!$configuredKeys->addKeys($liveTable->keys) ||
			!$configuredKeys->modifyKeys($liveTable->keys)
		) {
			$keysInSync = false;
		}
		
		return $colsInSync && $keysInSync;
	}


	protected function _syncColumns(Garp_Model_Spawn_MySql_Table $liveTable) {
		$sync = false;

		if ($this === $liveTable) {
			return true;
		} else {
			$colsInSync = true;
			if ($this->columns != $liveTable->columns) {
				if (!$this->_resolveColumnConflicts($liveTable)) {
					$colsInSync = false;
				}
			}
			return $colsInSync;
		}
	}


	public function columnExists($columnName) {
		foreach ($this->columns as $column) {
			if ($column->name === $columnName)
				return true;
		}
		return false;
	}
	
	
	public function getColumn($columnName) {
		foreach ($this->columns as $column)
			if ($column->name === $columnName)
				return $column;
	}


	static public function renderCreateFromLiveTable($tableName) {
		$adapter = Zend_Db_Table::getDefaultAdapter();
		$liveTable = $adapter->fetchAll("SHOW CREATE TABLE `{$tableName}`;");
		return $liveTable[0]['Create Table'].';';
	}


	static public function renderCreateFromSpawnModel(Garp_Model_Spawn_Model $model) {
		return self::_renderCreateAbstract(
			$model->id, $model->fields->getFields(), $model->relations->getRelations()
		);
	}
	

	static public function renderCreateForBindingModel(Garp_Model_Spawn_Relation $relation) {
		$model = $relation->getBindingModel();

		return self::_renderCreateAbstract(
			self::getBindingModelTableName($model->id),
			$model->fields->getFields(),
			$model->relations->getRelations()
		);
	}
	
	
	static public function getBindingModelTableName($bindingModelName) {
		return '_'.$bindingModelName;
	}


	/**
	 * Abstract method to render a CREATE TABLE statement.
	 * @param String $modelId 	The table name, usually the Model ID.
	 * @param Array $fields 	Numeric array of Garp_Model_Spawn_Field objects.
	 * @param Array $relations 	Associative array, where the key is the name
	 * 							of the relation, and the value a Garp_Model_Spawn_Relation object,
	 * 							or at least an object with properties column, model, type.
	 */
	static protected function _renderCreateAbstract($tableName, array $fields, array $relations) {
		$lines = array();

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


	protected function _resolveColumnConflicts(Garp_Model_Spawn_MySql_Table $liveTable) {
		$inSync = true;

		foreach ($this->columns as $newColumn) {
			if (!$liveTable->columnExists($newColumn->name)) {
				$addQuery = "ALTER TABLE `{$this->name}` ADD ".$newColumn->renderSqlDefinition();

				if (!$this->_adapter->query($addQuery)) {
						throw new Exception("Could not add the '{$newColumn->name}' column to the {$this->name} table.");
				}
			} else {
				$liveColumn = $liveTable->getColumn($newColumn->name);
				$diffProperties = $newColumn->getDiffProperties($liveColumn);

				if ($diffProperties) {
					//	________apply modifications
					$this->_adapter->query('SET FOREIGN_KEY_CHECKS = 0;');
					if (in_array('nullable', $diffProperties)) {
						foreach ($this->keys->foreignKeys as $fk) {
							if ($fk->localColumn === $newColumn->name) {
								Garp_Model_Spawn_MySql_ForeignKey::delete($this->name, $fk);
								$this->keys->droppedForeignKeyNamesDuringColumnSync[] = $fk->name;
								break;
							}
						}
					}
					$alterQuery = "ALTER TABLE `{$this->name}` MODIFY ".$newColumn->renderSqlDefinition();
					if (!$this->_adapter->query($alterQuery)) {
						throw new Exception("Could not modify the properties of {$this->name}.{$newColumn->name}\n".$alterQuery."\n");
					}
					$this->_adapter->query('SET FOREIGN_KEY_CHECKS = 1;');
				}
			}
		}
		
		$this->_deleteRedundantColumns($liveTable);
		
		return $inSync;
	}
	
	
	protected function _deleteRedundantColumns(Garp_Model_Spawn_MySql_Table $liveTable) {
		$progress = Garp_Cli_Ui_ProgressBar::getInstance();

		foreach ($liveTable->columns as $liveCol) {
			if (!$this->columnExists($liveCol->name)) {
				$progress->display("Delete column {$liveTable->name}.{$liveCol->name}? ");
				if (Garp_Model_Spawn_Util::confirm()) {
					$alterQuery = "ALTER TABLE `{$this->name}` DROP COLUMN `{$liveCol->name}`;";
					$this->_adapter->query($alterQuery);
				}
			}
		}
	}
	
	
	protected function _getConfirmationMessage(array $diffProperties, Garp_Model_Spawn_MySql_Column $newColumn) {
		if (
			count($diffProperties) === 1 &&
			$diffProperties[0] === 'nullable'
		) {
			return "Make {$this->name}.{$newColumn->name} " . ($newColumn->nullable ? 'no longer ' : '') . 'required? ';
		} else {
			$readableDiffPropsList = Garp_Model_Spawn_Util::humanList($diffProperties, "'");
			return "Change ".$readableDiffPropsList." of {$this->name}.{$newColumn->name}? ";
		}
	}


	protected function _setPropsByCreateStatement(Garp_Model_Spawn_Model $model) {
		$createStatementLines = explode("\n", $this->_createStatement);
		$createStatementLine = null;
		$columnStatements = array();

		foreach ($createStatementLines as $line) {
			if (Garp_Model_Spawn_MySql_Statement::isColumnStatement($line))
				$this->columns[] = new Garp_Model_Spawn_MySql_Column(count($this->columns), $line);
			elseif (Garp_Model_Spawn_MySql_Statement::isCreateStatement($line))
				$createStatementLine = $line;
		}

		if (
			!$createStatementLine ||
			!count($this->columns)
		) throw new Exception("I need at least a CREATE TABLE statement with a declaration of table columns.");

		$this->name = $this->_getModelFromCreateStatement($createStatementLine);
		$this->keys = new Garp_Model_Spawn_MySql_Keys($createStatementLines, $this->name, $model);
	}


	static protected function _getModelFromCreateStatement($line) {
		$matches = array();
		preg_match('/CREATE TABLE\s+`(?P<name>\w+)`/i', trim($line), $matches);
		if (!array_key_exists('name', $matches))
			throw new Exception("There was no model name found in the MySQL CREATE statement.");
		return $matches['name'];
	}
}
