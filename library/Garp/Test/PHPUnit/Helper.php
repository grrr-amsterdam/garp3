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
 	 */
	public function setUp(array $mockData) {
		Garp_Auth::getInstance()->setStore(new Garp_Store_Array('Garp_Auth'));
		Garp_Cache_Manager::purge();

		$this->_insertMockData($mockData);
	}

	public function tearDown(array $mockData) {
		$this->_truncate($mockData);
	}

	protected function _insertMockData(array $mockData) {
		foreach ($mockData as $datatype => $mockData) {
			$i18n = isset($mockData['i18n']) && $mockData['i18n'];
			unset($mockData['i18n']);
			foreach ($mockData as $i => $data) {
				$readModel = instance('Model_' . $datatype);
				if ($i18n) {
					$readModel = instance(new Garp_I18n_ModelFactory)->getModel($readModel);
				}
				$primaryKey = instance('Model_' . $datatype)->insert($data);
				$this->_mockData[$datatype][$i] = call_user_func_array(array($readModel, 'find'),
					(array)$primaryKey)->toArray();
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
