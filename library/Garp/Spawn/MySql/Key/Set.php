<?php
class Garp_Spawn_MySql_Key_Set {
	
	/** @var Array $primaryKey Garp_Spawn_MySql_PrimaryKey object */
	public $primaryKey;

	/** @var Array $foreignKeys Numeric array of Garp_Spawn_MySql_ForeignKey objects */
	public $foreignKeys = array();

	/** @var Array $uniqueKeys Numeric array of Garp_Spawn_MySql_UniqueKey objects */
	public $uniqueKeys = array();

	/** @var Array $indices Numeric array of  Garp_Spawn_MySql_Key objects */
	public $indices = array();

	/**
	 * @var String $_tableName
	 */
	protected $_tableName;

	/** @var Garp_Spawn_Model_Abstract $_model */
	protected $_model;
	

	public function __construct(Array $createStatementLines, $tableName, Garp_Spawn_Model_Abstract $model) {
		$this->setTableName($tableName);
		$this->_model = $model;

		foreach ($createStatementLines as $line) {
			if (Garp_Spawn_MySql_ForeignKey::isForeignKeyStatement($line)) {
				$this->foreignKeys[] = new Garp_Spawn_MySql_ForeignKey($line);
			} elseif (Garp_Spawn_MySql_UniqueKey::isUniqueKeyStatement($line)) {
				$this->uniqueKeys[] = new Garp_Spawn_MySql_UniqueKey($line);
			} elseif (Garp_Spawn_MySql_PrimaryKey::isPrimaryKeyStatement($line)) {
				$this->primaryKey = new Garp_Spawn_MySql_PrimaryKey($line);
			}
		}

		//	now retrieve index keys, excluding foreign keys
		foreach ($createStatementLines as $line) {
			 if (Garp_Spawn_MySql_IndexKey::isIndexKeyStatement($line, $this->foreignKeys)) {
				$this->indices[] = new Garp_Spawn_MySql_IndexKey($line);
			}
		}
	}
	
	/**
	 * @return String
	 */
	public function getTableName() {
		return $this->_tableName;
	}
	
	/**
	 * @param String $tableName
	 */
	public function setTableName($tableName) {
		$this->_tableName = strtolower($tableName);
	}
	
}
