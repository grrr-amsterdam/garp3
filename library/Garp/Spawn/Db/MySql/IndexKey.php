<?php
/**
 * @package Garp_Spawn_Db
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Spawn_Db_IndexKey extends Garp_Spawn_Db_Key {
    public $name;
    public $column;

    /**
     * @param  string $line       Line of SQL from the model's CREATE TABLE statement.
     * @param  array $foreignKeys The foreignKeys in this table, of type Garp_Spawn_Db_ForeignKey.
     * @return bool               Returns true if this is a 'KEY' (index) statement.
     *                            This does not include primary or foreign keys.
     */
    public static function isIndexKeyStatement($line, Array $foreignKeys) {
        if (substr(trim($line), 0, 3) === 'KEY') {
            $key = self::_parse($line);

            foreach ($foreignKeys as $fk) {
                if ($key['column'] === $fk->localColumn) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    public static function renderSqlDefinition($columnName) {
        return "  KEY `{$columnName}` (`{$columnName}`)";
    }

    public static function add($tableName, Garp_Spawn_Db_IndexKey $key) {
        $tableName  = strtolower($tableName);
        $adapter    = Zend_Db_Table::getDefaultAdapter();
        $success    = false;

        try {
            $success = $adapter->query(
                "ALTER TABLE `{$tableName}` ADD KEY `{$key->name}` (`{$key->column}`);"
            );
        } catch(Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate') === false) {
                throw $e;
            }
            $success = true;
        }
        return $success;
    }

    public static function delete($tableName, Garp_Spawn_Db_IndexKey $key) {
        $tableName = strtolower($tableName);
        $adapter   = Zend_Db_Table::getDefaultAdapter();
        return $adapter->query("ALTER TABLE `{$tableName}` DROP KEY `{$key->name}`;");
    }

    protected static function _parse($line) {
        $matches = [];
        preg_match('/\s*KEY\s+`(?P<name>\w+)`\s*\(`(?P<column>\w+)`\),?/i', trim($line), $matches);
        return $matches;
    }
}
