<?php
/**
 * @package Garp_Spawn_Db
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Spawn_Db_UniqueKey extends Garp_Spawn_Db_Key {
    public $name;
    public $column;

    const KEY_NAME_POSTFIX = '_unique';


    /**
     * @param  mixed $columnName A column name (String), or an array of column names,
     *                           to combine multiple columns into a single unique key.
     * @return string
     */
    public static function renderSqlDefinition($columnName) {
        $keyName    = is_array($columnName) ?
            implode('_', $columnName) :
            $columnName
        ;

        if (is_array($columnName)) {
            $columnName = implode('`,`', $columnName);
        }

        return
            "  UNIQUE KEY `" . $keyName
            . self::KEY_NAME_POSTFIX
            . "` (`{$columnName}`)"
        ;
    }

    public static function add($tableName, Garp_Spawn_Db_UniqueKey $key) {
        $column = is_array($key->column) ?
            implode('`,`', $key->column) :
            $key->column
        ;

        $tableName  = strtolower($tableName);
        $adapter    = Zend_Db_Table::getDefaultAdapter();
        $query      = "ALTER TABLE `{$tableName}` ADD UNIQUE `{$key->name}` (`{$column}`);";

        return $adapter->query($query);
    }


    public static function delete($tableName, Garp_Spawn_Db_UniqueKey $key) {
        $tableName  = strtolower($tableName);
        $adapter    = Zend_Db_Table::getDefaultAdapter();
        $adapter->query("SET FOREIGN_KEY_CHECKS = 0;");
        $success = $adapter->query("ALTER TABLE `{$tableName}` DROP INDEX `{$key->name}`;");
        $adapter->query("SET FOREIGN_KEY_CHECKS = 1;");
        return $success;
    }


    public static function isUniqueKeyStatement($line) {
        return stripos($line, 'UNIQUE KEY') !== false;
    }


    protected function _parse($line) {
        $matches = array();
        preg_match('/UNIQUE KEY\s+`(?P<name>\w+)`\s+\(`?(?P<column>[\w,` ]+)`?\)/i', trim($line), $matches);
        if (!array_key_exists('column', $matches) || !array_key_exists('name', $matches)) {
            throw new Exception(
                "Could not find a column and index name in the unique key statement.\n" . $line
            );
        }

        $matches['column'] = str_replace('`', '', $matches['column']);
        if (strpos($matches['column'], ',') !== false) {
            $matches['column'] = explode(',', $matches['column']);
        }

        return $matches;
    }
}
