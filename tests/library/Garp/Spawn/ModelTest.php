<?php
/**
 * This class tests Garp_Spawn_Model_Base.
 * @author David Spreekmeester | Grrr.nl
 * @group Spawn
 */
class Garp_Spawn_Model_BaseTest extends PHPUnit_Framework_TestCase {
	protected $_mocks = array(
		'directory' => null,
		'modelName' => 'Bogus',
		'extension' => 'json'
	);


	public function setUp() {
		$this->_mocks['directory'] = GARP_APPLICATION_PATH . "/modules/mocks/models/config/";
	}

	public function testModelShouldHaveALabel() {
		$model = $this->_constructMockModel();

		$this->assertObjectHasAttribute('label', $model);
		$this->assertGreaterThan(0, strlen($model->label));
	}

	public function testModelShouldHaveDefaultTruncatableBehavior() {
		$model = $this->_constructMockModel();

		$this->assertObjectHasAttribute('behaviors', $model);
		$this->assertGreaterThan(0, sizeof($model->behaviors));
		$behaviors = $model->behaviors->getBehaviors();
		$this->assertArrayHasKey('Truncatable', $behaviors);
	}

	protected function _constructMockModel() {
		$modelConfig = new Garp_Spawn_Config_Model_Base(
			$this->_mocks['modelName'],
			new Garp_Spawn_Config_Storage_File($this->_mocks['directory'], $this->_mocks['extension']),
			new Garp_Spawn_Config_Format_Json
		);

		return new Garp_Spawn_Model_Base($modelConfig);
	}
}
