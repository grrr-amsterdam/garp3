<?php
/**
 * Factory for DbManagers
 *
 * @package Garp_Spawn
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Spawn_Db_ManagerFactory {

    public static function getInstance(Zend_Db_Adapter_Abstract $adapter, Garp_Cli_Ui $progress): Garp_Spawn_Db_Manager_Abstract {
        $flavour = array_slice(explode('_', get_class($adapter)), -1)[0];
        $managerClass = "Garp_Spawn_{$flavour}_Manager";
        return $managerClass::getInstance($progress);
    }
}
