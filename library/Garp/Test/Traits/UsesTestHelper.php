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

    public function setUp(): void {
        $this->_helper = new Garp_Test_PHPUnit_Helper();
        $mockData = property_exists($this, '_mockData') ? $this->_mockData : [];
        $this->_helper->setUp($mockData);
        parent::setUp();
    }

    public function tearDown(): void {
        $mockData = property_exists($this, '_mockData') ? $this->_mockData : [];
        if ($this->_helper) {
            $this->_helper->tearDown($mockData);
        }
        parent::tearDown();
    }

}

