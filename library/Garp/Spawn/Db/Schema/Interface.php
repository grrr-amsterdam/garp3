<?php
/**
 * @package Garp_Spawn_Db_Schema
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
interface Garp_Spawn_Db_Schema_Interface {

    public function __construct(Zend_Db_Adapter_Abstract $dbAdapter);

    public function enforceUtf8();

}
