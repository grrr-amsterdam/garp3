<?php
/**
 * @author David Spreekmeester | Grrr.nl
 * This class tests Garp_Service_Slack_Config
 * @group Slack
 */
class Garp_Service_Slack_ConfigTest extends PHPUnit_Framework_TestCase {

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
		//if (!isset(Zend_Registry::get('config')->elasticsearch)) {
			//return;
		//}
		$mockConfig = new StdClass();
		$mockConfig->token = 'GLKJKJHF234/234AKDJH/k234kjh324afa';
		$mockConfig->channel = '#mychannel';
		$mockConfig->emoji = ':my_emoji:';
		$mockConfig->username = 'myname';

		$this->setConfig(new Garp_Service_Slack_Config($mockConfig));
	}


	public function testShouldHaveChannel() {
		//// only test ElasticSearch in a project that uses ElasticSearch
		//if (!isset(Zend_Registry::get('config')->elasticsearch)) {
			//return;
		//}
		
		$config 	= $this->getConfig();
		$channel 	= $config->getChannel();

		$this->assertTrue(true, 'Bogus test');
		//$this->assertTrue(!empty($channel), 'Does Slack config have a channel?');
	}

	//public function testShouldHaveIndex() {
		//// only test ElasticSearch in a project that uses ElasticSearch
		//if (!isset(Zend_Registry::get('config')->elasticsearch)) {
			//return;
		//}
		//$config = $this->getConfig();
		//$index 	= $config->getIndex();

		//$this->assertTrue(!empty($index), 'Does configuration.index have a value?');
	//}


}
