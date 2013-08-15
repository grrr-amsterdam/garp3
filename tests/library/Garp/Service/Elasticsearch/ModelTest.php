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

		$model = $this->getModel();
		$model->save($bogusData);

		// $this->assertTrue(!empty($baseUrl), 'Does configuration.baseUrl have a value?');
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
	

}
