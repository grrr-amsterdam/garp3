<?php
/**
 * @package Garp_Spawn_Db
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Spawn_Db_Statement {
    static public function isCreateStatement($line) {
        return preg_match('/CREATE TABLE/i', $line);
    }

    static public function isColumnStatement($line) {
        return preg_match('/\s+`\w+`\s+(tinyint|int|float|double|datetime|date|time|timestamp|varchar|char|set|blob|binary|varbinary|text|tinytext|enum|year)/i', $line);
    }
}
