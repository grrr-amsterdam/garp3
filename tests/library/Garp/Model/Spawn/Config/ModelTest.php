<?php
/**
 * This class tests Garp_Model_Spawn_Config_Model.
 * @author David Spreekmeester | Grrr.nl
 * @group Spawn
 */
class Garp_Model_Spawn_Config_ModelTest extends PHPUnit_Framework_TestCase {
	protected $_mockModelDir;


	public function setUp() {
		$this->_mockModelDir = APPLICATION_PATH."/../garp/tests/mocks/application/modules/default/models/config/";
	}


	public function testModelConfigShouldContainOrderProperty() {
		$modelConfig = $this->_loadBogusModelConfig('Bogus');

		$this->assertArrayHasKey('order', (array)$modelConfig);
		$this->assertInternalType('string', $modelConfig['order']);
		$this->assertGreaterThan(0, strlen($modelConfig['order']));
	}
	
	public function testLoadingAModelConfigFileShouldReturnConfigurationTree() {
		$modelConfig = $this->_loadBogusModelConfig('Bogus');
	
		$this->assertGreaterThan(0, count($modelConfig));
	}
	
	
	public function testLoadingAModelConfigFileShouldReturnName() {
		$modelName = 'Bogus';
		$modelConfig = $this->_loadBogusModelConfig($modelName);
	
		$this->assertEquals($modelName, $modelConfig['id']);
	}
	
	
	public function testLoadingAModelConfigFileShouldReturnLabel() {
		$modelConfig = $this->_loadBogusModelConfig('Bogus');
	
		$this->assertGreaterThan(0, strlen($modelConfig['label']));
	}


	protected function _loadBogusModelConfig($modelName) {
		return new Garp_Model_Spawn_Config_Model(
			$modelName,
			new Garp_Model_Spawn_Config_Storage_File($this->_mockModelDir, 'json'),
			new Garp_Model_Spawn_Config_Format_Json,
			new Garp_Model_Spawn_Config_Validator_Model
		);
	}
}
