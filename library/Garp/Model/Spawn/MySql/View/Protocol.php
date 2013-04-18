<?php
/**
 * A representation of a MySQL view that includes the labels of related hasOne and belongsTo records.
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 * @group Spawn
 */
interface Garp_Model_Spawn_MySql_View_Protocol {
	/**
	 * @return String The name of this view in the database
	 */
	public function getName();
}