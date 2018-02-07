<?php
/**
 * Generate and alter tables to reflect base models and association models
 *
 * @package Garp_Spawn_MySql
 * @author David Spreekmeester <david@grrr.nl>
 */
class Garp_Spawn_MySql_Manager extends Garp_Spawn_Db_Manager_Abstract {

    const ERROR_CANT_CREATE_TABLE = "Unable to create the %s table.";
    const CUSTOM_SQL_PATH = '/data/sql/spawn.sql';
    const CUSTOM_SQL_SHELL_COMMAND = "mysql -u'%s' -p'%s' -D'%s' --host='%s' < %s";
    const CUSTOM_SQL_PASSWORD_ARG = "-p'%s'";


    /**
     * When multilingual columns are spawned, either in a new table or an existing one,
     * content from the unilingual table should be moved to the multilingual leaf records.
     * This method is called by Garp_Spawn_MySql_Table_Base when that happens.
     *
     * @param Garp_Spawn_Model_Base $model
     * @return void
     */
    public function onI18nTableFork(Garp_Spawn_Model_Base $model) {
        new Garp_Spawn_MySql_I18nForker($model, $this->_feedback);
    }

    protected function _init() {
        $adapter = Zend_Db_Table::getDefaultAdapter();
        $adapter->query('SET NAMES utf8;');
    }

    protected function _deleteExistingViews() {
        Garp_Spawn_MySql_View_Joint::deleteAll();
        Garp_Spawn_MySql_View_I18n::deleteAll();
    }

    protected function _createBaseModelTableAndAdvance(Garp_Spawn_Model_Base $model) {
        $progress = $this->_getFeedbackInstance();
        $progress->display($model->id . " base table");
        $this->_createBaseModelTableIfNotExists($model);
        $progress->advance();
    }

    protected function _createBaseModelTableIfNotExists(Garp_Spawn_Model_Base $model) {
        $progress = $this->_getFeedbackInstance();
        $progress->display($model->id . " SQL render.");

        $tableFactory   = new Garp_Spawn_MySql_Table_Factory($model);
        $configTable    = $tableFactory->produceConfigTable();
        $this->_createTableIfNotExists($configTable);

        if ($model->isMultilingual()) {
            $i18nModel = $model->getI18nModel();
            $tableFactory->setModel($i18nModel);
            $i18nTable = $tableFactory->produceConfigTable($i18nModel);
            $this->_createTableIfNotExists($i18nTable);
        }
    }

    /**
     * Creates a MySQL view for every base model, that also fetches the labels of related hasOne / belongsTo records.
     *
     * @param Garp_Spawn_Model_Base $model
     * @return void
     */
    protected function _createJointView(Garp_Spawn_Model_Base $model) {
        $view = new Garp_Spawn_MySql_View_Joint($model);
        $view->create();
    }

    protected function _createI18nViews(Garp_Spawn_Model_Base $model) {
        $locales = Garp_I18n::getLocales();
        foreach ($locales as $locale) {
            $view = new Garp_Spawn_MySql_View_I18n($model, $locale);
            $view->create();
        }
    }

    protected function _createBindingModelTableIfNotExists(Garp_Spawn_Relation $relation) {
        $bindingModel   = $relation->getBindingModel();

        $tableFactory   = new Garp_Spawn_MySql_Table_Factory($bindingModel);
        $configTable    = $tableFactory->produceConfigTable();
        $this->_createTableIfNotExists($configTable);
    }

    protected function _syncBaseModel(Garp_Spawn_Model_Base $model) {
        $progress = $this->_getFeedbackInstance();
        $progress->display($model->id . " table comparison");

        $baseSynchronizer = new Garp_Spawn_MySql_Table_Synchronizer($model, $progress);
        $baseSynchronizer->sync(false);
    }

    protected function _cleanUpBaseModel(Garp_Spawn_Model_Base $model) {
        $progress = $this->_getFeedbackInstance();
        $progress->display($model->id . " table comparison");

        $baseSynchronizer = new Garp_Spawn_MySql_Table_Synchronizer($model, $progress);
        $baseSynchronizer->cleanUp();
    }

    protected function _syncI18nModel(Garp_Spawn_Model_Base $model) {
        if (!$model->isMultilingual()) {
            return;
        }

        $progress = $this->_getFeedbackInstance();
        $progress->display($model->id . " i18n comparison");

        $i18nModel      = $model->getI18nModel();
        $synchronizer   = new Garp_Spawn_MySql_Table_Synchronizer($i18nModel, $progress);
        $synchronizer->sync();

        try {
            $this->onI18nTableFork($model);
        } catch (Exception $e) {
        }
    }

    protected function _syncBindingModel(Garp_Spawn_Relation $relation) {
        $progress = $this->_getFeedbackInstance();
        $bindingModel = $relation->getBindingModel();
        $progress->display($bindingModel->id . " table comparison");

        $synchronizer = new Garp_Spawn_MySql_Table_Synchronizer($bindingModel, $progress);
        $synchronizer->sync();
    }

    protected function _createTableIfNotExists(Garp_Spawn_MySql_Table_Abstract $table) {
        if (!Garp_Spawn_MySql_Table_Base::exists($table->name)) {
            $progress = $this->_getFeedbackInstance();
            $progress->display($table->name . " table creation");
            if (!$table->create()) {
                $error = sprintf(self::ERROR_CANT_CREATE_TABLE, $table->name);
                throw new Exception($error);
            }
        }
    }

    protected function _executeCustomSql() {
        $path = APPLICATION_PATH . self::CUSTOM_SQL_PATH;

        if (!file_exists($path)) {
            return;
        }

        $config = Zend_Registry::get('config');
        $db     = $config->resources->db->params;
        $readSqlCommand = sprintf(
            self::CUSTOM_SQL_SHELL_COMMAND,
            $db->username,
            !empty($db->password) ? sprintf(SELF::CUSTOM_SQL_PASSWORD_ARG, $db->password) : '',
            $db->dbname,
            $db->host,
            $path
        );

        `$readSqlCommand`;
    }
}
