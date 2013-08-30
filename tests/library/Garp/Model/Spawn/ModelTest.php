<?php
/**
 * This class tests Garp_Model_Spawn_Model.
 * @author David Spreekmeester | Grrr.nl
 * @group Spawn
 */
class Garp_Model_Spawn_ModelTest extends PHPUnit_Framework_TestCase {
	protected $_mocks = array(
		'directory' => null,
		'modelName' => 'Bogus',
		'extension' => 'json'
	);
	
	
	public function setUp() {
		$this->_mocks['directory'] = APPLICATION_PATH."/../garp/tests/mocks/application/modules/default/models/config/";
	}


	public function testModelShouldHaveALabel() {
		$model = $this->_constructMockModel();

		$this->assertObjectHasAttribute('label', $model);
		$this->assertGreaterThan(0, strlen($model->label));
	}
	
	
	protected function _constructMockModel() {
		$modelConfig = new Garp_Model_Spawn_Config_Model(
			$this->_mocks['modelName'],
			new Garp_Model_Spawn_Config_Storage_File($this->_mocks['directory'], $this->_mocks['extension']),
			new Garp_Model_Spawn_Config_Format_Json
		);

		return new Garp_Model_Spawn_Model($modelConfig);
	}
}
