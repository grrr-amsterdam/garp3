<?php
use Garp_Spawn_Db_Schema_Interface as SchemaInterface;

/**
 * @package Garp_Spawn_Db_Table
 * @author  David Spreekmeester <david@grrr.nl>
 */
abstract class Garp_Spawn_Db_Table_Abstract {
    /**
     * The table name
     *
     * @var string
     */
    public $name;

    /**
     * Numeric array of Garp_Spawn_Db_Column objects
     *
     * @var array
     */
    public $columns = array();

    /**
     * @var Garp_Spawn_Db_Keys
     */
    public $keys;

    /**
     * MySQL 'CREATE TABLE' query.
     *
     * @var string
     */
    protected $_createStatement;

    /**
     * @var Garp_Spawn_Db_Schema_Interface
     */
    protected $_schema;

    /**
     * @var Garp_Spawn_Model_Abstract
     */
    protected $_model;

    /**
     * @param  string                         $createStatement
     * @param  Garp_Spawn_Db_Schema_Interface $schema
     * @param  Garp_Spawn_Model_Abstract      $model
     * @return void
     */
    public function __construct(
        string $createStatement,
        SchemaInterface $schema,
        Garp_Spawn_Model_Abstract $model
    ) {
        $this->_schema = $schema;
        $this->setModel($model);

        $this->_validateCreateStatement($createStatement);
        $this->setCreateStatement($createStatement);

        // set name, keys and columns
        $this->_setPropsByCreateStatement($model);
    }

    /**
     * @return Garp_Spawn_Model_Abstract
     */
    public function getModel() {
        return $this->_model;
    }

    /**
     * @param Garp_Spawn_Model_Abstract $model
     * @return void
     */
    public function setModel($model) {
        $this->_model = $model;
    }

    /**
     * @return string
     */
    public function getCreateStatement() {
        return $this->_createStatement;
    }

    /**
     * Set MySQL CREATE statement
     *
     * @param  string $createStatement
     * @return void
     */
    public function setCreateStatement(string $createStatement) {
        $this->_createStatement = $createStatement;
    }

    /**
     * Check if the table exists
     *
     * @param  Garp_Spawn_Db_Schema_Interface $schema
     * @param  string $tableName
     * @return bool
     */
    static public function exists(Garp_Spawn_Db_Schema_Interface $schema, string $tableName): bool {
        $tableName = strtolower($tableName);
        return $schema->tables()->exists($tableName);
    }

    /**
     * Create the table
     *
     * @return bool
     */
    public function create(): bool {
        $this->_schema->tables()->disableForeignKeyChecks();
        $success = $this->_query($this->_createStatement);
        $this->_schema->tables()->enableForeignKeyChecks();
        return $success;
    }

    /**
     * @param mixed $columnNameOrColumn Column name (String) or column itself
     *                                  (Garp_Spawn_Db_Column), in which case its name will be
     *                                  used to check the existence.
     * @return bool
     */
    public function columnExists($columnNameOrColumn): bool {
        $columnName = $columnNameOrColumn instanceof Garp_Spawn_Db_Column
            ? $columnNameOrColumn->name
            : $columnNameOrColumn;

        if (!is_string($columnName)) {
            throw new Exception(
                'Please feed this method either the column name as a string, or a
                Garp_Spawn_Db_Column instance.'
            );
        }

        return (bool)$this->getColumn($columnName);
    }

    public function getColumn($columnName) {
        foreach ($this->columns as $column) {
            if ($column->name === $columnName) {
                return $column;
            }
        }
        return false;
    }

    public function addColumn(Garp_Spawn_Db_Column $newColumn) {
        $addQuery = "ALTER TABLE `{$this->name}` ADD " . $newColumn->renderSqlDefinition();

        if (!$this->_query($addQuery)) {
            throw new Exception(
                "Could not add the '{$newColumn->name}' column to the {$this->name} table."
            );
        }
    }

    public function alterColumn(Garp_Spawn_Db_Column $newColumn) {
        $alterQuery = "ALTER TABLE `{$this->name}` MODIFY " . $newColumn->renderSqlDefinition();

        if (!$this->_query($alterQuery)) {
            throw new Exception(
                "Could not modify the properties of {$this->name}.{$newColumn->name}\n" .
                    $alterQuery . "\n"
            );
        }
    }

    public function deleteColumn(Garp_Spawn_Db_Column $liveColumn) {
        $alterQuery = "ALTER TABLE `{$this->name}` DROP COLUMN `{$liveColumn->name}`;";
        $this->_query($alterQuery);
    }

    protected function _getConfirmationMessage(
        array $diffProperties,
        Garp_Spawn_Db_Column $newColumn
    ) {
        if (count($diffProperties) === 1
            && $diffProperties[0] === 'nullable'
        ) {
            return "Make {$this->name}.{$newColumn->name} " .
                ($newColumn->nullable ? 'no longer ' : '') . 'required? ';
        } else {
            $readableDiffPropsList = Garp_Spawn_Util::humanList($diffProperties, "'");
            return "Change " . $readableDiffPropsList . " of {$this->name} . {$newColumn->name}? ";
        }
    }

    public function getColumns() {
        return $this->columns;
    }

    public function hasRecords() {
        return $this->_query("SELECT COUNT(*) FROM {$this->name}");
    }

    protected function _setPropsByCreateStatement(Garp_Spawn_Model_Abstract $model) {
        $createStatementLines = explode("\n", $this->_createStatement);
        $createStatementLine = null;
        $columnStatements = array();

        foreach ($createStatementLines as $line) {
            if (Garp_Spawn_Db_Statement::isColumnStatement($line)) {
                $this->columns[] = new Garp_Spawn_Db_Column(count($this->columns), $line);
            } elseif (Garp_Spawn_Db_Statement::isCreateStatement($line)) {
                $createStatementLine = $line;
            }
        }

        if (!$createStatementLine
            || !count($this->columns)
        ) {
            throw new Exception(
                "I need at least a CREATE TABLE statement with a declaration of table columns."
            );
        }

        $this->name = $this->_getTableNameFromCreateStatement($createStatementLine);
        $this->keys = new Garp_Spawn_Db_Key_Set($createStatementLines, $this->name, $model);
    }

    protected function _getTableNameFromCreateStatement($line) {
        $matches = array();
        preg_match('/CREATE TABLE\s+`(?P<name>\w+)`/i', trim($line), $matches);
        if (!array_key_exists('name', $matches)) {
            throw new Exception("There was no table name found in the MySQL CREATE statement.");
        }
        return $matches['name'];
    }

    protected function _validateCreateStatement($createStatement) {
        if (!is_string($createStatement)
            || substr($createStatement, 0, 6) !== 'CREATE'
        ) {
            throw new Exception("The provided argument has to be a MySQL 'CREATE' statement.");
        }
    }

    protected function _query(string $statement) {
        try {
            return $this->_schema->query($statement);
        } catch (Exception $e) {
            $msg = $e->getMessage()
                . "\n\n-- You were trying to execute this query: --\n\n"
                . $statement;
            throw new Exception($msg);
        }
    }
}
