<?php
/**
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 */
class Garp_Model_Spawn_MySql_UniqueKey extends Garp_Model_Spawn_MySql_Key {
	public $name;
	public $column;
	
	const KEY_NAME_POSTFIX = '_unique';


	public static function add($tableName, Garp_Model_Spawn_MySql_UniqueKey $key) {
		$adapter = Zend_Db_Table::getDefaultAdapter();
		return $adapter->query("ALTER TABLE `{$tableName}` ADD UNIQUE `{$key->name}`(`{$key->column}`);");
	}


	public static function delete($tableName, Garp_Model_Spawn_MySql_UniqueKey $key) {
		$adapter = Zend_Db_Table::getDefaultAdapter();
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
		preg_match('/UNIQUE KEY\s+`(?P<name>\w+)`\s+\(`?(?P<column>[\w]+)`?\)/i', trim($line), $matches);
		if (
			!array_key_exists('column', $matches) ||
			!array_key_exists('name', $matches)
		) {
			throw new Exception("Could not find a column and index name in the unique key statement.");
		}
		return $matches;
	}
}