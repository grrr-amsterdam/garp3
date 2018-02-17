<?php
/**
 * @package Garp_Spawn_Db_Schema_Views
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Spawn_Db_Schema_Views_MySql implements Garp_Spawn_Db_Schema_Views_Interface {

    protected $_schema;

    public function __construct(Garp_Spawn_Db_Schema_Interface $schema) {
        $this->_schema = $schema;
    }

    public function fetchByPostfix(string $dbName, string $postfix): array {
        $queryTpl = "SELECT table_name FROM information_schema.views
                        WHERE table_schema = '%s' and table_name like '%%%s';";
        $statement = sprintf($queryTpl, $dbName, $postfix);
        return $this->_schema->fetchAll($statement);
    }

    public function drop(string $viewName) {
        $dropStatement = "DROP VIEW IF EXISTS `{$viewName}`;";
        $this->_schema->query($dropStatement);
    }

}

