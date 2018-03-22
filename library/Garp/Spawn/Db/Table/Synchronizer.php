<?php
/**
 * Garp_Spawn_Db_Table_Synchronizer
 *
 * @package Garp_Spawn_Db_Table
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Spawn_Db_Table_Synchronizer {
    /**
     * @var Garp_Spawn_Db_Table_Abstract
     */
    protected $_source;

    /**
     * @var Garp_Spawn_Db_Table_Abstract
     */
    protected $_target;

    /**
     * @var Garp_Spawn_Model_Abstract
     */
    protected $_model;

    /**
     * @var Garp_Cli_Ui_Protocol
     */
    protected $_feedback;

    /**
     * @var Garp_Spawn_Db_Schema_Interface
     */
    protected $_schema;

    /**
     * @param  Garp_Spawn_Model_Abstract      $model
     * @param  Garp_Spawn_Db_Schema_Interface $schema
     * @param  Garp_Cli_Ui_Protocol           $feedback
     * @return void
     */
    public function __construct(
        Garp_Spawn_Model_Abstract $model,
        Garp_Spawn_Db_Schema_Interface $schema,
        Garp_Cli_Ui_Protocol $feedback
    ) {
        $this->_schema = $schema;
        $tableFactory  = new Garp_Spawn_Db_Table_Factory($model, $this->_schema);
        $configTable   = $tableFactory->produceConfigTable();
        $liveTable     = $tableFactory->produceLiveTable();

        $this->setSource($configTable);
        $this->setTarget($liveTable);
        $this->setModel($model);
        $this->setFeedback($feedback);
    }

    /**
     * Syncs source and target tables with one another, trying to resolve any conflicts.
     *
     * @param  bool $removeRedundantColumns Whether to remove no longer configured columns. This
     *                                      can be triggered separately with the cleanUp() method.
     * @return bool In sync
     */
    public function sync($removeRedundantColumns = true) {
        $target = $this->getTarget();
        $keysInSync = true;

        $configuredKeys = $this->_getConfiguredKeys();
        $keySyncer = new Garp_Spawn_Db_Key_Set_Synchronizer(
            $configuredKeys,
            $target->keys,
            $this->getFeedback()
        );

        if (!$keySyncer->removeKeys()) {
            $keysInSync = false;
        }

        $colsInSync = $this->_syncColumns($target);

        $i18nTableFork = $this->_detectI18nTableFork();

        if ($i18nTableFork) {
            $dbManager = Garp_Spawn_Db_Manager::getInstance($this->_feedback);
            $dbManager->onI18nTableFork($this->getModel());
        }

        if ($removeRedundantColumns) {
            $this->_deleteRedundantColumns();
        }

        if (!$keySyncer->addKeys() || !$keySyncer->modifyKeys()) {
            $keysInSync = false;
        }
        return $colsInSync && $keysInSync;
    }

    /**
     * Remove redundant columns
     *
     * @return void
     */
    public function cleanUp() {
        $this->_deleteRedundantColumns();
    }

    /**
     * @return Garp_Spawn_Db_Table_Abstract
     */
    public function getSource() {
        return $this->_source;
    }

    /**
     * @param Garp_Spawn_Db_Table_Abstract $source
     * @return void
     */
    public function setSource($source) {
        $this->_source = $source;
    }

    /**
     * @return Garp_Spawn_Db_Table_Abstract
     */
    public function getTarget() {
        return $this->_target;
    }

    /**
     * @param Garp_Spawn_Db_Table_Abstract $target
     * @return void
     */
    public function setTarget($target) {
        $this->_target = $target;
    }

    /**
     * @return Garp_Spawn_Model_Abstract
     */
    public function getModel() {
        return $this->_model;
    }

    /**
     * @param Garp_Spawn_Model_Abstract $model
     * @return void
     */
    public function setModel($model) {
        $this->_model = $model;
    }

    /**
     * @return Garp_Cli_Ui_Protocol
     */
    public function getFeedback() {
        return $this->_feedback;
    }

    /**
     * @param Garp_Cli_Protocol $feedback
     * @return void
     */
    public function setFeedback(Garp_Cli_Ui_Protocol $feedback) {
        $this->_feedback = $feedback;
    }

    protected function _detectI18nTableFork() {
        $source = $this->getSource();
        $target = $this->getTarget();
        $model  = $this->getModel();

        if (!$model->isMultilingual()) {
            return false;
        }

        $multilingualFields = $this->_getMultilingualFields();
        foreach ($multilingualFields as $field) {
            if ($target->columnExists($field->name)) {
                return true;
            }
        }

        return false;
    }

    protected function _getMultilingualFields() {
        return $this->getModel()->fields->getFields('multilingual', true);
    }

    protected function _syncColumns() {
        $source = $this->getSource();
        $target = $this->getTarget();
        $sync   = false;

        if ($source === $target) {
            return true;
        }
        if ($source->columns != $target->columns) {
            $this->_resolveColumnConflicts();
        }
        return true;
    }

    protected function _resolveColumnConflicts() {
        $source = $this->getSource();
        $target = $this->getTarget();

        foreach ($source->columns as $sourceColumn) {
            $target->columnExists($sourceColumn) ?
                $this->_alterColumn($sourceColumn, $target) :
                $target->addColumn($sourceColumn);
        }
    }

    protected function _alterColumn(Garp_Spawn_Db_Column $sourceColumn) {
        $diffProperties = $this->_getDiffProperties($sourceColumn);
        $target         = $this->getTarget();

        if (!$diffProperties) {
            return;
        }

        $target->disableFkChecks();
        $this->_ifNullableChangesThenDeleteForeignKeys($sourceColumn, $diffProperties);
        $target->alterColumn($sourceColumn);
        $target->enableFkChecks();
    }

    protected function _deleteRedundantColumns() {
        $target = $this->getTarget();

        foreach ($target->columns as $targetColumn) {
            $this->_deleteNoLongerConfiguredColumn($targetColumn);
        }
    }

    protected function _deleteNoLongerConfiguredColumn(Garp_Spawn_Db_Column $targetColumn) {
        $source   = $this->getSource();
        $target   = $this->getTarget();
        $progress = Garp_Cli_Ui_ProgressBar::getInstance();

        if ($source->columnExists($targetColumn->name)) {
            return;
        }

        if ($this->getFeedback()->isInteractive()) {
            $progress->display("Delete column {$target->name}.{$targetColumn->name}? ");
            if (!Garp_Spawn_Util::confirm()) {
                return;
            }
        }

        $target->deleteColumn($targetColumn);
    }

    protected function _ifNullableChangesThenDeleteForeignKeys(
        Garp_Spawn_Db_Column $sourceColumn, array $diffProperties
    ) {
        $source = $this->getSource();

        if (!in_array('nullable', $diffProperties)) {
            return;
        }
        foreach ($source->keys->foreignKeys as $fk) {
            if ($fk->localColumn === $sourceColumn->name) {
                Garp_Spawn_Db_ForeignKey::delete($source->name, $fk);
                $source->keys->droppedForeignKeyNamesDuringColumnSync[] = $fk->name;
                break;
            }
        }
    }

    protected function _getDiffProperties(Garp_Spawn_Db_Column $sourceColumn) {
        $target         = $this->getTarget();
        $targetColumn   = $target->getColumn($sourceColumn->name);
        $diffProperties = $sourceColumn->getDiffProperties($targetColumn);

        return $diffProperties;
    }

    /**
     * @return Garp_Spawn_Db_Key_Set
     */
    protected function _getConfiguredKeys() {
        $model                = $this->getModel();
        $source               = $this->getSource();
        $createStatementLines = explode("\n", $source->getCreateStatement());

        $keys = $this->_isBindingModel($model) ?
            new Garp_Spawn_Db_Key_Set(
                $createStatementLines,
                $source->name,
                $model
            ) :
            $source->keys;

        return $keys;
    }

    protected function _isBindingModel(Garp_Spawn_Model_Abstract $model) {
        return get_class($model) === 'Garp_Spawn_Model_Binding';
    }

}
