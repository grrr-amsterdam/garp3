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

}
