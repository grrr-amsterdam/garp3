<?php
/**
 * @package Garp_Spawn_Db_Schema_Tables
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Spawn_Db_Schema_Tables_Sqlite implements Garp_Spawn_Db_Schema_Tables_Interface {

    protected $_schema;

    public function __construct(Garp_Spawn_Db_Schema_Interface $schema) {
        $this->_schema = $schema;
    }

    public function exists(string $tableName): bool {
        $dbConfig = $this->_schema->getAdapter()->getConfig();
        return (bool)$this->_schema->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name='$tableName'"
        )->fetch();
    }

    public function enableForeignKeyChecks() {
        return $this->_schema->query(
            'PRAGMA foreign_keys = ON;'
        );
    }

    public function disableForeignKeyChecks() {
        return $this->_schema->query(
            'PRAGMA foreign_keys = OFF;'
        );
    }

    public function describe(string $tableName): string {
        $result = $this->_schema->fetchAll("SELECT sql FROM sqlite_master WHERE name = '{$tableName}';");

        throw new Exception('finish me');
    }

    /**
     * Render a CREATE TABLE statement.
     *
     * @param  string $tableName
     * @param  array  $fields     Numeric array of Garp_Spawn_Field objects.
     * @param  array  $relations  Associative array, where the key is the name
     *                            of the relation, and the value a Garp_Spawn_Relation object,
     *                            or at least an object with properties column, model, type.
     * @param  array  $unique     (optional) List of column names to be combined into a unique id.
     *                            This is model-wide and supersedes the 'unique' property per field.
     * @return string
     */
    public function renderCreateStatement(string $tableName, array $fields, array $relations, $unique): string {
        $lines = [];

        foreach ($fields as $field) {
            $lines[] = Garp_Spawn_Db_Column::renderFieldSql($field);
        }

        $out = "CREATE TABLE `{$tableName}` (\n";
        $out .= implode(",\n", $lines);
        $out .= "\n)";
        return $out;
    }

}

