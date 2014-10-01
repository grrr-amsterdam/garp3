<?php
/**
 * @group Util
 */
class Garp_Util_FullUrlTest extends Garp_Test_PHPUnit_TestCase {

	public function setUp() {
		$this->_helper->injectConfigValues(array(
			'app' => array(
				'domain' => 'loc.filmhuisdenhaag.nl'
			)
		));

		$router = Zend_Controller_Front::getInstance()->getRouter();
		$router->addRoute(
			'event_view',
			new Zend_Controller_Router_Route('agenda/event/:slug'));
		$router->addRoute(
			'theme_view',
			new Zend_Controller_Router_Route('themas/:slug/:primaryFilter'));
	}

	public function testUrl() {
		
		$this->assertEquals('http://loc.filmhuisdenhaag.nl/educatie', 
			(string) new Garp_Util_FullUrl('/educatie'));

		$this->assertEquals('//loc.filmhuisdenhaag.nl/educatie', 
			(string) new Garp_Util_FullUrl('/educatie',true,true));

		$this->assertEquals('http://loc.filmhuisdenhaag.nl/info/word-vriend', 
			(string) new Garp_Util_FullUrl('/info/word-vriend'));
			
		$this->assertEquals('http://loc.filmhuisdenhaag.nl/agenda/2014-07-25/alles', 
			(string) new Garp_Util_FullUrl('/agenda/2014-07-25/alles'));

		//Bogus google maps url
		$this->assertEquals('http://loc.filmhuisdenhaag.nl/maps/place/Grrr/@52.371188,4.894774,17z/data=!3m1!4b1!4m2!3m1!1s0x47c609eb14a274ab:0x6a0c0234076a9319', 
			(string) new Garp_Util_FullUrl('/maps/place/Grrr/@52.371188,4.894774,17z/data=!3m1!4b1!4m2!3m1!1s0x47c609eb14a274ab:0x6a0c0234076a9319'));

		$this->assertEquals('http://loc.filmhuisdenhaag.nl/agenda/event/my_slug',
			(string) new Garp_Util_FullUrl(
				array(array('slug' => 'my_slug'), 
					'event_view')
				));

		$this->assertEquals('http://loc.filmhuisdenhaag.nl/themas/my_slug/my_filter',
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
} 
