<?php
/**
 * Garp_Spawn_Db_Key_Set_Synchronizer
 * class description
 *
 * @package Garp_Spawn_Db_Key_Set
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Spawn_Db_Key_Set_Synchronizer {
    const ERROR_SET_UNIQUE_NOT_POSSIBLE
        // @codingStandardsIgnoreStart
        = "Could not set column '%s' to unique. Remember: the existing values in this column have to already be unique to be able to do this.";
        // @codingStandardsIgnoreEnd
    const ERROR_UNKNOWN_KEY_TYPE = 'Unknown key type.';
    const QUESTION_MAKE_COLUMN_UNIQUE = "Make %s.%s unique?";

    /**
     * When a relation column's nullable property is changed
     * during column syncing, the accompanying foreign key needs
     * to be dropped from there. Since MySql doesn't support
     * IF EXISTS, key syncing will not attempt to drop this
     * foreign key again.
     *
     * @var array
     */
    public $droppedForeignKeyNamesDuringColumnSync = array();

    protected $_types = array('foreign', 'unique', 'index');

    /**
     * @var Garp_Spawn_Db_Key_Set
     */
    protected $_source;

    /**
     * @var Garp_Spawn_Db_Key_Set
     */
    protected $_target;

    /**
     * @var Garp_Cli_Ui_Protocol $feedback
     */
    protected $_feedback;

    /**
     * @var Garp_Spawn_Model_Abstract
     */
    protected $_model;

    public function __construct(
        Garp_Spawn_Db_Key_Set $source, Garp_Spawn_Db_Key_Set $target,
        Garp_Cli_Ui_Protocol $feedback
    ) {
        $this->setSource($source);
        $this->setTarget($target);
        $this->setFeedback($feedback);
        $this->_model = $source->getModel();
    }

    /**
     * Add non-existing keys in the live database, if these are configured.
     *
     * @return void
     */
    public function addKeys() {
        foreach ($this->_types as $type) {
            $this->_addKeysPerType($type);
        }
    }

    /**
     * Modify keys in the live database, if the configuration differs.
     *
     * @return void
     */
    public function modifyKeys() {
        foreach ($this->_types as $type) {
            $this->_modifyKeysPerType($type);
        }
    }

    /**
     * Remove existing keys in the live database, if these are removed in the configuration.
     *
     * @return void
     */
    public function removeKeys() {
        foreach ($this->_types as $type) {
            $this->_removeKeysPerType($type);
        }
    }

    public function setSource(Garp_Spawn_Db_Key_Set $configKeys) {
        $this->_source = $configKeys;
    }

    public function getSource() {
        return $this->_source;
    }

    public function setTarget(Garp_Spawn_Db_Key_Set $liveKeys) {
        $this->_target = $liveKeys;
    }

    public function getTarget() {
        return $this->_target;
    }

    public function setFeedback(Garp_Cli_Ui_Protocol $feedback) {
        $this->_feedback = $feedback;
    }

    public function getFeedback() {
        return $this->_feedback;
    }

    protected function _addUniqueKeys(array $keysToAdd) {
        $progress  = $this->getFeedback();
        $tableName = $this->getSource()->getTableName();

        foreach ($keysToAdd as $key) {
            $fields = $this->_model->fields->getFields('name', $key->column);
            $field = current($fields);
            $column = is_array($key->column) ?
                implode(', ', $key->column) :
                $key->column;

            if ($progress->isInteractive()) {
                $question = sprintf(self::QUESTION_MAKE_COLUMN_UNIQUE, $this->_model->id, $column);
                $progress->display($question . " ");
                if (!Garp_Spawn_Util::confirm()) {
                    continue;
                }
            }

            if (!Garp_Spawn_Db_UniqueKey::add($tableName, $key)) {
                $error = sprintf(self::ERROR_SET_UNIQUE_NOT_POSSIBLE, $column);
                throw new Exception($error);
            }
        }
    }

    protected function _addForeignKeys(array $keysToAdd) {
        $tableName = $this->getSource()->getTableName();

        foreach ($keysToAdd as $key) {
            $this->_addIndexForForeignKey($key);

            if (!Garp_Spawn_Db_ForeignKey::add($tableName, $key)) {
                throw new Exception("Could not create '{$key->localColumn}' foreign key.");
            }
        }
    }

    protected function _addIndices(array $keysToAdd) {
        $tableName = $this->getSource()->getTableName();

        foreach ($keysToAdd as $key) {
            if (!Garp_Spawn_Db_IndexKey::add($tableName, $key)) {
                throw new Exception("Could not make column '{$key->column}' indexable.");
            }
        }
    }

    protected function _addKeysPerType($keyType) {
        $inSync   = true;
        $liveKeys = $this->getTarget();

        if ($keysToAdd = $this->_getKeysToAdd($keyType, $liveKeys)) {
            switch ($keyType) {
            case 'unique':
                $this->_addUniqueKeys($keysToAdd);
                break;
            case 'foreign':
                $this->_addForeignKeys($keysToAdd);
                break;
            case 'index':
                $this->_addIndices($keysToAdd);
                break;
            default:
                $error = "Syncing {$keyType} keys is not yet supported. ";
                if ($keysToAdd) {
                    $error.= "You're trying to add: ";
                    $keysToAddColumns = array();
                    foreach ($keysToAdd as $key) {
                        $keyToAdd = $keyType === 'primary' ? $key : $key->remoteColumn;
                        $keysToAddColumns[] = $keyToAdd;
                    }
                    $error .= "'" . implode("', '", $keysToAddColumns) . "' as {$keyType} key.";
                }
                throw new Exception($error);
            }
        }
        return $inSync;
    }

    protected function _modifyKeysPerType($keyType) {
        $progress  = $this->getFeedback();
        $inSync    = true;
        $tableName = $this->getSource()->getTableName();
        $liveKeys  = $this->getTarget();

        if ($keysToModify = $this->_getKeysToModify($keyType, $liveKeys)) {
            switch ($keyType) {
            case 'foreign':
                foreach ($keysToModify as $key) {
                    $this->_addIndexForForeignKey($key);

                    $keyIsDropped = in_array(
                        $key->name,
                        $this->droppedForeignKeyNamesDuringColumnSync
                    );
                    $keyAction = $keyIsDropped ?
                        Garp_Spawn_Db_ForeignKey::add($tableName, $key) :
                        Garp_Spawn_Db_ForeignKey::modify($tableName, $key);
                    if (!$keyAction) {
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

    protected function _removeUniqueKeys(array $keysToRemove) {
        $progress  = $this->getFeedback();
        $tableName = $this->getSource()->getTableName();

        foreach ($keysToRemove as $key) {
            $fields = $this->_model->fields->getFields('name', $key->column);
            $field = current($fields);
            $columnsOutput = implode(', ', (array)$key->column);

            if ($progress->isInteractive()) {
                $progress->display("Make {$this->_model->id}.{$columnsOutput} no longer unique? ");
                if (!Garp_Spawn_Util::confirm()) {
                    continue;
                }
            }

            if (!Garp_Spawn_Db_UniqueKey::delete($tableName, $key)) {
                throw new Exception("Could not set column '{$columnsOutput}' to non-unique.");
            }
        }
    }

    protected function _removeForeignKeys(array $keysToRemove) {
        $tableName = $this->getSource()->getTableName();

        foreach ($keysToRemove as $key) {
            if (!(in_array($key->name, $this->droppedForeignKeyNamesDuringColumnSync)
                || Garp_Spawn_Db_ForeignKey::delete($tableName, $key)                )
            ) {
                throw new Exception("Could not delete '{$key->localColumn}' foreign key.");
            }
        }
    }

    protected function _removeIndices(array $keysToRemove) {
        $tableName = $this->getSource()->getTableName();

        foreach ($keysToRemove as $key) {
            if (!Garp_Spawn_Db_IndexKey::delete($tableName, $key)) {
                throw new Exception("Could not set column '{$key->column}' to non-indexable.");
            }
        }
    }

    protected function _removeKeysPerType($keyType) {
        $inSync   = true;
        $liveKeys = $this->getTarget();

        if ($keysToRemove = $this->_getKeysToRemove($keyType, $liveKeys)) {
            switch ($keyType) {
            case 'unique':
                $this->_removeUniqueKeys($keysToRemove);
                break;
            case 'foreign':
                $this->_removeForeignKeys($keysToRemove);
                break;
            case 'index':
                $this->_removeIndices($keysToRemove);
                break;
            default:
                $error = "Syncing {$keyType} keys is not yet supported. ";
                if ($keysToRemove) {
                    $error.= "You're trying to delete: ";
                    $keysToDelColumns = array();
                    foreach ($keysToRemove as $key) {
                        $keyToDel = $keyType === 'primary' ? $key : $key->remoteColumn;
                        $keysToDelColumns[] = $keyToDel;
                    }
                    $error.= "'" . implode("', '", $keysToDelColumns) . "' as {$keyType} key.";
                }
                throw new Exception($error);
            }
        }

        return $inSync;
    }

    protected function _setPrimaryKey() {
        $tableName = $this->getSource()->getTableName();
        $liveKeys = $this->getTarget();

        $livePkPresent = property_exists($liveKeys, 'primaryKey')
            && $liveKeys->primaryKey
            && property_exists($liveKeys->primaryKey, 'columns');

        sort($this->primaryKey->columns);

        if ($livePkPresent) {
            sort($liveKeys->primaryKey->columns);
        }

        if (!$livePkPresent || $this->primaryKey->columns != $liveKeys->primaryKey->columns) {
            if (!Garp_Spawn_Db_PrimaryKey::modify($tableName, $this->primaryKey)) {
                throw new Exception("Could not alter {$tableName}'s primary key.");
            }
        }
    }

    protected function _getKeysToAdd($keyType) {
        $keyTypeVarName = $keyType === 'index' ?
            'indices' :
            (
                $keyType .
                ($keyType === 'primary' ?
                    'Key' : 'Keys'
                )
            )
        ;
        $configuredTypeKeys = (array)$this->getSource()->{$keyTypeVarName};
        $existingTypeKeys   = (array)$this->getTarget()->{$keyTypeVarName};
        $keysToAdd          = array();

        foreach ($configuredTypeKeys as $key) {
            switch ($keyType) {
            case 'foreign':
                $fkExists = false;
                foreach ($existingTypeKeys as $existingTypeKey) {
                    if ($existingTypeKey->name === $key->name) {
                        $fkExists = true;
                    }
                }
                if (!$fkExists) {
                    $keysToAdd[] = $key;
                }
                break;
            case 'unique':
            case 'index':
                if (!in_array($key, $existingTypeKeys)) {
                    $keysToAdd[] = $key;
                }
                break;
            default:
                throw new Exception(self::ERROR_UNKNOWN_KEY_TYPE);
            }
        }

        return $keysToAdd;
    }


    protected function _getKeysToModify($keyType) {
        $keyTypeVarName = $keyType === 'index' ?
            'indices' :
            (
                $keyType .
                ($keyType === 'primary' ?
                    'Key' : 'Keys'
                )
            )
        ;
        $configuredTypeKeys = (array)$this->{$keyTypeVarName};
        $liveKeys           = $this->getTarget();
        $existingTypeKeys   = (array)$liveKeys->{$keyTypeVarName};
        $keysToModify       = array();

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
                throw new Exception(self::ERROR_UNKNOWN_KEY_TYPE);
            }
        }

        return $keysToModify;
    }


    protected function _getKeysToRemove($keyType) {
        $keyTypeVarName = $keyType === 'index' ?
            'indices' :
            $keyType . ($keyType === 'primary' ? 'Key' : 'Keys');

        $configuredTypeKeys = (array)$this->getSource()->{$keyTypeVarName};
        $existingTypeKeys   = (array)$this->getTarget()->{$keyTypeVarName};
        $keysToRemove       = array();

        foreach ($existingTypeKeys as $key) {
            switch ($keyType) {
            case 'foreign':
                if (!$this->_foreignKeyIsConfigured($key->name)) {
                    $keysToRemove[] = $key;
                }
                break;
            case 'unique':
                if (!in_array($key, $configuredTypeKeys)) {
                    $keysToRemove[] = $key;
                }
                break;
            case 'index':
                if (!$this->_foreignKeyIsConfigured($key->name)
                    && !in_array($key, $configuredTypeKeys)
                ) {
                    $keysToRemove[] = $key;
                }
                break;
            default:
                throw new Exception(self::ERROR_UNKNOWN_KEY_TYPE);
            }
        }

        return $keysToRemove;
    }


    protected function _foreignKeyIsConfigured($foreignKeyName) {
        foreach ($this->getSource()->foreignKeys as $fk) {
            if ($fk->name === $foreignKeyName) {
                return true;
            }
        }

        return false;
    }


    protected function _addIndexForForeignKey(Garp_Spawn_Db_ForeignKey $key) {
        $indexKeySql = Garp_Spawn_Db_IndexKey::renderSqlDefinition($key->localColumn);
        $indexKey    = new Garp_Spawn_Db_IndexKey($indexKeySql);
        $tableName   = $this->getSource()->getTableName();

        if (!Garp_Spawn_Db_IndexKey::add($tableName, $indexKey)) {
            throw new Exception("Could not create '{$key->localColumn}' index key.");
        }
    }
}

