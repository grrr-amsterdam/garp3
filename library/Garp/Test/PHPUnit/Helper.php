<?php
use Garp\Functional as f;

/**
 * Garp_Test_PHPUnit_Helper
 * Collection of handy unit testing helper methods.
 *
 * @package Garp_Test_PHPUnit
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Test_PHPUnit_Helper {
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;

    protected $_profiler;

    /**
     * Mockdata inserted at runtime thru $this->insertMockData
     *
     * @var array
     */
    protected $_dynamicallyInsertedMockData = array();

    /**
     * The methods setUp and tearDown are called by Garp_Test_PHPUnit_TestCase and
     * Garp_Test_PHPUnit_ControllerTestCase.
     * First they reset the global state as much as possible.
     *
     * @param array $mockData
     * @return void
     */
    public function setUp(array &$mockData) {
        Garp_Auth::getInstance()
            ->setStore(new Garp_Store_Array('Garp_Auth'))
            ->destroy();
        Garp_Cache_Manager::purge();
        Garp_Model_Db_BindingManager::destroyAllBindings();

        $this->_prepareDatabaseProfiler();
        $this->_insertPredefinedMockData($mockData);
    }

    public function tearDown(array $mockData) {
        if ($this->_profiler) {
            $this->_truncateFromProfiler();
            $this->_profiler->clear();
        } else {
            $this->_truncate($mockData);
            $this->_truncate($this->_dynamicallyInsertedMockData);
        }
    }

    /**
     * Get database adapter for executing queries quickly.
     * It will be configured as defined in application.ini.
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function getDatabaseAdapter() {
        if (!$this->_db) {
            $this->_db = Zend_Db_Table::getDefaultAdapter();
        }
        return $this->_db;
    }

    /**
     * Insert mockdata for the given model, as generated by Faker.
     *
     * @param Garp_Model_Db $model       The subject model.
     * @param array         $defaultData Overwrite some fake data with constants of your own.
     * @return int The primary key of the newly inserted data
     */
    public function insertMockData(Garp_Model_Db $model, array $defaultData = array()) {
        $data = $model->getDataFactory()->make($defaultData);
        $modelSuffix = $model->getNameWithoutNamespace();
        if (!array_key_exists($modelSuffix, $this->_dynamicallyInsertedMockData)) {
            $this->_dynamicallyInsertedMockData[$modelSuffix] = array(
                'i18n' => $model->isMultilingual()
            );
        }
        return $model->insert($data);
    }

    /**
     * Inserts the mockdata that can be specified as property of the test class.
     *
     * @param array $mockData
     * @return void
     */
    protected function _insertPredefinedMockData(array &$mockData) {
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

    protected function _truncateFromProfiler() {
        $tables = $this->_getTablesFromProfiler();
        $dbAdapter = $this->getDatabaseAdapter();

        $this->_setForeignKeyChecks($dbAdapter, 0);

        foreach ($tables as $table) {
            $dbAdapter->query("TRUNCATE TABLE {$table}");
        }

        $this->_setForeignKeyChecks($dbAdapter, 1);
    }

    protected function _truncate($mockData) {
        foreach ($mockData as $datatype => $mockData) {
            $i18n = isset($data['i18n']) && $data['i18n'];
            $model = instance('Model_' . $datatype);
            $model->getAdapter()->query('SET foreign_key_checks=0;');
            $model->getAdapter()->query('TRUNCATE TABLE ' . $model->getName());
            if ($i18n) {
                $modelI18n = instance(
                    'Model_' . $datatype . Garp_Model_Behavior_Translatable::I18N_MODEL_SUFFIX
                );
                $model->getAdapter()->query('TRUNCATE TABLE ' . $modelI18n->getName());
            }
            $model->getAdapter()->query('SET foreign_key_checks=1;');
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
     * Force certain config values at runtime
     *
     * @param array $dynamicConfig
     * @return void
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
     *
     * @param array $userData
     * @return void
     */
    public function login(array $userData) {
        Garp_Auth::getInstance()->store($userData);
    }

    protected function _prepareDatabaseProfiler() {
        $db = $this->getDatabaseAdapter();
        if (!$db) {
            return;
        }
        $profiler = $db->getProfiler();
        if (!$profiler) {
            return;
        }
        $this->_profiler = $profiler;
        $this->_profiler->setFilterQueryType(Zend_Db_Profiler::INSERT);
    }

    protected function _getTablesFromProfiler(): array {
        $profiles = $this->_profiler->getQueryProfiles();
        if (!$profiles) {
            return [];
        }
        $parser = new PHPSQLParser\PHPSQLParser();
        return f\unique(
            f\reduce(
                function ($tableNames, $profile) use ($parser) {
                    $parsed = $parser->parse($profile->getQuery());
                    $insertParts = f\prop('INSERT', $parsed) ?: [];
                    $tables = f\filter(f\prop_equals('expr_type', 'table'), $insertParts);
                    return f\concat(
                        $tableNames,
                        f\map(f\prop('table'), $tables)
                    );
                },
                [],
                $profiles
            )
        );
    }

    protected function _setForeignKeyChecks(Zend_Db_Adapter_Abstract $dbAdapter, int $setting) {
        if ($dbAdapter instanceof Zend_Db_Adapter_Pdo_Mysql) {
            $dbAdapter->query(sprintf('SET foreign_key_checks=%d;', $setting));
        }
    }
}
