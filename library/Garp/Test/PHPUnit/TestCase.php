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
     * @var Garp_Test_PHPUnit_Helper
     */
    protected $_helper;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $_mockData = array();

    public function setUp() {
        $this->_helper = new Garp_Test_PHPUnit_Helper();
        $this->_helper->setUp($this->_mockData);
        parent::setUp();
    }

    public function tearDown() {
        if ($this->_helper) {
            $this->_helper->tearDown($this->_mockData);
        }
        parent::tearDown();
    }
}

