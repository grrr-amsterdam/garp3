<?php
/**
 * @package Garp_Spawn_Db_Schema_Views
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
interface Garp_Spawn_Db_Schema_Views_Interface {

    public function __construct(Garp_Spawn_Db_Schema_Interface $schema);

    public function fetchByPostfix(string $dbName, string $postfix): array;

    public function drop(string $viewName);

}
