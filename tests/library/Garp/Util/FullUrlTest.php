<?php
/**
 * @package Tests
 * @author Harmen Janssen <harmen@grrr.nl>
 * @group  Util
 */
class Garp_Util_FullUrlTest extends Garp_Test_PHPUnit_TestCase {

    public function setUp() {
        parent::setUp();
        $this->_helper->injectConfigValues(
            array(
            'app' => array(
                'domain' => 'example.com'
            )
            )
        );

        $router = Zend_Controller_Front::getInstance()->getRouter();
        $router->addRoute(
            'event_view',
            new Zend_Controller_Router_Route('agenda/event/:slug')
        );
        $router->addRoute(
            'theme_view',
            new Zend_Controller_Router_Route('themas/:slug/:primaryFilter')
        );
    }

    public function testProtocol() {
        $this->_helper->injectConfigValues(
            array(
            'app' => array(
                'protocol' => 'https'
            )
            )
        );
        $this->assertEquals(
            'https://example.com/banaan',
            (string) new Garp_Util_FullUrl('/banaan')
        );

        $this->_helper->injectConfigValues(
            array(
            'app' => array(
                'protocol' => 'ftp'
            )
            )
        );
        $this->assertEquals(
            'ftp://example.com/banaan',
            (string) new Garp_Util_FullUrl('/banaan')
        );

    }

    public function testUrl() {
        $this->_helper->injectConfigValues(
            array(
            'app' => array(
                'protocol' => 'http'
            )
            )
        );

        $this->assertEquals(
            'http://example.com/educatie',
            (string) new Garp_Util_FullUrl('/educatie')
        );

        $this->assertEquals(
            '//example.com/educatie',
            (string) new Garp_Util_FullUrl('/educatie', true, true)
        );

        $this->assertEquals(
            'http://example.com/info/word-vriend',
            (string) new Garp_Util_FullUrl('/info/word-vriend')
        );

        $this->assertEquals(
            'http://example.com/agenda/2014-07-25/alles',
            (string) new Garp_Util_FullUrl('/agenda/2014-07-25/alles')
        );

        //Bogus google maps url
        // @codingStandardsIgnoreStart
        $this->assertEquals('http://example.com/maps/place/Grrr/@52.371188,4.894774,17z/data=!3m1!4b1!4m2!3m1!1s0x47c609eb14a274ab:0x6a0c0234076a9319',
            (string) new Garp_Util_FullUrl('/maps/place/Grrr/@52.371188,4.894774,17z/data=!3m1!4b1!4m2!3m1!1s0x47c609eb14a274ab:0x6a0c0234076a9319'));
        // @codingStandardsIgnoreEnd

        $this->assertEquals(
            'http://example.com/agenda/event/my_slug',
            (string) new Garp_Util_FullUrl(
                array(array('slug' => 'my_slug'),
                    'event_view')
            )
        );

        $this->assertEquals(
            'http://example.com/themas/my_slug/my_filter',
            (string) new Garp_Util_FullUrl(
                array(array(
                        'slug' => 'my_slug',
                        'primaryFilter' => 'my_filter'
                    ),
                    'theme_view'
                )
            )
        );
    }

    public function testCanBeJsonSerialized() {
        $this->assertEquals(
            '{"url":"http:\/\/example.com\/agenda\/event\/my_slug"}',
            json_encode([
                'url' => new Garp_Util_FullUrl(
                    [['slug' => 'my_slug'], 'event_view']
                )
            ])
        );
    }
}
