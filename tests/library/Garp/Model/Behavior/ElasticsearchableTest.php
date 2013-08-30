<?php
/**
 * @author David Spreekmeester | Grrr.nl
 * This class tests Garp_Service_Elasticsearch_Model
 * @group Elasticsearch
 */
class Garp_Model_Behavior_ElasticsearchableTest extends PHPUnit_Framework_TestCase {
	const BEHAVIOR_NAME = 'Elasticsearchable';

	const TEST_MODEL_NAME = 'ElasticsearchBogus';

	const MOCK_ID = '666';

	/**
	 * @var Array $_mockRowData
	 */
	protected $_mockRowData = array(
		'name' => 'Unit test name',
		'description' => 'Unit test description'
	);


	/**
	 * @var Garp_Model_Db $_garpModel
	 */
	protected $_garpModel;
	
	/**
	 * @var Garp_Service_Elasticsearch_Model $_elasticModel
	 */
	protected $_elasticModel;
	

	public function setUp() {
		$garpModel = $this->_initGarpModel();
		$this->setGarpModel($garpModel);

		$elasticModel = $this->_initElasticModel();
		$this->setElasticModel($elasticModel);
	}

	public function testAfterCreateShouldCreateElasticDocument() {
		$elasticModel 	= $this->getElasticModel();

		$this->_fireAfterInsert();

		$elasticRow 	= $elasticModel->fetch(self::MOCK_ID);
		$question 		= 'Is the bogus record present after insertion?';
		$this->assertTrue($elasticRow['exists'], $question);

		$elasticData 	= $elasticRow['_source'];
		$overlap 		= array_intersect($this->_mockRowData, $elasticData);
		$question 		= 'Does the indexed data overlap with mocks?';
		$this->assertGreaterThanOrEqual(count($this->_mockRowData), count($overlap), $question);

		$elasticModel->delete(self::MOCK_ID);
		$elasticRow = $elasticModel->fetch(self::MOCK_ID);
		$this->assertFalse($elasticRow['exists'], 'Is the bogus record cleaned up?');
	}

	protected function _fireAfterInsert() {
		$garpModel = $this->getGarpModel();
		$garpModel->notifyObservers('afterInsert', array($garpModel, $this->_mockRowData, self::MOCK_ID));
	}

	/**
	 * @return Garp_Model_Db
	 */
	public function getGarpModel() {
		return $this->_garpModel;
	}
	
	/**
	 * @param Garp_Model_Db $garpModel
	 */
	public function setGarpModel($garpModel) {
		$this->_garpModel = $garpModel;
		return $this;
	}

	/**
	 * @return Garp_Service_Elasticsearch_Model
	 */
	public function getElasticModel() {
		return $this->_elasticModel;
	}
	
	/**
	 * @param Garp_Service_Elasticsearch_Model $elasticModel
	 */
	public function setElasticModel($elasticModel) {
		$this->_elasticModel = $elasticModel;
		return $this;
	}

	protected function _initGarpModel() {
		$modelName 	= 'Mocks_Model_' . self::TEST_MODEL_NAME;
		$model 		= new $modelName();
		return $model;
	}

	protected function _initElasticModel() {
		$model = new Garp_Service_Elasticsearch_Model(self::TEST_MODEL_NAME);
		return $model;
	}

}
