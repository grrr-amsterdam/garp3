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
				$readModel = instance('Model_' . $datatype);
				if ($i18n) {
					$readModel = instance(new Garp_I18n_ModelFactory)->getModel($readModel);
				}
				$primary = instance('Model_' . $datatype)->insert($data);
				$mockData[$datatype][$i] = $this->_fetchFreshData($readModel, $primary)->toArray();
			}
		}
	}

	protected function _truncate($mockData) {
		foreach ($mockData as $datatype => $mockData) {
			$model = instance('Model_' . $datatype);
			$model->getAdapter()->query('SET foreign_key_checks=0;');
			$model->getAdapter()->query('TRUNCATE TABLE ' . $model->getName());
		}
	}

	protected function _fetchFreshData($model, $primaryKey) {
		if (!is_array($primaryKey)) {
			return $model->find($primaryKey)->current();
		}
		$select = $model->select();
		foreach ($primaryKey as $column => $value) {
			$select->where("{$column} = ?", $value);
		}
		return $model->fetchRow($select);
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
