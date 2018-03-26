<?php
/**
 * @package Garp_Spawn_Db_Schema_Tables
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Spawn_Db_Schema_Tables_MySql implements Garp_Spawn_Db_Schema_Tables_Interface {

    protected $_schema;

    public function __construct(Garp_Spawn_Db_Schema_Interface $schema) {
        $this->_schema = $schema;
    }

    public function exists(string $tableName): bool {
        $dbConfig = $this->_schema->getAdapter()->getConfig();
        return (bool)$this->_schema->query(
            'SELECT * '
            . 'FROM information_schema.tables '
            . "WHERE table_schema = '{$dbConfig['dbname']}' "
            . "AND table_name = '{$tableName}'"
        )->fetch();
    }

    public function enableForeignKeyChecks() {
        return $this->_schema->query(
            'SET FOREIGN_KEY_CHECKS = 1;'
        );
    }

    public function disableForeignKeyChecks() {
        return $this->_schema->query(
            'SET FOREIGN_KEY_CHECKS = 0;'
        );
    }

    public function describe(string $tableName): string {
        $liveTable = $this->_schema->fetchAll("SHOW CREATE TABLE `{$tableName}`;");
        return $liveTable[0]['Create Table'] . ';';
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
            $lines[] = Garp_Spawn_Db_Column_MySql::renderFieldSql($field);
        }

        $primKeys = [];
        $uniqueKeys = [];

        if ($unique) {
            // This checks wether a single one-dimensional array is given: a collection of
            // columns combined into a unique key, or wether an array of arrays is given, meaning
            // multiple collections of columns combining into multiple unique keys per table.
            $isArrayOfArrays = count(array_filter($unique, 'is_array')) === count($unique);
            $unique = !$isArrayOfArrays ? [$unique] : $unique;
            $uniqueKeys = array_merge($uniqueKeys, $unique);
        }

        foreach ($fields as $field) {
            if ($field->primary) {
                $primKeys[] = $field->name;
            }
            if ($field->unique) {
                $uniqueKeys[] = $field->name;
            }
        }
        if ($primKeys) {
            $lines[] = Garp_Spawn_Db_PrimaryKey::renderSqlDefinition($primKeys);
        }
        foreach ($uniqueKeys as $fieldName) {
            $lines[] = Garp_Spawn_Db_UniqueKey::renderSqlDefinition($fieldName);
        }

        foreach ($relations as $rel) {
            if (($rel->type === 'hasOne' || $rel->type === 'belongsTo') && !$rel->multilingual) {
                $lines[] = Garp_Spawn_Db_IndexKey::renderSqlDefinition($rel->column);
            }
        }

        //  set indices that were configured in the Spawn model config
        foreach ($fields as $field) {
            if ($field->index) {
                $lines[] = Garp_Spawn_Db_IndexKey::renderSqlDefinition($field->name);
            }
        }

        foreach ($relations as $relName => $rel) {
            if (($rel->type === 'hasOne' || $rel->type === 'belongsTo') && !$rel->multilingual) {
                $fkName = Garp_Spawn_Db_ForeignKey::generateForeignKeyName($tableName, $relName);
                $lines[] = Garp_Spawn_Db_ForeignKey::renderSqlDefinition(
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
