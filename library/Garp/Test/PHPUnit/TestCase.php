<?php
use PHPUnit\Framework\TestCase;

/**
 * Garp_Test_PHPUnit_TestCase
 * Adds some convenience methods to unit tests.
 *
 * @package Garp_Test_PHPUnit
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
abstract class Garp_Test_PHPUnit_TestCase extends TestCase {
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;

    /**
     * @var Garp_Test_PHPUnit_Helper
     */
    protected $_helper;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $_mockData = array();

    public function __construct() {
        $this->_helper = new Garp_Test_PHPUnit_Helper();
        parent::__construct();
    }

    /**
     * Get database adapter for executing queries quickly.
     * It will be configured as defined in application.ini.
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function getDatabaseAdapter() {
        if (!$this->_db) {
            $ini = Zend_Registry::get('config');
            $this->_db = Zend_Db::factory($ini->resources->db);
        }
        return $this->_db;
    }

    public function setUp() {
        $this->_helper->setUp($this->_mockData);
        parent::setUp();
    }

    public function tearDown() {
        $this->_helper->tearDown($this->_mockData);
        parent::tearDown();
    }
}
