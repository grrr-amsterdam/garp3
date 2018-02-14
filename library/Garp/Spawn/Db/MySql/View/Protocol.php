<?php
/**
 * A representation of a MySQL view that includes the labels of related hasOne
 * and belongsTo records.
 *
 * @package Garp_Spawn_Db_View
 * @author  David Spreekmeester <david@grrr.nl>
 */
interface Garp_Spawn_Db_View_Protocol {
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
     *
     * @return void
     */
    public static function deleteAll();
}
