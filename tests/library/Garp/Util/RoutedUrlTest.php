<?php
/**
 * @package Garp3
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Util_RoutedUrlTest extends Garp_Test_PHPUnit_TestCase {

    public function test_can_be_json_serialized() {
        Zend_Registry::get('application')->getBootstrap()->getResource('FrontController')
            ->getRouter()
            ->addRoute('home', new Zend_Controller_Router_Route('/homepage', []));

        $url = new Garp_Util_RoutedUrl('home', []);
        $this->assertSame(
            '{"the_url":"\/homepage"}',
            json_encode([
                'the_url' => $url
            ])
        );
    }

}
