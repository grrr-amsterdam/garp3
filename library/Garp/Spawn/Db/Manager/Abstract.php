<?php
/**
 * @package Garp_Spawn_Db_Manager
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
abstract class Garp_Spawn_Db_Manager_Abstract {

    const MSG_INITIALIZING = "Initializing database...";
    const MSG_FINALIZING = "√ Done";

    protected $_priorityModel = 'User';

    /**
     * Singleton instance
     *
     * @var Garp_Spawn_Db_Manager_Abstract
     */
    protected static $_instance = null;

    /**
     * @var bool Whether the feedback mode is interactive (progress bar) or not (batch mode).
     */
    protected $_interactive = true;

    /**
     * @var Garp_Cli_Protocol
     */
    protected $_feedback;

    public static function getInstance(Garp_Cli_Ui_Protocol $feedback = null): Garp_Spawn_Db_Manager_Abstract {
        if (!static::$_instance) {
            static::$_instance = new static($feedback);
        }
        return static::$_instance;
    }

    /**
     * Private constructor. Here be Singletons.
     *
     * @param Garp_Cli_Ui_Protocol $feedback
     * @return void
     */
    private function __construct(Garp_Cli_Ui_Protocol $feedback) {
        $this->setFeedback($feedback);
    }

    public function setInteractive(bool $interactive) {
        $this->_interactive = $interactive;
    }

    public function getInteractive(): bool {
        return $this->_interactive;
    }

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

    /**
     * @param Garp_Spawn_Model_Set $modelSet The model set to model the database after.
     * @return void
     */
    public function run(Garp_Spawn_Model_Set $modelSet) {
        $totalActions = count($modelSet) * 5;
        $progress = $this->_getFeedbackInstance();
        $progress->init($totalActions);
        $progress->display(self::MSG_INITIALIZING);

        $this->_init();

        //  Stage 0: Remove all generated views________
        $this->_deleteExistingViews();

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

    abstract protected function _init();

    abstract protected function _deleteExistingViews();

    abstract protected function _createBaseModelTableAndAdvance(Garp_Spawn_Model_Base $model);

    abstract protected function _createBindingModelTableIfNotExists(Garp_Spawn_Relation $relation);

    abstract protected function _syncBaseModel(Garp_Spawn_Model_Base $model);

    abstract protected function _syncBindingModel(Garp_Spawn_Relation $relation);

    abstract protected function _syncI18nModel(Garp_Spawn_Model_Base $model);

    abstract protected function _cleanUpBaseModel(Garp_Spawn_Model_Base $model);

    abstract protected function _createI18nViews(Garp_Spawn_Model_Base $model);

    abstract protected function _createJointView(Garp_Spawn_Model_Base $model);

    abstract protected function __executeCustomSql();
}
