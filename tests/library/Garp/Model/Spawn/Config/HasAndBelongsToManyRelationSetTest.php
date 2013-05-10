<?php
/**
 * This class tests Garp_Spawn_Config_HasAndBelongsToManyRelationSet.
 * @author David Spreekmeester | Grrr.nl
 * @group Spawn
 */
class Garp_Spawn_Config_HasAndBelongsToManyRelationSetTest extends PHPUnit_Framework_TestCase {
	protected $_mockModelDir;


	public function setUp() {
		$this->_mockModelDir = APPLICATION_PATH."/../garp/tests/mocks/application/modules/default/models/config/";
	}


	public function testHabtmConfigShouldContainEntries() {
		$habtmConfig = $this->_loadMockHabtmConfig();
		$this->assertGreaterThan(0, count($habtmConfig));
	}


	protected function _loadMockHabtmConfig() {
		return new Garp_Spawn_Config_HasAndBelongsToManyRelationSet (
			new Garp_Spawn_Config_Storage_File($this->_mockModelDir, 'json'),
			new Garp_Spawn_Config_Format_Json
		);
	}
}
