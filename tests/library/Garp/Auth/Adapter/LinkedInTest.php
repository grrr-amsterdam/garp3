<?php
/**
 * @package Tests
 * @author  Harmen Janssen <harmen@grrr.nl>
 * @group   Auth
 */
class Garp_Auth_Adapter_LinkedInTest extends Garp_Test_PHPUnit_TestCase {

    public function testFactoryShouldRecognizeLinkedInType() {
        $this->assertTrue(
            Garp_Auth_Factory::getAdapter('LinkedIn') instanceof
                Garp_Auth_Adapter_LinkedIn
        );
    }

    public function setUp() {
        $this->_helper->injectConfigValues(
            array(
                'auth' => array(
                    'adapters' => array(
                        'linkedin' => array()
                    )
                )
            )
        );
    }

}
