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

	public function testModelSetShouldContainMulipleRelationsToTheSameModel() {
		$modelName 			= 'Bogus';
		$opposingModelName 	= 'Foo';
		$relName1 			= 'Foo';
		$relName2 			= 'Foo2';
		$relName3 			= 'PrimaryFoo';
		$oppositeRule1		= 'Bogus';
		$oppositeRule2		= 'Foo2';
		$oppositeRule3		= 'Bogus';


		$model 			= $this->_modelSet[$modelName];

		//	test habtm relations
		$habtmRels 		= $model->relations->getRelations('type', 'hasAndBelongsToMany');

		$this->assertGreaterThan(1, count($habtmRels), "Is there more than 1 habtm relation in {$modelName} model?");
		$this->assertTrue(array_key_exists($relName1, $habtmRels), "Does relation {$relName1} exist in {$modelName} model?");
		$this->assertTrue(array_key_exists($relName2, $habtmRels), "Does relation {$relName2} exist in {$modelName} model?");

		$rel1 = $habtmRels[$relName1];
		$rel2 = $habtmRels[$relName2];

		$this->assertEquals($oppositeRule1, $rel1->oppositeRule, "Is oppositeRule of relation {$relName1} correct?");
		$this->assertEquals($oppositeRule2, $rel2->oppositeRule, "Is oppositeRule of relation {$relName2} correct?");


		//	test hasOne relation
		$rel3 = $habtmRels[$relName1];
		$hasOneRels 	= $model->relations->getRelations('type', 'hasOne');
		$this->assertTrue(array_key_exists($relName3, $hasOneRels), "Does relation {$relName3} exist in {$modelName} model?");
		$this->assertEquals($oppositeRule3, $rel3->oppositeRule, "Is oppositeRule of relation {$relName3} correct?");


		//	test opposing hasMany relation
		$opposingModel 			= $this->_modelSet[$opposingModelName];
		$opposingHasManyRels 	= $opposingModel->relations->getRelations('type', 'hasMany');
		$this->assertTrue(array_key_exists($relName3, $opposingHasManyRels), "Does relation {$relName3} exist in {$opposingModelName} model?");
	}

	public function testHabtmHomoRelationsFromBindingModel() {
		$modelName 	= 'Bogus';
		$relName1 	= 'Bogus';
		$relName2	= 'BogusLike';

		$model 		= $this->_modelSet[$modelName];
		$habtmRels 	= $model->relations->getRelations('type', 'hasAndBelongsToMany');
		$rel1 		= $habtmRels[$relName1];
		$rel2 		= $habtmRels[$relName2];

		//	test habtm binding models
		$bindingModel1 	= $rel1->getBindingModel();
		$bindingModel2 	= $rel2->getBindingModel();
		$belongsToRels1 = $bindingModel1->relations->getRelations('type', 'belongsTo');
		$belongsToRels2 = $bindingModel2->relations->getRelations('type', 'belongsTo');

		//	test homo relation that uses model names
		$this->assertTrue(array_key_exists($modelName . '1', $belongsToRels1), "Does unlabeled binding model {$bindingModel1->id} use correct rule names?");
		$this->assertTrue(array_key_exists($modelName . '2', $belongsToRels1), "Does unlabeled binding model {$bindingModel1->id} use correct rule names?");

		//	test homo relation that uses a relation label
		$this->assertTrue(array_key_exists($relName2, $belongsToRels2), "Does labeled binding model {$bindingModel2->id} use correct rule names?");
		$this->assertTrue(array_key_exists($modelName, $belongsToRels2), "Does labeled binding model {$bindingModel2->id} use correct rule names?");
	}

	public function testOppositeRelationsFromTheSameModel() {
		$modelNameA = 'Bogus';
		$modelNameB	= 'Foo';

		$relNamesFromB = array(
			'Bogus',
			'Foo2',
			'PrimaryFoo',
			'MyBigBogus'
		);

		$modelA		= $this->_modelSet[$modelNameA];
		$modelB		= $this->_modelSet[$modelNameB];

		$oppositeRels = $modelB->relations->getRelations();

		foreach ($relNamesFromB as $relName) {
			$this->assertTrue(array_key_exists($relName, $oppositeRels), "Does mirrored relation {$relName} exist in {$modelNameB}?");
		}
	}

	public function testOppositeHabtmRelationNameForCustomNamedRelations() {
		$modelNameA = 'Bogus';
		$relNameFromA = 'BogusLike';
		$modelNameB	= 'Foo';

		$modelA		= $this->_modelSet[$modelNameA];
		$modelB		= $this->_modelSet[$modelNameB];

		$relFromA = $modelA->relations->getRelation($relNameFromA);

		$this->assertNotNull($relFromA);
		$this->assertEquals($relNameFromA, $relFromA->oppositeRule);
	}

	
	protected function _constructMockModelSet() {
		$config = new Garp_Spawn_Config_Model_Set(
			new Garp_Spawn_Config_Storage_File($this->_mocks['directory'], $this->_mocks['extension']),
			new Garp_Spawn_Config_Format_Json
		);
		
		return Garp_Spawn_Model_Set::getInstance($config);
	}
}