<?php
/**
 * This class tests Garp_Spawn_Model_Set.
 * @author David Spreekmeester | Grrr.nl
 * @group Spawn
 */
class Garp_Spawn_Relation_SetTest extends PHPUnit_Framework_TestCase {
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

	public function testModelSetShouldContainMultipleHabtmRelations() {
		$modelName 		= 'Bogus';
		$relName1 		= 'Foo';
		$relName2 		= 'Foo2';
		$oppositeRule1	= 'Bogus';
		$oppositeRule2	= 'Foo2Bogus';


		$model = $this->_modelSet['Bogus'];
		$habtmRels = $model->relations->getRelations('type', 'hasAndBelongsToMany');

		$this->assertGreaterThan(1, count($habtmRels), "Is there more than 1 habtm relation in {$modelName} model?");

		$this->assertTrue(array_key_exists($relName1, $habtmRels), "Does relation {$relName1} exist in {$modelName} model?");
		$this->assertTrue(array_key_exists($relName2, $habtmRels), "Does relation {$relName2} exist in {$modelName} model?");

		$rel1 = $habtmRels[$relName1];
		$rel2 = $habtmRels[$relName2];

		$this->assertEquals($oppositeRule1, $rel1->oppositeRule, "Is oppositeRule of relation {$relName1} correct?");
		$this->assertEquals($oppositeRule2, $rel2->oppositeRule, "Is oppositeRule of relation {$relName2} correct?");
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