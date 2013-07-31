<?php
/**
 * @author David Spreekmeester | Grrr.nl
 * This class tests Garp_Spawn_Keys.
 * @group Spawn
 */
class Garp_Spawn_MySql_TableTest extends PHPUnit_Framework_TestCase {
	protected $_mocks = array(
		'directory' => null,
		'modelName' => 'Bogus',
		'extension' => 'json'
	);


	public function setUp() {
		$this->_mocks['directory'] = APPLICATION_PATH."/../garp/application/modules/mocks/models/config/";
	}


	protected $_bogusTableSql = <<<EOF
CREATE TABLE `bogus` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(124) NOT NULL,
  `year` int(11) unsigned DEFAULT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `author_id` int(11) unsigned DEFAULT NULL,
  `modifier_id` int(11) unsigned DEFAULT NULL,
  `length` int(11) unsigned DEFAULT NULL,
  `color` tinyint(1) unsigned DEFAULT '1',
  `category` enum('A','B','C','D') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_unique` (`name`),
  KEY `author_id` (`author_id`),
  KEY `modifier_id` (`modifier_id`),
  KEY `name` (`name`),
  CONSTRAINT `a1125055e1239cc6582f97c58813cae1` FOREIGN KEY (`modifier_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `abb2a4eb90c8f9f85d0d3bca202a1ca5` FOREIGN KEY (`author_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
) ENGINE=InnoDB AUTO_INCREMENT=607279 DEFAULT CHARSET=utf8
EOF;


	function testTableShouldContainColumns() {
		$table = new Garp_Spawn_MySql_Table_Base($this->_bogusTableSql, $this->_constructMockModel());
		$this->assertEquals(count($table->columns), 10);
	}


	function testTableShouldHaveValidName() {
		$table = new Garp_Spawn_MySql_Table_Base($this->_bogusTableSql, $this->_constructMockModel());
		$this->assertEquals($table->name, 'bogus');
	}


	protected function _constructMockModel() {
		$modelConfig = new Garp_Spawn_Config_Model_Base(
			$this->_mocks['modelName'],
			new Garp_Spawn_Config_Storage_File($this->_mocks['directory'], $this->_mocks['extension']),
			new Garp_Spawn_Config_Format_Json
		);

		return new Garp_Spawn_Model_Base($modelConfig);
	}

	//	TODO testen van $table->keys
}
