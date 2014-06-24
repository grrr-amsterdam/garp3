<?php
/**
 * @author David Spreekmeester | Grrr.nl
 * This class tests Garp_Service_Elasticsearch_Configuration
 * @group Elasticsearch
 */
class Garp_Service_Elasticsearch_ConfigurationTest extends PHPUnit_Framework_TestCase {

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
	 * @param Garp_Service_Elasticsearch_Configuration _config
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
			return;
		}
		
		$config 	= $this->getConfig();
		$baseUrl 	= $config->getBaseUrl();

		$this->assertTrue(!empty($baseUrl), 'Does configuration.baseUrl have a value?');
	}

	public function testShouldHaveIndex() {
		// only test ElasticSearch in a project that uses ElasticSearch
		if (!isset(Zend_Registry::get('config')->elasticsearch)) {
			return;
		}
		$config = $this->getConfig();
		$index 	= $config->getIndex();

		$this->assertTrue(!empty($index), 'Does configuration.index have a value?');
	}


}
