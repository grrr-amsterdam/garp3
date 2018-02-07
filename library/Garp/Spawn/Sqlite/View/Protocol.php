<?php
/**
 * A representation of a Sqlite view that includes the labels of related hasOne
 * and belongsTo records.
 *
 * @package Garp_Spawn_Sqlite_View
 * @author  David Spreekmeester <david@grrr.nl>
 */
interface Garp_Spawn_Sqlite_View_Protocol {
    /**
     * @return string The name of this view in the database
     */
    public function getName();

    /**
     * @return string The sql to create this view
     */
    public function renderSql();

    /**
     * Do a direct query on the database, removing all views of this type
     *
     * @return void
     */
    public static function deleteAll();
}
