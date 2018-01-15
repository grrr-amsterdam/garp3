<?php
/**
 * This class tests Garp_Service_Elasticsearch_Request
 *
 * @package Tests
 * @author  David Spreekmeester <david@grrr.nl>
 * @group   Elasticsearch
 */
class Garp_Service_Elasticsearch_RequestTest extends Garp_Test_PHPUnit_TestCase {
    const BOGUS_PATH = '/Bogus/666';


    public function testRequestShouldNotHaveDuplicateSlashes() {
        // only test ElasticSearch in a project that uses ElasticSearch
        if (!isset(Zend_Registry::get('config')->elasticsearch)) {
            $this->assertTrue(true);
            return;
        }
        $method     = Garp_Service_Elasticsearch_Request::GET;
        $request    = new Garp_Service_Elasticsearch_Request($method, self::BOGUS_PATH);
        $url        = $request->getUrl();

        // strip off the protocol, cause those duplicate slashes don't count.
        $url = substr($url, 7);

        $containsDuplicateSlashes = strpos($url, '//') !== false;
        $this->assertFalse(
            $containsDuplicateSlashes,
            "Does the following request url contain duplicate slashes?\n" . $url
        );
    }

}
