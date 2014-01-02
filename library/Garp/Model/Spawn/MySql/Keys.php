<?php
class Garp_Model_Spawn_MySql_Keys {
	/** @var Array $primaryKey Garp_Model_Spawn_MySql_PrimaryKey object */
	public $primaryKey;

	/** @var Array $foreignKeys Numeric array of Garp_Model_Spawn_MySql_ForeignKey objects */
	public $foreignKeys = array();

	/** @var Array $uniqueKeys Numeric array of Garp_Model_Spawn_MySql_UniqueKey objects */
	public $uniqueKeys = array();

	protected $_tableName;
	
	protected $_modelId;

	/** @var Array $droppedForeignKeyNamesDuringColumnSync When a relation column's nullable property is changed
	* 														during column syncing, the accompanying foreign key needs
	* 														to be dropped from there. Since MySql doesn't support
	* 														IF EXISTS, key syncing will not attempt to drop this
	* 														foreign key again.
	*/
	public $droppedForeignKeyNamesDuringColumnSync = array();


	public function __construct(Array $createStatementLines, $tableName, $modelId) {
		$this->_tableName = $tableName;
		$this->_modelId = $modelId;

		foreach ($createStatementLines as $line) {
			if (Garp_Model_Spawn_MySql_ForeignKey::isForeignKeyStatement($line)) {
				$this->foreignKeys[] = new Garp_Model_Spawn_MySql_ForeignKey($line);
			} elseif (Garp_Model_Spawn_MySql_UniqueKey::isUniqueKeyStatement($line)) {
				$this->uniqueKeys[] = new Garp_Model_Spawn_MySql_UniqueKey($line);
			} elseif (Garp_Model_Spawn_MySql_PrimaryKey::isPrimaryKeyStatement($line)) {
				$this->primaryKeys = new Garp_Model_Spawn_MySql_PrimaryKey($line);
			}
		}
	}


	public function sync(Garp_Model_Spawn_MySql_Keys $existingKeys) {
		$inSync = true;
		$types = array('primary', 'foreign', 'unique');

		foreach ($types as $type) {
			if (!$this->_syncKeysPerType($type, $existingKeys))
				$inSync = false;
		}

		return $inSync;
	}
	
	
	protected function _syncKeysPerType($keyType, Garp_Model_Spawn_MySql_Keys $existingKeys) {
		$inSync = true;

		list($keysToAdd, $keysToDelete, $keysToModify) = $this->_getKeysDiff($keyType, $existingKeys);

		if (
			//	Note! Keys of binding model tables are not synced. You're just supposed to keep your grubby little fingers off them :)
			strpos($this->_modelId, '_') === false &&
			($keysToAdd || $keysToDelete || $keysToModify)
		) {
			$model = new Garp_Model_Spawn_Model($this->_modelId);

			switch ($keyType) {
				case 'unique':
					foreach ($keysToAdd as $key) {
						$fields = $model->fields->getFields('name', $key->column);
						$field = current($fields);

						// if ($field->origin === 'config') {
						p("  Column '{$key->column}' currently does not require unique values.");
						p("! Warning: setting the column to 'unique' requires the values");
						p("  in it to already be unique.");
						if (
							Garp_Model_Spawn_Util::confirm("  Would you like the '{$key->column}' column to accept only\n"
							.INDENT."  unique values from now on?")
						) {
							if (Garp_Model_Spawn_MySql_UniqueKey::add($this->_tableName, $key)) {
								p("√ {$this->_tableName}.{$key->column} is now unique.");
							} else {
								p("! Could not set column '{$key->column}' to unique.");
								$inSync = false;
							}
						}
						// }
					}
					foreach ($keysToDelete as $key) {
						$fields = $model->fields->getFields('name', $key->column);
						$field = current($fields);

						p("  Column '{$key->column}' currently requires unique values.");
						if (
							Garp_Model_Spawn_Util::confirm("! Would you like the '{$key->column}' column to no longer\n"
							.INDENT."  require unique values from now on?")
						) {
							if (Garp_Model_Spawn_MySql_UniqueKey::delete($this->_tableName, $key)) {
								p("√ {$this->_tableName}.{$key->column} is now no longer unique.");
							} else {
								p("! Could not set column '{$key->column}' to non-unique.");
								$inSync = false;
							}
						}
					}

					p();
				break;
				case 'foreign':
					foreach ($keysToAdd as $key) {
						if (Garp_Model_Spawn_Util::confirm("! Would you like to add a foreign key from {$this->_modelId} to {$key->remoteTable}?")) {
							if (Garp_Model_Spawn_MySql_ForeignKey::add($this->_tableName, $key)) {
								p("√ Created {$key->localColumn} foreign key.");
							} else {
								p("! Could not create '{$key->localColumn}' foreign key.");
								$inSync = false;
							}
						} else {
							$inSync = false;
							p('  All right, all right, sheesh! Me and my bright ideas.');
						}
					}
					foreach ($keysToDelete as $key) {
						if (Garp_Model_Spawn_Util::confirm("! Would you like to delete the foreign key from {$this->_modelId} to {$key->remoteTable} ({$key->name})?")) {
							if (
								in_array($key->name, $this->droppedForeignKeyNamesDuringColumnSync) ||
								Garp_Model_Spawn_MySql_ForeignKey::delete($this->_tableName, $key)
							) {
								p("√ Deleted {$key->localColumn} foreign key.");
							} else {
								p("! Could not delete '{$key->localColumn}' foreign key.");
								$inSync = false;
							}
						} else {
							$inSync = false;
							p('  Okay, I\'ll let that cheecky foreign key live just a tiddy bit longer.');
						}
					}
					foreach ($keysToModify as $key) {
						if (
							Garp_Model_Spawn_Util::confirm("! Would you like to set the foreign key events of {$this->_modelId}.{$key->localColumn},\n"
							.INDENT."  referencing {$key->remoteTable}.{$key->remoteColumn}, to '{$key->events}'?")
						) {
							if (
								in_array($key->name, $this->droppedForeignKeyNamesDuringColumnSync) ?
									Garp_Model_Spawn_MySql_ForeignKey::add($this->_tableName, $key) :
									Garp_Model_Spawn_MySql_ForeignKey::modify($this->_tableName, $key)
							) {
								p("√ Modified foreign key '{$key->name}'.");
							} else {
								p("! Could not modify foreign key '{$key->name}'.");
								$inSync = false;
							}
						} else {
							$inSync = false;
							p('  Minding my own business...');
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
					if ($keysToDelete) {
						$error.= "You're trying to delete: ";
						$keysToDelColumns = array();
						foreach ($keysToDelete as $key) {
							$keysToDelColumns[] = $keyType === 'primary' ? $key : $key->remoteColumn;
						}
						$error.= "'".implode("', '", $keysToDelColumns)."' as {$keyType} key.";
					}
					throw new Exception($error);
			}
		}

		return $inSync;
	}


	protected function _getKeysDiff($keyType, Garp_Model_Spawn_MySql_Keys $existingKeys) {
		$keyTypeVarName = $keyType.($keyType === 'primary' ? 'Key' : 'Keys');
		$configuredTypeKeys = (array)$this->{$keyTypeVarName};
		$existingTypeKeys = (array)$existingKeys->{$keyTypeVarName};
		$keysToAdd = array();
		$keysToDelete = array();
		$keysToModify = array();

		foreach ($configuredTypeKeys as $key) {
			switch ($keyType) {
				case 'foreign':
					$fkExists = false;
					foreach ($existingTypeKeys as $existingTypeKey) {
						if ($existingTypeKey->name === $key->name) {
							$fkExists = true;
							if ($existingTypeKey->events !== $key->events) {
								$keysToModify[] = $key;
							}
						}
					}
					if (!$fkExists)
						$keysToAdd[] = $key;
				break;
				case 'unique':
					if (!in_array($key, $existingTypeKeys))
						$keysToAdd[] = $key;
				break;
				case 'primary':
					if ($key !== $existingTypeKeys) {
						$keysToModify[] = $key;
					}
				break;
			}
		}
		foreach ($existingTypeKeys as $key) {
			switch ($keyType) {
				case 'foreign':
					$fkExists = false;
					foreach ($configuredTypeKeys as $configuredTypeKey) {
						if ($configuredTypeKey->name === $key->name) {
							$fkExists = true;
						}
					}
					if (!$fkExists)
						$keysToDelete[] = $key;
				break;
				case 'unique':
					if (!in_array($key, $configuredTypeKeys))
						$keysToDelete[] = $key;
				break;
			}
		}

		return array($keysToAdd, $keysToDelete, $keysToModify);
	}
}