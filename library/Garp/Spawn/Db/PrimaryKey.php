<?php
/**
 * @package Garp_Spawn_Db
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Spawn_Db_PrimaryKey extends Garp_Spawn_Db_Key {
    public $columns = array();

    public static function isPrimaryKeyStatement($line) {
        return stripos($line, 'PRIMARY KEY') !== false;
    }

    /**
     * @param array $primaryKeyNames An array of field names that will serve as primary key.
     * @return string
     */
    public static function renderSqlDefinition(array $primaryKeyNames) {
        return "  PRIMARY KEY (`" . implode($primaryKeyNames, "`,`") . "`)";
    }

    public static function modify($tableName, Garp_Spawn_Db_PrimaryKey $newPrimaryKey) {
        $tableName  = strtolower($tableName);
        $adapter    = Zend_Db_Table::getDefaultAdapter();

        $sql =   "ALTER TABLE `{$tableName}` ";
        if (self::_liveTableHasPrimaryKey($tableName)) {
            $sql .= "DROP PRIMARY KEY, ";
        }
        $sql .= "ADD PRIMARY KEY(`" . implode($newPrimaryKey->columns, "`,`") . "`)";

        return $adapter->query($sql);
    }

    protected static function _liveTableHasPrimaryKey($tableName) {
        $tableName  = strtolower($tableName);
        $adapter    = Zend_Db_Table::getDefaultAdapter();
        return (bool)$adapter->query(
            "SHOW INDEXES FROM `{$tableName}` WHERE Key_name = 'PRIMARY'"
        )->fetch();
    }

    protected function _parse($line) {
        $matches = array();
        preg_match('/PRIMARY KEY\s+\((?P<columns>[`\w,]+?)\)/i', trim($line), $matches);
        if (!array_key_exists('columns', $matches)) {
            throw new Exception("Could not find any column names in the primary key statement.");
        }
        $columns = preg_split('/`+,?\s?/', $matches['columns'], null, PREG_SPLIT_NO_EMPTY);
        return array('columns' => $columns);
    }
}
