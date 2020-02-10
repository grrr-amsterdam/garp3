<?php

/**
 * Class Garp_Util_AssetUrlTest
 *
 * @package Garp3
 * @author Martijn Gastkemper <martijn@grrr.nl>
 */
class Garp_Util_AssetUrlTest extends Garp_Test_PHPUnit_TestCase {

    public function setUp(): void {
        parent::setUp();
        $this->_helper->injectConfigValues(
            array(
                'cdn' => array(
                    'baseUrl' => 'example.com'
                )
            )
        );
    }

    public function testToString() {
        $url = new Garp_Util_AssetUrl();
        $this->assertEquals('example.com', (string)$url);
    }

    public function testJsonSerialize() {
        $url = new Garp_Util_AssetUrl();
        $this->assertEquals('["example.com"]', json_encode([$url]));
    }

}
