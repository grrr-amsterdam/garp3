<?php
/**
 * This class tests Garp_Service_Elasticsearch_Model
 *
 * @package Tests
 * @author  David Spreekmeester <david@grrr.nl>
 * @group   Elasticsearch
 */
class Garp_Service_Elasticsearch_ModelTest extends Garp_Test_PHPUnit_TestCase {
    const BOGUS_MODEL_NAME = 'ElasticsearchBogus';
    /**
     * @var Array $_bogusData
     */
    protected $_bogusData = array(
        'id'            => 666,
        'name'          => 'Bogus name',
        'description'   => 'Bogus description'
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
        // only test ElasticSearch in a project that uses ElasticSearch
        if (!isset(Zend_Registry::get('config')->elasticsearch)) {
            return;
        }
        $bogusData = $this->getBogusData();
        $this->_createBogusRecord();

        $data = $this->_fetchBogusRecord();

        $this->assertTrue($data['exists'], 'Does the bogus record exist?');
        $this->assertEquals(
            $data['_source']['name'],
            $bogusData['name'],
            'Does the stored name equal the bogus data?'
        );

        $this->_deleteBogusRecord();

        $recordExists = false;
        try {
            $data = $this->_fetchBogusRecord();
            $recordExists = $data['exists'];
        } catch (Exception $e) {
        }

        $this->assertFalse($recordExists, 'Is the bogus record actually removed?');
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
        $bogusData  = $this->getBogusData();

        $model = $this->getModel();
        $model->save($bogusData);
    }

    protected function _fetchBogusRecord() {
        $bogusData  = $this->getBogusData();
        $bogusId    = $bogusData['id'];

        $model      = $this->getModel();
        $record     = $model->fetch($bogusId);

        return $record;
    }

    protected function _deleteBogusRecord() {
        $bogusData  = $this->getBogusData();
        $bogusId    = $bogusData['id'];

        $model      = $this->getModel();
        $model->delete($bogusId);
    }

}
