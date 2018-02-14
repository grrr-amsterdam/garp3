<?php
/**
 * @package Garp_Spawn_Db_Schema
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Spawn_Db_Schema_Factory {

    public static function getSchemaByAdapter(
        Zend_Db_Adapter_Abstract $dbAdapter
    ): Garp_Spawn_Db_Schema_Interface {
        $classNameParts = explode('_', get_class($dbAdapter));
        $className = "Garp_Spawn_Db_Schema_{$classNameParts[count($classNameParts) - 1]}";
        return new $className($dbAdapter);
    }

}
