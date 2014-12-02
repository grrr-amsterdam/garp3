<?php
/**
 * A representation of a MySQL view that includes the labels of related hasOne and belongsTo records.
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 * @group Spawn
 */
interface Garp_Spawn_MySql_View_Protocol {
	/**
	 * @return String The name of this view in the database
	 */
	public function getName();
	
	/**
	 * @return String The sql to create this view
	 */
	public function renderSql();
	
	/**
	 * Do a direct query on the database, removing all views of this type
	 */
	public static function deleteAll();
}