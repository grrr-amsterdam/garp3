<?php
/**
 * @package Garp
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
trait Garp_Test_Traits_UsesTestHelper {

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

    public function setUp(): void {
        $this->_helper = new Garp_Test_PHPUnit_Helper();
        $this->_helper->setUp($this->_mockData);
        parent::setUp();
    }

    public function tearDown(): void {
        if ($this->_helper) {
            $this->_helper->tearDown($this->_mockData);
        }
        parent::tearDown();
    }

}
