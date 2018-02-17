<?php
/**
 * @package Garp_Spawn_Db_Schema
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
interface Garp_Spawn_Db_Schema_Interface {

    public function __construct(Zend_Db_Adapter_Abstract $dbAdapter);

    public function enforceUtf8();

    public function views(): Garp_Spawn_Db_Schema_Views_Interface;

    public function tables(): Garp_Spawn_Db_Schema_Tables_Interface;

    public function fetchAll(string $sql, $bind = array(), $fetchMode = null): array;

    public function getAdapter(): Zend_Db_Adapter_Abstract;
}
