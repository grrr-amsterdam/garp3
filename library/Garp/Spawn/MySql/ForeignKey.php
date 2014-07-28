<?php
/**
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 */
class Garp_Spawn_MySql_ForeignKey extends Garp_Spawn_MySql_Key {
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


	/**
	 * @param String $fkName The foreign key name
	 * @param String $columnName The name of the relation column in the referencing table
	 * @param String $modelName The name of the table, usually the Model.
	 * @param String $relType The relation type, f.i. 'hasOne', 'belongsTo', 'hasAndBelongsToMany'.
	 */
	public static function renderSqlDefinition($fkName, $columnName, $modelName, $relType) {
		$lcModelName = strtolower($modelName);

		switch ($relType) {
			case 'belongsTo':
			case 'hasAndBelongsToMany':
				$events = 'ON DELETE CASCADE ON UPDATE CASCADE';
			break;
			default:
				$events = 'ON DELETE SET NULL ON UPDATE CASCADE';
		}

		return "  CONSTRAINT `{$fkName}` FOREIGN KEY (`{$columnName}`) REFERENCES `{$lcModelName}` (`id`) " . $events;
	}


	public static function add($tableName, Garp_Spawn_MySql_ForeignKey $key) {
		$tableName 	= strtolower($tableName);
		$adapter 	= Zend_Db_Table::getDefaultAdapter();
		$adapter->query("SET FOREIGN_KEY_CHECKS = 0;");
		$success 	= $adapter->query("ALTER TABLE `{$tableName}` ADD CONSTRAINT `{$key->name}` FOREIGN KEY (`{$key->localColumn}`) REFERENCES `{$key->remoteTable}`(`{$key->remoteColumn}`) {$key->events};");
		$adapter->query("SET FOREIGN_KEY_CHECKS = 1;");
		return $success;
	}


	/** Delete a foreign key, if it exists. */
	public static function delete($tableName, Garp_Spawn_MySql_ForeignKey $key) {
		$tableName 	= strtolower($tableName);
		$adapter 	= Zend_Db_Table::getDefaultAdapter();
		$adapter->query("SET FOREIGN_KEY_CHECKS = 0;");
		$success 	= $adapter->query("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$key->name}`;");
		$adapter->query("SET FOREIGN_KEY_CHECKS = 1;");
		return $success;
	}
	
	
	public static function modify($tableName, Garp_Spawn_MySql_ForeignKey $key) {
		$tableName	= strtolower($tableName);
		$success 	= false;
		$adapter 	= Zend_Db_Table::getDefaultAdapter();
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
