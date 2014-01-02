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


	public function __construct($createStatement) {
		if (
			!is_string($createStatement) ||
			substr($createStatement, 0, 6) !== 'CREATE'
		) throw new Exception("The provided argument has to be a MySQL 'CREATE' statement.");

		$this->_adapter = Zend_Db_Table::getDefaultAdapter();
		$this->_createStatement = $createStatement;
		$this->_setPropsByCreateStatement();
	}
	
	
	static public function exists($tableName) {
		$adapter = Zend_Db_Table::getDefaultAdapter();
		$dbConfig = $adapter->getConfig();
		$result = $adapter->fetchAll(
			'SELECT COUNT(*) as existing '
			.'FROM information_schema.tables '
			."WHERE table_schema = '{$dbConfig['dbname']}' "
			."AND table_name = '{$tableName}'"
		);
		
		return (bool)$result[0]['existing'];
	}
	
	
	public function create() {
		$success = false;
		$this->_adapter->query('SET FOREIGN_KEY_CHECKS = 0;');
		$success = $this->_adapter->query($this->_createStatement);
		$this->_adapter->query('SET FOREIGN_KEY_CHECKS = 1;');
		return $success;
	}


	public function compareWithExisting(Garp_Model_Spawn_MySql_Table $existingTable, $isBindingModel = false) {
		$tableInSync = false;
		if ($this == $existingTable) {
			$tableInSync = true;
		} else {
			$columnsInSync = true;
			if ($this->columns != $existingTable->columns) {
				if (!$this->_resolveColumnConflicts($existingTable))
					$columnsInSync = false;
			}

			if (!$isBindingModel) {
				$tableInSync = $columnsInSync && $this->keys->sync($existingTable->keys);
			} else {
				$tableInSync = $columnsInSync;
			}
		}

		p(($tableInSync ? '√ ' : '  ')."The ".$this->name." table ".($tableInSync ? 'is in sync.' : 'remains in conflict.'));
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
		$liveTable = $adapter->fetchAll("SHOW CREATE TABLE {$tableName};");
		return $liveTable[0]['Create Table'].';';
	}

	static public function renderCreateFromSpawnModel(Garp_Model_Spawn_Model $model) {
		$lines = array();
		$fields = $model->fields->getFields();

		foreach ($fields as $field) {
			$type = Garp_Model_Spawn_MySql_Column::getFieldType($field);
			$reqAndDef = Garp_Model_Spawn_MySql_Column::getRequiredAndDefault($field);
			if ($reqAndDef)
				$reqAndDef = ' '.$reqAndDef;
			$autoIncr = $field->name === 'id' ?	' AUTO_INCREMENT' : '';
			$lines[] = "  `{$field->name}` {$type}{$reqAndDef}{$autoIncr}";

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
			$lines[] = "  PRIMARY KEY (`".implode($primKeys, "`,`")."`)";
		}
		foreach ($uniqueKeys as $fieldName) {
			$lines[] = "  UNIQUE KEY `".$fieldName.Garp_Model_Spawn_MySql_UniqueKey::KEY_NAME_POSTFIX."` (`{$fieldName}`)";
		}

		$rels = $model->relations->getRelations();
		foreach ($rels as $rel) {
			if ($rel->isSingular())
				$lines[] = "  KEY `{$rel->column}` (`{$rel->column}`)";
		}
		foreach ($rels as $relName => $rel) {
			if ($rel->isSingular()) {
				$fkName = Garp_Model_Spawn_MySql_ForeignKey::generateForeignKeyName($model->id, $relName);
				$lines[] = "  CONSTRAINT `{$fkName}` FOREIGN KEY (`{$rel->column}`) REFERENCES `{$rel->model}` (`id`)"
							.($rel->type === 'belongsTo' ?
								' ON DELETE CASCADE ON UPDATE CASCADE' :
								' ON DELETE SET NULL ON UPDATE CASCADE'
							);
			}
		}

		$out = "CREATE TABLE `{$model->id}` (\n";
		$out.= implode(",\n", $lines);
		$out.= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

		return $out;
	}
	
	
	static public function renderCreateForBindingModel($modelId1, $modelId2) {
		$bindingModelName = Garp_Model_Spawn_Relations::getBindingModelName($modelId1, $modelId2);
		$tableName = self::getBindingModelTableName($bindingModelName);
		$modelColumn1 = Garp_Model_Spawn_Relations::getRelationColumn($modelId1, $modelId1 === $modelId2 ? 1 : null);
		$modelColumn2 = Garp_Model_Spawn_Relations::getRelationColumn($modelId2, $modelId1 === $modelId2 ? 2 : null);

		$out = "CREATE TABLE `{$tableName}` (\n";
		$out .= "  `{$modelColumn1}` int(11) unsigned NOT NULL,\n";
		$out .= "  `{$modelColumn2}` int(11) unsigned NOT NULL,\n";
		$out .= "  PRIMARY KEY (`{$modelColumn1}`,`{$modelColumn2}`),\n";
		$out .= "  KEY `{$modelColumn1}` (`{$modelColumn1}`),\n";
		$out .= "  KEY `{$modelColumn2}` (`{$modelColumn2}`),\n";
		$out .= "  CONSTRAINT `".uniqid()."` FOREIGN KEY (`{$modelColumn1}`) REFERENCES `{$modelId1}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,\n";
		$out .= "  CONSTRAINT `".uniqid()."` FOREIGN KEY (`{$modelColumn2}`) REFERENCES `{$modelId2}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE\n";
		$out .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		
		return $out;
	}
	
	
	static public function getBindingModelTableName($bindingModelName) {
		return '_'.$bindingModelName;
	}


	protected function _resolveColumnConflicts(Garp_Model_Spawn_MySql_Table $existingTable) {
		$inSync = true;

		foreach ($this->columns as $newColumn) {
			if (!$existingTable->columnExists($newColumn->name)) {
				p("! Column '{$newColumn->name}' does not exist yet.");
				if (Garp_Model_Spawn_Util::confirm("  Would you like to add it to the database?")) {
					$addQuery = "ALTER TABLE `{$this->name}` ADD ".$newColumn->renderSqlDefinition();
					if ($this->_adapter->query($addQuery)) {
						echo "\n";
						p("√ Added the '{$newColumn->name}' column to {$this->name}.");
					} else throw new Exception("Could not add the '{$newColumn->name}' column to the {$this->name} table.");
				} else $inSync = false;
				p();
			} else {
				$existingColumn = $existingTable->getColumn($newColumn->name);
				$diffProperties = $newColumn->getDiffProperties($existingColumn);
				if ($diffProperties) {
					//	______report differences
					p("! Your configured {$this->name}.{$newColumn->name} differs from the existing column.");
					foreach ($diffProperties as $diffProp) {
						echo "\n";
						p("  Difference between new and existing '{$diffProp}' property of {$this->name}.{$newColumn->name}:");
						echo INDENT."  new: ";
						var_dump($newColumn->{$diffProp});
						echo INDENT."  existing: ";
						var_dump($existingColumn->{$diffProp});
						if (
							$diffProp === 'nullable' &&
							$existingColumn->{$diffProp} &&
							!$newColumn->{$diffProp}
						) {
							p("! Warning: Setting a column from non-required to required");
							p("  demands all existing records to have this column filled.");
						}
					}

					p();
					$readableDiffPropsList = Garp_Model_Spawn_Util::humanList($diffProperties, "'");
					if (
						Garp_Model_Spawn_Util::confirm("  Would you like to change the ".$readableDiffPropsList."\n"
						.INDENT."  propert".(count($diffProperties) > 1 ? 'ies' : 'y')." of {$this->name}.{$newColumn->name} in the database?")
					) {
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
						if ($this->_adapter->query($alterQuery)) {
							echo "\n";
							p("√ Modified the ".Garp_Model_Spawn_Util::humanList($diffProperties, "'")." properties of {$this->name}.{$newColumn->name}.\n");
						} else {
							throw new Exception("Could not modify the properties of {$this->name}.{$newColumn->name}\n".$alterQuery."\n");
						}
						$this->_adapter->query('SET FOREIGN_KEY_CHECKS = 1;');
					} else {
						echo "\n";
						p("  Suit yourself. Fifty bucks and I won't tell anyone about {$readableDiffPropsList}.");
						$inSync = false;
					}
				}
			}
		}
		
		return $inSync;
	}


	protected function _setPropsByCreateStatement() {
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

		$this->keys = new Garp_Model_Spawn_MySql_Keys($createStatementLines, $this->name, $this->name);
	}


	static protected function _getModelFromCreateStatement($line) {
		$matches = array();
		preg_match('/CREATE TABLE\s+`(?P<name>\w+)`/i', trim($line), $matches);
		if (!array_key_exists('name', $matches))
			throw new Exception("There was no model name found in the MySQL CREATE statement.");
		return $matches['name'];
	}
}