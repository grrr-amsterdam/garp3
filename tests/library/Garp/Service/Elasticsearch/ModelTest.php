<?php
/**
 * @author David Spreekmeester | Grrr.nl
 * This class tests Garp_Service_Elasticsearch_Model
 * @group Elasticsearch
 */
class Garp_Service_Elasticsearch_ModelTest extends PHPUnit_Framework_TestCase {
	const BOGUS_MODEL_NAME = 'Bogus';
	/**
	 * @var Array $_bogusData
	 */
	protected $_bogusData = array(
		'id' 			=> 666,
		'name' 			=> 'Bogus name',
		'description' 	=> 'Bogus description'
	);
	
	/**
	 * @var Garp_Service_Elasticsearch_Model $_model
	 */
	protected $_model;
	

	public function setUp() {
		$model = new Garp_Service_Elasticsearch_Model(self::BOGUS_MODEL_NAME);
		$this->setModel($model);
	}

	public function testSavingRecordShouldBeFetchable() {
		$bogusData = $this->getBogusData();
		$this->_createBogusRecord();

		$data = $this->_fetchBogusRecord();

		$this->assertTrue($data['exists'], 'Does the bogus record exist?');
		$this->assertEquals($data['_source']['name'], $bogusData['name'], 'Does the stored name equal the bogus data?');

		$this->_deleteBogusRecord();

		$data = $this->_fetchBogusRecord();
		$this->assertFalse($data['exists'], 'Is the bogus record actually removed?');
	}

	/**
	 * @return Garp_Service_Elasticsearch_Model
	 */
	public function getModel() {
		return $this->_model;
	}
	
	/**
	 * @param Garp_Service_Elasticsearch_Model $model
	 */
	public function setModel(Garp_Service_Elasticsearch_Model $model) {
		$this->_model = $model;
		return $this;
	}

	/**
	 * @return Array
	 */
	public function getBogusData() {
		return $this->_bogusData;
	}
	
	protected function _createBogusRecord() {
		$bogusData 	= $this->getBogusData();

		$model = $this->getModel();
		$model->save($bogusData);
	}

	protected function _fetchBogusRecord() {
		$bogusData 	= $this->getBogusData();
		$bogusId 	= $bogusData['id'];

		$model 		= $this->getModel();
		$record 	= $model->fetch($bogusId);

		return $record;
	}

	protected function _deleteBogusRecord() {
		$bogusData 	= $this->getBogusData();
		$bogusId 	= $bogusData['id'];

		$model 		= $this->getModel();
		$model->delete($bogusId);
	}

}
