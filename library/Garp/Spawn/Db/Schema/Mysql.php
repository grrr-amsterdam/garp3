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

    public function views(): Garp_Spawn_Db_Schema_Views_Interface {
        return new Garp_Spawn_Db_Schema_Views_MySql($this);
    }

    public function tables(): Garp_Spawn_Db_Schema_Tables_Interface {
        return new Garp_Spawn_Db_Schema_Tables_MySql($this);
    }

    public function query(string $sql) {
        return $this->_dbAdapter->query($sql);
    }

    public function fetchAll(string $sql, $bind = array(), $fetchMode = null): array {
        return $this->_dbAdapter->fetchAll($sql, $bind, $fetchMode);
    }

    public function getAdapter(): Zend_Db_Adapter_Abstract {
        return $this->_dbAdapter;
    }
}
