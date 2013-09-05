<?php
/**
 * @author David Spreekmeester | Grrr.nl
 * This class tests Garp_Service_Elasticsearch_Model
 * @group Elasticsearch
 */
class Garp_Model_Behavior_ElasticsearchableTest extends Garp_Test_PHPUnit_TestCase {
	const BEHAVIOR_NAME 		= 'Elasticsearchable';
	const TEST_MODEL_NAME 		= 'ElasticsearchBogus';
	const TEST_MODEL_NAMESPACE 	= 'Mocks_Model_';
	const MOCK_ID 				= '666';

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

		$this->_createTable();
	}

	public function tearDown() {
		$this->_dropTable();
	}

	public function testAfterCreateShouldCreateElasticDocument() {
		$elasticModel 	= $this->getElasticModel();

		$dbId = $this->_insertBogusRecord();

		$elasticRow 	= $elasticModel->fetch($dbId);
		$question 		= 'Is the bogus record present after insertion?';
		$this->assertTrue($elasticRow['exists'], $question);

		$elasticData 	= $elasticRow['_source'];
		$overlap 		= array_intersect($this->_mockRowData, $elasticData);
		$question 		= 'Does the indexed data overlap with mocks?';
		$this->assertGreaterThanOrEqual(count($this->_mockRowData), count($overlap), $question);

		$this->_deleteBogusRecord($dbId);

		$elasticRow = $elasticModel->fetch($dbId);
		$this->assertFalse($elasticRow['exists'], 'Is the bogus record cleaned up?');
	}

	protected function _insertBogusRecord() {
		$model 	= $this->getGarpModel();
		$id 	= $model->insert($this->_mockRowData);
		return $id;
	}

	protected function _deleteBogusRecord($dbId) {
		$model 		= $this->getGarpModel();
		$dbAdapter 	= $this->getDatabaseAdapter();
		$where 		= 'id = ' . $dbId;
		$model->delete($where);
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
		$modelName 	= self::TEST_MODEL_NAMESPACE . self::TEST_MODEL_NAME;
		$model 		= new $modelName();
		return $model;
	}

	protected function _initElasticModel() {
		$model = new Garp_Service_Elasticsearch_Model(self::TEST_MODEL_NAME);
		return $model;
	}

	protected function _createTable() {
		$dbAdapter 	= $this->getDatabaseAdapter();
		$dbName 	= $this->getGarpModel()->getName();

		$this->_dropTable();
		$createStatement = $this->_getCreateTableStatement();

		$dbAdapter->query($createStatement);
	}

	protected function _getCreateTableStatement() {
		$dbName = $this->getGarpModel()->getName();
		$columns = array_keys($this->_mockRowData);

		$create = "CREATE TABLE `$dbName`(
			`id` int UNSIGNED NOT NULL AUTO_INCREMENT,";

		foreach ($columns as $column) {
			$create .= "`$column` varchar(255),";
		}

		$create .= "PRIMARY KEY (`id`)) ENGINE=`InnoDB`;";

		return $create;
	}

	protected function _dropTable() {
		$dbAdapter = $this->getDatabaseAdapter();
		$dbName = $this->getGarpModel()->getName();
		$dbAdapter->query("DROP TABLE IF EXISTS `{$dbName}`;");
	}
}
