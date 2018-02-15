<?php
/**
 * @package Garp_Spawn_Db_Schema_Mysql
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Spawn_Db_Schema_Mysql implements Garp_Spawn_Db_Schema_Interface {

    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_dbAdapter;

    public function __construct(Zend_Db_Adapter_Abstract $dbAdapter) {
        $this->_dbAdapter = $dbAdapter;
    }

    public function enforceUtf8() {
        $this->_dbAdapter->query('SET NAMES utf8;');
    }

    public function fetchViewsByPostfix(string $dbName, string $postfix): array {
        $queryTpl = "SELECT table_name FROM information_schema.views
                        WHERE table_schema = '%s' and table_name like '%%%s';";
        $statement = sprintf($queryTpl, $dbName, $postfix);
        return $this->_dbAdapter->fetchAll($statement);
    }

    public function dropView(string $viewName) {
        $dropStatement = "DROP VIEW IF EXISTS `{$viewName}`;";
        $this->query($dropStatement);
    }

    public function query(string $sql) {
        return $this->_dbAdapter->query($sql);
    }

}
