<?php
/**
 * This class tests Garp_Service_Elasticsearch_Configuration
 *
 * @package Tests
 * @author  David Spreekmeester <david@grrr.nl>
 * @group   Elasticsearch
 */
class Garp_Service_Elasticsearch_ConfigurationTest extends Garp_Test_PHPUnit_TestCase {

    /**
     * @var Garp_Service_Elasticsearch_Configuration $_config
     */
    protected $_config;

    /**
     * @return Garp_Service_Elasticsearch_Configuration
     */
    public function getConfig() {
        return $this->_config;
    }

    /**
     * @param Garp_Service_Elasticsearch_Configuration $config
     */
    public function setConfig($config) {
        $this->_config = $config;
        return $this;
    }


    public function setUp() {
        // only test ElasticSearch in a project that uses ElasticSearch
        if (!isset(Zend_Registry::get('config')->elasticsearch)) {
            return;
        }
        $this->setConfig(new Garp_Service_Elasticsearch_Configuration());
    }


    public function testShouldHaveBaseUrl() {
        // only test ElasticSearch in a project that uses ElasticSearch
        if (!isset(Zend_Registry::get('config')->elasticsearch)) {
            $this->assertTrue(true);
            return;
        }

        $config     = $this->getConfig();
        $baseUrl    = $config->getBaseUrl();

        $this->assertTrue(!empty($baseUrl), 'Does configuration.baseUrl have a value?');
    }

    public function testShouldHaveIndex() {
        // only test ElasticSearch in a project that uses ElasticSearch
        if (!isset(Zend_Registry::get('config')->elasticsearch)) {
            $this->assertTrue(true);
            return;
        }
        $config = $this->getConfig();
        $index  = $config->getIndex();

        $this->assertTrue(!empty($index), 'Does configuration.index have a value?');
    }

}
