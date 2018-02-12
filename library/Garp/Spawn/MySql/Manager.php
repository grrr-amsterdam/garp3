<?php
/**
 * Generate and alter tables to reflect base models and association models
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage MySql
 */
class Garp_Spawn_MySql_Manager {
    const ERROR_CANT_CREATE_TABLE =
        "Unable to create the %s table.";
    const CUSTOM_SQL_PATH =
        '/data/sql/spawn.sql';
    const CUSTOM_SQL_SHELL_COMMAND =
        "mysql -u'%s' %s -D'%s' --host='%s' < %s";
    const CUSTOM_SQL_PASSWORD_ARG =
        "-p'%s'";
    const MSG_INITIALIZING =
        "Initializing database...";
    const MSG_FINALIZING =
        "√ Done";

    /**
     * Singleton instance
     * @var Garp_Spawn_MySql_Manager
     */
    private static $_instance = null;

    /**
     * @var Boolean $_interactive Whether the feedback mode is interactive (progress bar) or not (batch mode).
     */
    protected $_interactive = true;

    /** @param Array $_models Array of Garp_Spawn_Model_Base objects */
    protected $_modelSet;
    protected $_adapter;

    protected $_priorityModel = 'User';

    /**
     * Garp_Cli_Protocol $_feedback
     */
    protected $_feedback;

    /**
     * Private constructor. Here be Singletons.
     * @return Void
     */
    private function __construct(Garp_Cli_Ui_Protocol $feedback) {
        $this->setFeedback($feedback);
    }

    /**
     * Get Garp_Auth instance
     * @return Garp_Auth
     */
    public static function getInstance(Garp_Cli_Ui_Protocol $feedback = null) {
         if (!Garp_Spawn_MySql_Manager::$_instance) {
             Garp_Spawn_MySql_Manager::$_instance = new Garp_Spawn_MySql_Manager($feedback);
         }

         return Garp_Spawn_MySql_Manager::$_instance;
    }

    /**
     * @param Garp_Spawn_Model_Set  $modelSet       The model set to model the database after.
     * @param Array                         &$changelist    An array of strings, describing the changes made to the database in this Spawn session.
     */
    public function run(Garp_Spawn_Model_Set $modelSet) {
        $totalActions = count($modelSet) * 5;
        $progress = $this->_getFeedbackInstance();
        $progress->init($totalActions);
        $progress->display(self::MSG_INITIALIZING);

        $this->_modelSet = $modelSet;
        $this->_adapter = Zend_Db_Table::getDefaultAdapter();
        $this->_adapter->query('SET NAMES utf8;');


        //  Stage 0: Remove all generated views________
        Garp_Spawn_MySql_View_Joint::deleteAll();
        Garp_Spawn_MySql_View_I18n::deleteAll();

        //  Stage 1: Spawn the prioritized table first________
        if (array_key_exists($this->_priorityModel, $modelSet)) {
            $this->_createBaseModelTableAndAdvance($modelSet[$this->_priorityModel]);
        }

        //  Stage 2: Create the rest of the base models' tables________
        foreach ($modelSet as $model) {
            if ($model->id !== $this->_priorityModel) {
                $this->_createBaseModelTableAndAdvance($model);
            }
        }

        //  Stage 3: Create binding models________
        foreach ($modelSet as $model) {
            $progress->display($model->id . " many-to-many config reading");
            $habtmRelations = $model->relations->getRelations('type', 'hasAndBelongsToMany');

            foreach ($habtmRelations as $relation) {
                if (strcmp($model->id, $relation->model) <= 0) {
                    //  only sync binding tables from models A -> B, not from B -> A
                    $this->_createBindingModelTableIfNotExists($relation);
                }
            }

            $progress->advance();
        }

        //  Stage 4: Sync base and binding models________
        foreach ($modelSet as $model) {
            $this->_syncBaseModel($model);

            $habtmRelations = $model->relations->getRelations('type', 'hasAndBelongsToMany');

            foreach ($habtmRelations as $relation) {
                if (strcmp($model->id, $relation->model) <= 0) {
                    //  only sync binding tables from models A -> B, not from B -> A
                    $this->_syncBindingModel($relation);
                }
            }

            $progress->advance();
        }

        foreach ($modelSet as $model) {
            $this->_syncI18nModel($model);
            $this->_cleanUpBaseModel($model);
        }

        //  Stage 5: Create base model i18n views________
        foreach ($modelSet as $model) {
            $progress->display($model->id . " i18n view");
            $this->_createI18nViews($model);
            $progress->advance();
        }

        //  Stage 6: Create base model joint views________
        foreach ($modelSet as $model) {
            $progress->display($model->id . " joint view");
            $this->_createJointView($model);
            $progress->advance();
        }

        //  Stage 7: Execute custom SQL________
        $progress->display("Executing custom SQL");
        $this->_executeCustomSql();


        $progress->display(self::MSG_FINALIZING);
    }

    /**
     * When multilingual columns are spawned, either in a new table or an existing one,
     * content from the unilingual table should be moved to the multilingual leaf records.
     * This method is called by Garp_Spawn_MySql_Table_Base when that happens.
     */
    public function onI18nTableFork(Garp_Spawn_Model_Base $model) {
        new Garp_Spawn_MySql_I18nForker($model, $this->_feedback);
    }

    /**
     * @param Boolean $interactive Whether interactive feedback mode should be enabled.
     */
    public function setInteractive($interactive) {
        $this->_interactive = $interactive;
    }

    /**
     * @return Boolean
     */
    public function getInteractive() {
        return $this->_interactive;
    }

    /**
     * @param Garp_Cli_Ui_Protocol $feedback
     */
    public function setFeedback(Garp_Cli_Ui_Protocol $feedback) {
        $this->_feedback = $feedback;
    }

    /**
     * @return Garp_Cli_Ui_Protocol
     */
    public function getFeedback() {
        return $this->_feedback;
    }

    protected function _getFeedbackInstance() {
        return $this->getInteractive()
            ? Garp_Cli_Ui_ProgressBar::getInstance()
            : Garp_Cli_Ui_BatchOutput::getInstance()
        ;
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
        } catch (Exception $e) {}
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
