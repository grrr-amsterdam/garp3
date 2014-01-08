<?php
/**
 * @author Harmen Janssen | Grrr.nl
 * This class tests Garp_Service_Gofilex
 */
class Garp_Service_GofilexTest extends PHPUnit_Framework_TestCase {
	/**
 	 * Gofilex service object
 	 * @var Garp_Service_Gofilex
 	 */
	protected $_service;


	public function setUp() {
		$wdsl = 'http://82.94.241.186:34/GofilexC.nsf/GofilexCOD?WSDL';
		$this->_service = new Garp_Service_Gofilex($wdsl);
	}


	public function testShouldReceiveArrayOfMovies() {
		$movies = $this->_service->getMovies();
		$this->assertInternalType('array', $movies);
	}


	public function testShouldReceiveArrayOfTheaters() {
		$theaters = $this->_service->getTheaters();
		$this->assertInternalType('array', $theaters);
	}
}
