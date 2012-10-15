<?php
/**
 * This class tests Garp_Model_Spawn_Model.
 * @author David Spreekmeester | Grrr.nl
 * @group Spawn
 */
class Garp_Model_Spawn_Config_Storage_FileTest extends PHPUnit_Framework_TestCase {
	protected $_mocks = array(
		'directory' => null,
		'extension' => 'json',
		'modelName' => 'Bogus'
	);


	public function setUp() {
		$this->_mocks['directory'] = APPLICATION_PATH."/../garp/tests/mocks/application/modules/default/models/config/";
	}


	function testMockConfigShouldContainBytes() {
		$storage = new Garp_Model_Spawn_Config_Storage_File($this->_mocks['directory'], $this->_mocks['extension']);
		$rawConfig = $storage->load($this->_mocks['modelName']);
		
		$this->assertGreaterThan(0, strlen($rawConfig));
	}
	
	
	function testShouldBeAbleToRetrieveObjectList() {
		$storage = new Garp_Model_Spawn_Config_Storage_File($this->_mocks['directory'], $this->_mocks['extension']);
		$filenames = $storage->listObjectIds();

		$this->assertGreaterThan(1, count($filenames));
	}
}
