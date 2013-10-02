<?php
/**
 * @author David Spreekmeester | Grrr.nl
 * This class tests Garp_Service_Elasticsearch_Model
 * @group Elasticsearch
 */
class Garp_Model_Behavior_ElasticsearchableTest extends Garp_Test_PHPUnit_TestCase {
	const BEHAVIOR_NAME 		= 'Elasticsearchable';
	const TEST_MODEL_NAMESPACE 	= 'Mocks_Model_';

	protected $_testModelNames = array(
		'ElasticsearchBogus',
		'ElasticsearchFoo'
	);

	protected $_testBindingModelName = 
		'ElasticsearchBogusElasticsearchFoo'
	;

	/**
	 * @var Array $_garpModel Numeric array of Garp_Model_Db objects
	 */
	protected $_garpModels;
	
	/**
	 * @var Garp_Service_Elasticsearch_Model $_elasticModel representing the first Garp test model
	 */
	protected $_elasticModel;
	

	public function setUp() {
		$garpModels = $this->_initGarpModels();
		$this->setGarpModels($garpModels);

		$elasticModel = $this->_initElasticModel();
		$this->setElasticModel($elasticModel);

		$this->_createTables();
	}

	public function tearDown() {
		$this->_dropTables();
	}

	public function testAfterCreateShouldCreateElasticDocument() {
		$elasticModel 			= $this->getElasticModel();
		$dbIds 					= $this->_updateBogusRecords();
		$firstGarpModelClass 	= get_class($this->getGarpModel());
		$dbId 					= $dbIds[$firstGarpModelClass];

		/* 	The Elasticsearch records should be inserted at this point,
			because of the db trigger. */

		$question 				= 'Is the bogus record present after insertion?';
		$elasticRow 			= $elasticModel->fetch($dbId);
		$this->assertTrue($elasticRow['exists'], $question);

		$question 				= 'Does the indexed data overlap with mocks?';
		$elasticData 			= $elasticRow['_source'];
		$mockRowData 			= $this->getGarpModel()->getMockRowData();
		$overlap 				= array_intersect($mockRowData, $elasticData);
		$this->assertGreaterThanOrEqual(count($mockRowData), count($overlap), $question);

		$question 				= 'Is the bogus record cleaned up?';
		$this->_deleteBogusRecords($dbIds);
		$elasticRow 			= $elasticModel->fetch($dbId);
		$this->assertFalse($elasticRow['exists'], $question);
	}

	/**
	 * @return Garp_Model_Db The HABTM binding test model
	 */
	public function getBindingModel() {
		$class = self::TEST_MODEL_NAMESPACE . $this->_testBindingModelName;
		$bindingModel 	= new $class();

		return $bindingModel;
	}

	/**
	 * @return Array List of Garp_Model_Db test objects
	 */
	public function getGarpModels() {
		return $this->_garpModels;
	}

	/**
	 * @return Garp_Model_Db The first test model
	 */
	public function getGarpModel() {
		return $this->_garpModels[0];
	}

	/**
	 * @param Garp_Model_Db $garpModel
	 */
	public function setGarpModels(array $garpModels) {
		$this->_garpModels = $garpModels;
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

	/**
	 * @return Array 	The ids of the freshly inserted records.
	 					Associative array in the following format:
						{
							"Model_Bogus": 1,
							"Model_Foo": 4
						}
	 */
	protected function _updateBogusRecords() {
		$models = $this->getGarpModels();
		$ids 	= array();

		foreach ($models as $model) {
			$mockData = $model->getMockRowData();
			$ids[get_class($model)] = $model->update($mockData, 'id = 1');
		}

		// $bindingModel = $this->getBindingModel();
		// $mockData = array(
			// 'elasticsearch_bogus_id' => $ids['Mocks_Model_ElasticsearchBogus'],
			// 'elasticsearch_foo_id' => $ids['Mocks_Model_ElasticsearchFoo'],
		// );
		// $bindingModel->insert($mockData);
		// Zend_Debug::dump(get_class($bindingModel)); exit;

		return $ids;
	}

	protected function _deleteBogusRecords($dbIds) {
		foreach ($dbIds as $modelName => $dbId) {
			$model 	= new $modelName();
			$where 	= 'id = ' . $dbId;
			$model->delete($where);
		}
	}

	protected function _initGarpModels() {
		$models = array();

		foreach ($this->_testModelNames as $modelName) {
			$className 	= self::TEST_MODEL_NAMESPACE . $modelName;
			$models[]	= new $className();
		}

		return $models;
	}

	protected function _initElasticModel() {
		$model = new Garp_Service_Elasticsearch_Model($this->_testModelNames[0]);
		return $model;
	}

	protected function _createTables() {
		$dbAdapter 	= $this->getDatabaseAdapter();

		$this->_dropTables();
		$createStatement = $this->_getCreateTablesStatement();
		$dbAdapter->query($createStatement);
	}

	/**
	 * Builds the queries needed to create the base test models and the binding table.
	 */
	protected function _getCreateTablesStatement() {
		$models = $this->getGarpModels();
		$statement = '';

		foreach ($models as $model) {
			$statement .= $model->getCreateStatement();
		}
		
		$statement .= $this->getBindingModel()->getCreateStatement();

		return $statement;
	}

	protected function _dropTables() {
		$models 	= $this->getGarpModels();
		
		foreach ($models as $model) {
			$tableName = $model->getName();
			$this->_dropTable($tableName);
		}

		$bindingModel 		= $this->getBindingModel();
		$bindingTableName 	= $bindingModel->getName();
		$this->_dropTable($bindingTableName);
	}

	protected function _dropTable($tableName) {
		$dbAdapter = $this->getDatabaseAdapter();
		$dbAdapter->query("DROP TABLE IF EXISTS `{$tableName}`;");
	}
}
