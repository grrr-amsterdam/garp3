<?php
/**
 * @package Garp_Spawn_Db_Key
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Spawn_Db_Key_Set {

    /**
     * @var Garp_Spawn_Db_PrimaryKey object
     */
    public $primaryKey;

    /**
     * Numeric array of Garp_Spawn_Db_ForeignKey objects
     *
     * @var array
     */
    public $foreignKeys = array();

    /**
     * Numeric array of Garp_Spawn_Db_UniqueKey objects
     *
     * @var array
     */
    public $uniqueKeys = array();

    /**
     * Numeric array of  Garp_Spawn_Db_Key objects
     *
     * @var array
     */
    public $indices = array();

    /**
     * @var string $_tableName
     */
    protected $_tableName;

    /**
     * @var Garp_Spawn_Model_Abstract
     */
    protected $_model;


    public function __construct(
        array $createStatementLines,
        $tableName,
        Garp_Spawn_Model_Abstract $model
    ) {
        $this->setTableName($tableName);
        $this->_model = $model;

        foreach ($createStatementLines as $line) {
            if (Garp_Spawn_Db_ForeignKey::isForeignKeyStatement($line)) {
                $this->foreignKeys[] = new Garp_Spawn_Db_ForeignKey($line);
            } elseif (Garp_Spawn_Db_UniqueKey::isUniqueKeyStatement($line)) {
                $this->uniqueKeys[] = new Garp_Spawn_Db_UniqueKey($line);
            } elseif (Garp_Spawn_Db_PrimaryKey::isPrimaryKeyStatement($line)) {
                $this->primaryKey = new Garp_Spawn_Db_PrimaryKey($line);
            }
        }

        //  now retrieve index keys, excluding foreign keys
        foreach ($createStatementLines as $line) {
            if (Garp_Spawn_Db_IndexKey::isIndexKeyStatement($line, $this->foreignKeys)) {
                $this->indices[] = new Garp_Spawn_Db_IndexKey($line);
            }
        }
    }

    /**
     * @return Garp_Spawn_Model_Abstract $_model
     */
    public function getModel() {
        return $this->_model;
    }

    /**
     * @return string
     */
    public function getTableName() {
        return $this->_tableName;
    }

    /**
     * @param string $tableName
     * @return void
     */
    public function setTableName($tableName) {
        $this->_tableName = strtolower($tableName);
    }

}
