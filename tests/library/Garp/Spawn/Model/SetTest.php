<?php
/**
 * This class tests Garp_Spawn_Model_Set.
 * @author David Spreekmeester | Grrr.nl
 * @group Spawn
 */
class Garp_Spawn_Model_SetTest extends PHPUnit_Framework_TestCase {
	protected $_mocks = array(
		'directory' => null,
		'extension' => 'json'
	);

	/**
	 * Garp_Spawn_Model_Set $_modelSet
	 */
	protected $_modelSet;
	
	
	public function setUp() {
		$this->_mocks['directory'] = APPLICATION_PATH . "/../garp/application/modules/mocks/models/config/";
		$this->_modelSet = $this->_constructMockModelSet();
	}


	public function testModelSetShouldContainModels() {
		$this->assertGreaterThan(0, count($this->_modelSet));
	}
	
	
	protected function _constructMockModelSet() {
		return Garp_Spawn_Model_Set::getInstance(
			new Garp_Spawn_Config_Model_Set(
				new Garp_Spawn_Config_Storage_File($this->_mocks['directory'], $this->_mocks['extension']),
				new Garp_Spawn_Config_Format_Json
			)
		);
	}
}