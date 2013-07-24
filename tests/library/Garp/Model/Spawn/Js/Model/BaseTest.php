<?php
/**
 * This class tests Garp_Spawn_Js_Renderer.
 * @author David Spreekmeester | Grrr.nl
 * @group Spawn
 */
class Garp_Spawn_Js_Model_BaseTest extends PHPUnit_Framework_TestCase {
	protected $_mocks = array(
		'directory' => null,
		'modelName' => 'Bogus',
		'extension' => 'json'
	);


	public function setUp() {
		$this->_mocks['directory'] = APPLICATION_PATH."/../garp/tests/mocks/application/modules/default/models/config/";
	}


	public function testShouldBeAbleToRenderShitIntoTemplate() {
		$model = new Garp_Spawn_Js_Model_Base($this->_mocks['modelName'], $this->_constructMockModelSet());
		$modelOutput = $model->render();

		$this->assertGreaterThan(0, strlen($modelOutput));
	}


	protected function _constructMockModelSet() {
		return Garp_Spawn_Model_Set::getInstance(
			new Garp_Spawn_Config_ModelSet(
				new Garp_Spawn_Config_Storage_File($this->_mocks['directory'], $this->_mocks['extension']),
				new Garp_Spawn_Config_Format_Json
			)
		);
	}
}
