<?php
use Garp_Spawn_Db_Schema_Interface as SchemaInterface;

/**
 * Generate and alter tables to reflect base models and association models
 *
 * @package Garp_Spawn_Db
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Spawn_Db_Manager {
    const ERROR_CANT_CREATE_TABLE = "Unable to create the %s table.";
    const CUSTOM_SQL_PATH = '/data/sql/spawn.sql';
    const CUSTOM_SQL_SHELL_COMMAND = "mysql -u'%s' -p'%s' -D'%s' --host='%s' < %s";
    const MSG_INITIALIZING = "Initializing database...";
    const MSG_FINALIZING = "√ Done";

    /**
     * Singleton instance
     *
     * @var Garp_Spawn_Db_Manager
     */
    private static $_instance = null;

    /**
     * Whether the feedback mode is interactive (progress bar) or not (batch mode).
     *
     * @var bool
     */
    protected $_interactive = true;

    /**
     * @var Garp_Spawn_Db_Schema_Interface
     */
    protected $_schema;

    /**
     * @var Garp_Cli_Protocol
     */
    protected $_feedback;

    /**
     * @var string
     */
    protected $_priorityModel = 'User';

    /**
     * Private constructor. Here be Singletons.
     *
     * @param  Garp_Spawn_Db_Schema_Interface $schema
     * @param  Garp_Cli_Protocol              $feedback
     * @return void
     */
    private function __construct(SchemaInterface $schema, Garp_Cli_Ui_Protocol $feedback) {
        $this->_schema = $schema;
        $this->_feedback = $feedback;
    }

    public static function getInstance(
        SchemaInterface $schema,
        Garp_Cli_Ui_Protocol $feedback = null
    ): Garp_Spawn_Db_Manager {
        if (!static::$_instance) {
            static::$_instance = new static($schema, $feedback);
        }
        return static::$_instance;
    }

    /**
     * @param Garp_Spawn_Model_Set  $modelSet The model set to model the database after.
     * @return void
     */
    public function run(Garp_Spawn_Model_Set $modelSet) {
        $totalActions = count($modelSet) * 5;
        $progress = $this->_getFeedbackInstance();
        $progress->init($totalActions);
        $progress->display(self::MSG_INITIALIZING);

        $this->_schema->enforceUtf8();

        //  Stage 0: Remove all generated views________
        Garp_Spawn_Db_View_Joint::deleteAll();
        Garp_Spawn_Db_View_I18n::deleteAll();

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
     * This method is called by Garp_Spawn_Db_Table_Base when that happens.
     *
     * @param Garp_Spawn_Db_Table_Base $model
     * @return void
     */
    public function onI18nTableFork(Garp_Spawn_Model_Base $model) {
        new Garp_Spawn_Db_I18nForker($model, $this->_feedback);
    }

    /**
     * @param bool $interactive Whether interactive feedback mode should be enabled.
     * @return void
     */
    public function setInteractive(bool $interactive) {
        $this->_interactive = $interactive;
    }

    public function getInteractive(): bool {
        return $this->_interactive;
    }

    /**
     * @param Garp_Cli_Ui_Protocol $feedback
     * @return void
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
            : Garp_Cli_Ui_BatchOutput::getInstance();
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

        $tableFactory   = new Garp_Spawn_Db_Table_Factory($model);
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
     * Creates a MySQL view for every base model, that also fetches the labels of
     * related hasOne / belongsTo records.
     *
     * @param Garp_Spawn_Model_Base $model
     * @return void
     */
    protected function _createJointView(Garp_Spawn_Model_Base $model) {
        $view = new Garp_Spawn_Db_View_Joint($model);
        $view->create();
    }

    protected function _createI18nViews(Garp_Spawn_Model_Base $model) {
        $locales = Garp_I18n::getLocales();
        foreach ($locales as $locale) {
            $view = new Garp_Spawn_Db_View_I18n($model, $locale);
            $view->create();
        }
    }

    protected function _createBindingModelTableIfNotExists(Garp_Spawn_Relation $relation) {
        $bindingModel = $relation->getBindingModel();
        $tableFactory = new Garp_Spawn_Db_Table_Factory($bindingModel);
        $configTable  = $tableFactory->produceConfigTable();
        $this->_createTableIfNotExists($configTable);
    }

    protected function _syncBaseModel(Garp_Spawn_Model_Base $model) {
        $progress = $this->_getFeedbackInstance();
        $progress->display($model->id . " table comparison");

        $baseSynchronizer = new Garp_Spawn_Db_Table_Synchronizer($model, $progress);
        $baseSynchronizer->sync(false);
    }

    protected function _cleanUpBaseModel(Garp_Spawn_Model_Base $model) {
        $progress = $this->_getFeedbackInstance();
        $progress->display($model->id . " table comparison");

        $baseSynchronizer = new Garp_Spawn_Db_Table_Synchronizer($model, $progress);
        $baseSynchronizer->cleanUp();
    }

    protected function _syncI18nModel(Garp_Spawn_Model_Base $model) {
        if (!$model->isMultilingual()) {
            return;
        }

        $progress = $this->_getFeedbackInstance();
        $progress->display($model->id . " i18n comparison");

        $i18nModel    = $model->getI18nModel();
        $synchronizer = new Garp_Spawn_Db_Table_Synchronizer($i18nModel, $progress);
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

        $synchronizer = new Garp_Spawn_Db_Table_Synchronizer($bindingModel, $progress);
        $synchronizer->sync();
    }

    protected function _createTableIfNotExists(Garp_Spawn_Db_Table_Abstract $table) {
        if (Garp_Spawn_Db_Table_Base::exists($table->name)) {
            return;
        }
        $progress = $this->_getFeedbackInstance();
        $progress->display($table->name . " table creation");
        if (!$table->create()) {
            $error = sprintf(self::ERROR_CANT_CREATE_TABLE, $table->name);
            throw new Exception($error);
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
            $db->password,
            $db->dbname,
            $db->host,
            $path
        );

        `$readSqlCommand`;
    }
}
