<?php
/**
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 */
class Garp_Model_Spawn_MySql_ForeignKey extends Garp_Model_Spawn_MySql_Key {
	public $name;
	public $localColumn;
	public $remoteTable;
	public $remoteColumn;
	/** @var String $events MySql foreign key event string, f.i.: "ON DELETE SET NULL ON UPDATE SET NULL" */
	public $events;


	/**
	 * @param String $modelId ID of the model in which this foreign key is defined (local model).
	 * @param String $relName Name of the relation (remote table, or alias).
	 */
	public static function generateForeignKeyName($modelId, $relName) {
		return md5($modelId.$relName);
	}


	public static function add($tableName, Garp_Model_Spawn_MySql_ForeignKey $key) {
		$adapter = Zend_Db_Table::getDefaultAdapter();
		return $adapter->query("ALTER TABLE `{$tableName}` ADD CONSTRAINT `{$key->name}` FOREIGN KEY (`{$key->localColumn}`) REFERENCES `{$key->remoteTable}`(`{$key->remoteColumn}`) {$key->events};");
	}


	/** Delete a foreign key, if it exists. */
	public static function delete($tableName, Garp_Model_Spawn_MySql_ForeignKey $key) {
		$adapter = Zend_Db_Table::getDefaultAdapter();
		return $adapter->query("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$key->name}`;");
	}
	
	
	public static function modify($tableName, Garp_Model_Spawn_MySql_ForeignKey $key) {
		$success = false;
		$adapter = Zend_Db_Table::getDefaultAdapter();
		if (self::delete($tableName, $key)) {
			$success = self::add($tableName, $key);
		}
		return $success;
	}


	static public function isForeignKeyStatement($line) {
		return stripos($line, 'CONSTRAINT') !== false;
	}


	protected function _parse($line) {
		$matches = array();
		preg_match('/\s*CONSTRAINT\s+`(?P<name>\w+)`\s+FOREIGN KEY\s*\(`(?P<localColumn>\w+)`\)\s+REFERENCES\s+`(?P<remoteTable>\w+)`\s+\(`(?P<remoteColumn>\w+)`\)\s*(?P<events>[\w\s]+)?/i', trim($line), $matches);
		return $matches;
	}
}