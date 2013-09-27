<?php
/**
 * Garp_Cli_Command_Elasticsearch
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class Garp_Cli_Command_Elasticsearch extends Garp_Cli_Command {
	const BEHAVIOR_NAME = 'Elasticsearchable';

	/**
	 * @var Garp_Service_Elasticsearch $_service
	 */
	protected $_service;
	

	public function main(array $args = array()) {
		$this->setService($this->_initService());
		parent::main($args);
	}

	/**
	 * Prepares an index for storing and searching.
	 */
	public function prepare() {
		Garp_Cli::lineOut('Preparing Elasticsearch index...');
		
		$service 	= $this->getService();
		$log 		= $service->prepare();

		Garp_Cli::lineOut($log, Garp_Cli::BLUE);
	}

	/**
	 * Pushes all appropriate existing database content to the indexer.
	 */
	public function index() {
		$modelSet = Garp_Spawn_Model_Set::getInstance();

		foreach ($modelSet as $model) {
			$this->_indexModel($model);
		}
	}

	public function help() {
		Garp_Cli::lineOut('# Usage');
		Garp_Cli::lineOut('Create a new index:');
		Garp_Cli::lineOut('  g elasticsearch prepare', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
	}

	/**
	 * @return Garp_Service_Elasticsearch
	 */
	public function getService() {
		return $this->_service;
	}
	
	/**
	 * @param Garp_Service_Elasticsearch $service
	 */
	public function setService(Garp_Service_Elasticsearch $service) {
		$this->_service = $service;
		return $this;
	}

	protected function _initService() {
		$service = new Garp_Service_Elasticsearch();
		return $service;
	}

	protected function _indexModel(Garp_Spawn_Model_Abstract $model) {
		if (!$this->_isElasticsearchable($model)) {
			return;
		}

		$phpModel 		= $this->_getPhpModel($model);
		$phpBehavior	= $this->_getPhpBehavior($phpModel);

		if (!$phpBehavior->getRootable()) {
			return;
		}

		$records = $this->_fetchAllIds($phpModel);

		foreach ($records as $record) {
			$this->_pushRecord($phpModel, $phpBehavior, $record);
		}

		$report = sprintf('Indexed %d %s records', count($records), $model->id);
		Garp_Cli::lineOut($report);
	}

	protected function _isElasticsearchable(Garp_Spawn_Model_Abstract $model) {
		return $model->behaviors->displaysBehavior(self::BEHAVIOR_NAME);
	}

	protected function _getPhpModel(Garp_Spawn_Model_Abstract $model) {
		$modelClass 	= 'Model_' . $model->id;
		$phpModel 		= new $modelClass();
		return $phpModel;		
	}

	protected function _getPhpBehavior(Garp_Model_Db $model) {
		$phpBehavior 	= $model->getObserver(self::BEHAVIOR_NAME);
		return $phpBehavior;
	}

	protected function _fetchAllIds(Garp_Model_Db $model) {
		$fields			= array('id');
		$select 		= $model->select()->from($model->getName(), $fields);
		$records 		= $model->fetchAll($select);
		return $records;
	}

	protected function _pushRecord(Garp_Model_Db $model, Garp_Model_Behavior_Abstract $phpBehavior, Garp_Db_Table_Row $record) {
		$primaryKey = current($record->toArray());
		$phpBehavior->afterSave($model, $primaryKey);
	}
}
