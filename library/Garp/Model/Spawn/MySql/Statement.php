<?php
/**
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 */
class Garp_Model_Spawn_MySql_Statement {
	static public function isCreateStatement($line) {
		return preg_match('/CREATE TABLE/i', $line);
	}

	static public function isColumnStatement($line) {
		return preg_match('/\s+`\w+`\s+(tinyint|int|float|double|datetime|date|time|timestamp|varchar|char|set|blob|binary|varbinary|text|tinytext|enum|year)/i', $line);
	}
}