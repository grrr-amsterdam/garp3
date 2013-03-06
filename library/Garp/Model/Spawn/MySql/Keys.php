<?php
class Garp_Model_Spawn_MySql_Keys {
	/** @var Array $primaryKey Garp_Model_Spawn_MySql_PrimaryKey object */
	public $primaryKey;

	/** @var Array $foreignKeys Numeric array of Garp_Model_Spawn_MySql_ForeignKey objects */
	public $foreignKeys = array();

	/** @var Array $uniqueKeys Numeric array of Garp_Model_Spawn_MySql_UniqueKey objects */
	public $uniqueKeys = array();

	/** @var Array $indices Numeric array of  Garp_Model_Spawn_MySql_Key objects */
	public $indices = array();

	/** @var Array $droppedForeignKeyNamesDuringColumnSync When a relation column's nullable property is changed
	* 														during column syncing, the accompanying foreign key needs
	* 														to be dropped from there. Since MySql doesn't support
	* 														IF EXISTS, key syncing will not attempt to drop this
	* 														foreign key again.
	*/
	public $droppedForeignKeyNamesDuringColumnSync = array();


	protected $_tableName;

	/** @var Garp_Model_Spawn_Model $_model */
	protected $_model;
	
	protected $_types = array('foreign', 'unique', 'index');



	public function __construct(Array $createStatementLines, $tableName, Garp_Model_Spawn_Model $model) {
		$this->_tableName = $tableName;
		$this->_model = $model;

		foreach ($createStatementLines as $line) {
			if (Garp_Model_Spawn_MySql_ForeignKey::isForeignKeyStatement($line)) {
				$this->foreignKeys[] = new Garp_Model_Spawn_MySql_ForeignKey($line);
			} elseif (Garp_Model_Spawn_MySql_UniqueKey::isUniqueKeyStatement($line)) {
				$this->uniqueKeys[] = new Garp_Model_Spawn_MySql_UniqueKey($line);
			} elseif (Garp_Model_Spawn_MySql_PrimaryKey::isPrimaryKeyStatement($line)) {
				$this->primaryKey = new Garp_Model_Spawn_MySql_PrimaryKey($line);
			}
		}

		//	now retrieve index keys, excluding foreign keys
		foreach ($createStatementLines as $line) {
			 if (Garp_Model_Spawn_MySql_IndexKey::isIndexKeyStatement($line, $this->foreignKeys)) {
				$this->indices[] = new Garp_Model_Spawn_MySql_IndexKey($line);
			}
		}
	}

	
	/**
	 * Add non-existing keys in the live database, if these are configured.
	 */
	public function addKeys(Garp_Model_Spawn_MySql_Keys $liveKeys) {
		foreach ($this->_types as $type) {
			$this->_addKeysPerType($type, $liveKeys);
		}
	}
	
	
	/**
	 * Modify keys in the live database, if the configuration differs.
	 */
	public function modifyKeys(Garp_Model_Spawn_MySql_Keys $liveKeys) {
		foreach ($this->_types as $type) {
			$this->_modifyKeysPerType($type, $liveKeys);
		}
	}
	

	/**
	 * Remove existing keys in the live database, if these are removed in the configuration.
	 */	
	public function removeKeys(Garp_Model_Spawn_MySql_Keys $liveKeys) {
		foreach ($this->_types as $type) {
			$this->_removeKeysPerType($type, $liveKeys);
		}
	}
	
	
	protected function _addKeysPerType($keyType, Garp_Model_Spawn_MySql_Keys $liveKeys) {
		$progress = Garp_Cli_Ui_ProgressBar::getInstance();
		$inSync = true;

		if ($keysToAdd = $this->_getKeysToAdd($keyType, $liveKeys)) {
			switch ($keyType) {
				case 'unique':
					foreach ($keysToAdd as $key) {
						$fields = $this->_model->fields->getFields('name', $key->column);
						$field = current($fields);
						$progress->display("Make {$this->_model->id}.{$key->column} unique? ");
						if (Garp_Model_Spawn_Util::confirm()) {
							if (!Garp_Model_Spawn_MySql_UniqueKey::add($this->_tableName, $key)) {
								throw new Exception("Could not set column '{$key->column}' to unique. Remember: the existing values in this column have to already be unique to be able to do this.");
							}
						}
					}
				break;
				case 'foreign':
					foreach ($keysToAdd as $key) {
						$this->_addIndexForForeignKey($key);

						if (!Garp_Model_Spawn_MySql_ForeignKey::add($this->_tableName, $key)) {
							throw new Exception("Could not create '{$key->localColumn}' foreign key.");
						}
					}
				break;
				case 'index':
					foreach ($keysToAdd as $key) {
						if (!Garp_Model_Spawn_MySql_IndexKey::add($this->_tableName, $key)) {
							throw new Exception("Could not make column '{$key->column}' indexable.");
						}
					}
				break;
				default:
					$error = "Syncing {$keyType} keys is not yet supported. ";
					if ($keysToAdd) {
						$error.= "You're trying to add: ";
						$keysToAddColumns = array();
						foreach ($keysToAdd as $key) {
							$keysToAddColumns[] = $keyType === 'primary' ? $key : $key->remoteColumn;
						}
						$error.= "'".implode("', '", $keysToAddColumns)."' as {$keyType} key.";
					}
					throw new Exception($error);
			}
		}

		return $inSync;
	}
	

	protected function _modifyKeysPerType($keyType, Garp_Model_Spawn_MySql_Keys $liveKeys) {
		$progress = Garp_Cli_Ui_ProgressBar::getInstance();
		$inSync = true;
		
		if ($keysToModify = $this->_getKeysToModify($keyType, $liveKeys)) {
			switch ($keyType) {
				case 'foreign':
					foreach ($keysToModify as $key) {
						$this->_addIndexForForeignKey($key);

						if (!(
							in_array($key->name, $this->droppedForeignKeyNamesDuringColumnSync) ?
								Garp_Model_Spawn_MySql_ForeignKey::add($this->_tableName, $key) :
								Garp_Model_Spawn_MySql_ForeignKey::modify($this->_tableName, $key)
						)) {
							throw new Exception("Could not modify foreign key '{$key->name}'.");
						}
					}
				break;
				case 'unique':
				case 'index':
				break;
				default:
					throw new Exception("Syncing {$keyType} keys is not yet supported.");
			}
		}

		$this->_setPrimaryKey($liveKeys);

		return $inSync;
	}
	
	
	protected function _removeKeysPerType($keyType, Garp_Model_Spawn_MySql_Keys $liveKeys) {
		$progress = Garp_Cli_Ui_ProgressBar::getInstance();
		$inSync = true;
		
		if ($keysToRemove = $this->_getKeysToRemove($keyType, $liveKeys)) {
			switch ($keyType) {
				case 'unique':
					foreach ($keysToRemove as $key) {
						$fields = $this->_model->fields->getFields('name', $key->column);
						$field = current($fields);

						$progress->display("Make {$this->_model->id}.{$key->column} no longer unique? ");
						if (Garp_Model_Spawn_Util::confirm()) {
							if (!Garp_Model_Spawn_MySql_UniqueKey::delete($this->_tableName, $key)) {
								throw new Exception("Could not set column '{$key->column}' to non-unique.");
							}
						}
					}

				break;
				case 'foreign':
					foreach ($keysToRemove as $key) {
						if (!(
							in_array($key->name, $this->droppedForeignKeyNamesDuringColumnSync) ||
							Garp_Model_Spawn_MySql_ForeignKey::delete($this->_tableName, $key)
						)) {
							throw new Exception("Could not delete '{$key->localColumn}' foreign key.");
						}
					}
				break;
				case 'index':
					foreach ($keysToRemove as $key) {
						if (!Garp_Model_Spawn_MySql_IndexKey::delete($this->_tableName, $key)) {
							throw new Exception("Could not set column '{$key->column}' to non-indexable.");
						}
					}
				break;
				default:
					$error = "Syncing {$keyType} keys is not yet supported. ";
					if ($keysToRemove) {
						$error.= "You're trying to delete: ";
						$keysToDelColumns = array();
						foreach ($keysToRemove as $key) {
							$keysToDelColumns[] = $keyType === 'primary' ? $key : $key->remoteColumn;
						}
						$error.= "'".implode("', '", $keysToDelColumns)."' as {$keyType} key.";
					}
					throw new Exception($error);
			}
		}

		return $inSync;	
	}
	
	
	protected function _setPrimaryKey(Garp_Model_Spawn_MySql_Keys $liveKeys) {
		$livePkPresent = 
			property_exists($liveKeys, 'primaryKey') &&
			$liveKeys->primaryKey &&
			property_exists($liveKeys->primaryKey, 'columns')
		;
		
		sort($this->primaryKey->columns);

		if ($livePkPresent) {
			sort($liveKeys->primaryKey->columns);
		}
		
		if (
			!$livePkPresent ||
			$this->primaryKey->columns != $liveKeys->primaryKey->columns
		) {
			if (!Garp_Model_Spawn_MySql_PrimaryKey::modify($this->_tableName, $this->primaryKey)) {
				throw new Exception("Could not alter {$this->_tableName}'s primary key.");
			}
		}
	}


	protected function _getKeysToAdd($keyType, Garp_Model_Spawn_MySql_Keys $liveKeys) {
		$keyTypeVarName = $keyType === 'index' ?
			'indices' :
			(
				$keyType.
				($keyType === 'primary' ?
					'Key' :	'Keys'
				)
			)
		;
		$configuredTypeKeys = (array)$this->{$keyTypeVarName};
		$existingTypeKeys = (array)$liveKeys->{$keyTypeVarName};
		$keysToAdd = array();

		foreach ($configuredTypeKeys as $key) {
			switch ($keyType) {
				case 'foreign':
					$fkExists = false;
					foreach ($existingTypeKeys as $existingTypeKey) {
						if ($existingTypeKey->name === $key->name) {
							$fkExists = true;
						}
					}
					if (!$fkExists)
						$keysToAdd[] = $key;
				break;
				case 'unique':
				case 'index':
					if (!in_array($key, $existingTypeKeys)) {
						$keysToAdd[] = $key;
					}
				break;
				default:
					throw new Exception('Unknown key type.');
			}
		}

		return $keysToAdd;
	}
	
	
	protected function _getKeysToModify($keyType, Garp_Model_Spawn_MySql_Keys $liveKeys) {
		$keyTypeVarName = $keyType === 'index' ?
			'indices' :
			(
				$keyType.
				($keyType === 'primary' ?
					'Key' :	'Keys'
				)
			)
		;
		$configuredTypeKeys = (array)$this->{$keyTypeVarName};
		$existingTypeKeys = (array)$liveKeys->{$keyTypeVarName};
		$keysToModify = array();

		foreach ($configuredTypeKeys as $key) {
			switch ($keyType) {
				case 'foreign':
					foreach ($existingTypeKeys as $existingTypeKey) {
						if ($existingTypeKey->name === $key->name) {
							if ($existingTypeKey->events !== $key->events) {
								$keysToModify[] = $key;
							}
						}
					}
				break;
				case 'unique':
				case 'index':
				break;
				default:
					throw new Exception('Unknown key type.');
			}
		}

		return $keysToModify;
	}
	
	
	protected function _getKeysToRemove($keyType, Garp_Model_Spawn_MySql_Keys $liveKeys) {
		$keyTypeVarName = $keyType === 'index' ?
			'indices' :
			(
				$keyType.
				($keyType === 'primary' ?
					'Key' :	'Keys'
				)
			)
		;
		$configuredTypeKeys = (array)$this->{$keyTypeVarName};
		$existingTypeKeys = (array)$liveKeys->{$keyTypeVarName};
		$keysToRemove = array();

		foreach ($existingTypeKeys as $key) {
			switch ($keyType) {
				case 'foreign':
					if (!$this->_foreignKeyIsConfigured($key->name))
						$keysToRemove[] = $key;
				break;
				case 'unique':
					if (!in_array($key, $configuredTypeKeys))
						$keysToRemove[] = $key;
				break;
				case 'index':
					if (
						!$this->_foreignKeyIsConfigured($key->name) &&
						!in_array($key, $configuredTypeKeys)
					) {
						$keysToRemove[] = $key;
					}
				break;
				default:
					throw new Exception('Unknown key type.');
			}
		}

		return $keysToRemove;		
	}


	protected function _foreignKeyIsConfigured($foreignKeyName) {
		foreach ($this->foreignKeys as $fk) {
			if ($fk->name === $foreignKeyName) {
				return true;
			}
		}
		
		return false;
	}
	
	
	protected function _addIndexForForeignKey(Garp_Model_Spawn_MySql_ForeignKey $key) {
		$indexKeySql = Garp_Model_Spawn_MySql_IndexKey::renderSqlDefinition($key->localColumn);
		$indexKey = new Garp_Model_Spawn_MySql_IndexKey($indexKeySql);

		if (!Garp_Model_Spawn_MySql_IndexKey::add($this->_tableName, $indexKey)) {
			throw new Exception("Could not create '{$key->localColumn}' index key.");
		}
	}
}