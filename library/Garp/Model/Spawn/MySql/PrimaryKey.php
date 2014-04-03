<?php
/**
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 */
class Garp_Model_Spawn_MySql_PrimaryKey extends Garp_Model_Spawn_MySql_Key {
	public $columns = array();


	public static function isPrimaryKeyStatement($line) {
		return stripos($line, 'PRIMARY KEY') !== false;
	}


	protected function _parse($line) {
		$matches = array();
		preg_match('/PRIMARY KEY\s+\(`(?P<columns>[`\w,]+?)`\)/i', trim($line), $matches);
		if (!array_key_exists('columns', $matches))
			throw new Exception("Could not find any column names in the primary key statement.");
		$columns = explode('`,`', $matches['columns']);
		return $columns;
	}
	
	// 		///alter table a add primary key (editions_id,images_id)
	// 		////alter table a drop primary key

}