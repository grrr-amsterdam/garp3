<?php
/**
 * This class tests Garp_Model_Spawn_ModelSet.
 * @author David Spreekmeester | Grrr.nl
 * @group Spawn
 */
class Garp_Model_Spawn_ModelSetTest extends PHPUnit_Framework_TestCase {
	protected $_mocks = array(
		'directory' => null,
		'extension' => 'json'
	);
	
	
	public function setUp() {
		$this->_mocks['directory'] = APPLICATION_PATH."/../garp/tests/mocks/application/modules/default/models/config/";
	}


	public function testModelSetShouldContainModels() {
		$modelSet = $this->_constructMockModelSet();
		$this->assertGreaterThan(0, count($modelSet));
	}
	
	
	protected function _constructMockModelSet() {
		return new Garp_Model_Spawn_ModelSet(
			new Garp_Model_Spawn_Config_ModelSet(
				new Garp_Model_Spawn_Config_Storage_File($this->_mocks['directory'], $this->_mocks['extension']),
				new Garp_Model_Spawn_Config_Format_Json
			),
			new Garp_Model_Spawn_Config_HasAndBelongsToManyRelationSet(
				new Garp_Model_Spawn_Config_Storage_File($this->_mocks['directory'], $this->_mocks['extension']),
				new Garp_Model_Spawn_Config_Format_Json
			)
		);
	}
}
