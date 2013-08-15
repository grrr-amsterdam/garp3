<?php
/**
 * @author David Spreekmeester | Grrr.nl
 * This class tests Garp_Service_Elasticsearch_Request
 * @group Elasticsearch
 */
class Garp_Service_Elasticsearch_RequestTest extends PHPUnit_Framework_TestCase {
	const BOGUS_PATH = '/Bogus/666';


	public function testRequestShouldNotHaveDuplicateSlashes() {
		$method 	= Garp_Service_Elasticsearch_Request::GET;
		$request 	= new Garp_Service_Elasticsearch_Request($method, self::BOGUS_PATH);
		$url 		= $request->getUrl();

		// strip off the protocol, cause those duplicate slashes don't count.
		$url = substr($url, 7);

		$containsDuplicateSlashes = strpos($url, '//') !== false;
		$this->assertFalse($containsDuplicateSlashes, "Does the following request url contain duplicate slashes?\n" . $url);
	}

}
