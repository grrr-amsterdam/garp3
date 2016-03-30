<?php
/**
 * Garp_Test_PHPUnit_Helper
 * Collection of handy unit testing helper methods.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Garp_Test_PHPUnit
 */
class Garp_Test_PHPUnit_Helper {
	/**
 	 * setUp and tearDown are called by Garp_Test_PHPUnit_TestCase and
 	 * Garp_Test_PHPUnit_ControllerTestCase.
 	 * First they reset the global state as much as possible.
 	 */
	public function setUp(array &$mockData) {
		Garp_Auth::getInstance()
			->setStore(new Garp_Store_Array('Garp_Auth'))
			->destroy();
		Garp_Cache_Manager::purge();
		Garp_Model_Db_BindingManager::destroyAllBindings();

		$this->_insertMockData($mockData);
	}

	public function tearDown(array $mockData) {
		$this->_truncate($mockData);
	}

	protected function _insertMockData(array &$mockData) {
		foreach ($mockData as $datatype => $data) {
			$i18n = isset($data['i18n']) && $data['i18n'];
			unset($data['i18n']);
			foreach ($data as $i => $data) {
				$readModel = $saveModel = instance('Model_' . $datatype);
				if ($i18n) {
					$readModel = instance(new Garp_I18n_ModelFactory)->getModel($readModel);
				}
				$saveModel->unregisterObserver('ImageScalable');
				$primary = $saveModel->insert($data);
				$mockData[$datatype][$i] = $this->_fetchFreshData($readModel, $primary)->toArray();
			}
		}
	}

	protected function _truncate($mockData) {
		foreach ($mockData as $datatype => $mockData) {
			$model = instance('Model_' . $datatype);
			$model->getAdapter()->query('SET foreign_key_checks=0;');
			$model->getAdapter()->query('TRUNCATE TABLE ' . $model->getName());
			if (array_key_exists('i18n', $mockData)) {
				$modelI18n = instance('Model_' . $datatype .
					Garp_Model_Behavior_Translatable::I18N_MODEL_SUFFIX);
				$model->getAdapter()->query('TRUNCATE TABLE ' . $modelI18n->getName());
			}
		}
	}

	protected function _fetchFreshData($model, $primaryKey) {
		// Make sure data is fresh, Draftable would block offline items, but that's not really what
		// we want here.
		// Since the observer is restored tests can still test for Draftable particulars.
		if ($draftableBehavior = $model->getObserver('Draftable')) {
			$model->unregisterObserver('Draftable');
		}
		if (!is_array($primaryKey)) {
			return $model->find($primaryKey)->current();
		}
		$select = $model->select();
		foreach ($primaryKey as $column => $value) {
			$select->where("{$column} = ?", $value);
		}
		$row = $model->fetchRow($select);
		if ($draftableBehavior) {
			$model->registerObserver($draftableBehavior);
		}
		return $row;
	}

	/**
 	 * Force certain config values @ runtime
 	 * @param Array $dynamicConfig
 	 * @return Void
 	 */
	public function injectConfigValues(array $dynamicConfig) {
		$config = Zend_Registry::get('config');
		// Very sneakily bypass 'readOnly'
		if ($config->readOnly()) {
			$config = new Zend_Config($config->toArray(), APPLICATION_ENV, true);
		}
		$config->merge(new Zend_Config($dynamicConfig));
		$config->setReadOnly();

		Zend_Registry::set('config', $config);
	}

	/**
 	 * Login the given user
 	 * @param Array $userData
 	 */
	public function login(array $userData) {
		Garp_Auth::getInstance()->store($userData);
	}

}
