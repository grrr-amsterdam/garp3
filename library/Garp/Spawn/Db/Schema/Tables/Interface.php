<?php
/**
 * @package Garp_Spawn_Db_Schema_Tables
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
interface Garp_Spawn_Db_Schema_Tables_Interface {

    public function __construct(Garp_Spawn_Db_Schema_Interface $schema);

    public function exists(string $tableName): bool;

    public function enableForeignKeyChecks();

    public function disableForeignKeyChecks();

    public function renderCreateStatement(string $tableName, array $fields, array $relations, $unique): string;

    public function describe(string $tableName): string;

}
