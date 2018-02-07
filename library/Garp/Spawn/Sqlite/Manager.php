<?php
/**
 * @package Garp_Spawn_Sqlite
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Spawn_Sqlite_Manager extends Garp_Spawn_Db_Manager_Abstract {

    protected function _init() {
    }

    protected function _deleteExistingViews() {
        Garp_Spawn_Sqlite_View_Joint::deleteAll();
        Garp_Spawn_Sqlite_View_I18n::deleteAll();
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

        $tableFactory   = new Garp_Spawn_Sqlite_Table_Factory($model);
        $configTable    = $tableFactory->produceConfigTable();
        $this->_createTableIfNotExists($configTable);

        if ($model->isMultilingual()) {
            $i18nModel = $model->getI18nModel();
            $tableFactory->setModel($i18nModel);
            $i18nTable = $tableFactory->produceConfigTable($i18nModel);
            $this->_createTableIfNotExists($i18nTable);
        }
    }

    protected function _createBindingModelTableIfNotExists(Garp_Spawn_Relation $relation) {
        throw new Exception('not implemented yet');
    }

    protected function _syncBaseModel(Garp_Spawn_Model_Base $model) {
        throw new Exception('not implemented yet');
    }

    protected function _syncBindingModel(Garp_Spawn_Relation $relation) {
        throw new Exception('not implemented yet');
    }

    protected function _syncI18nModel(Garp_Spawn_Model_Base $model) {
        throw new Exception('not implemented yet');
    }

    protected function _cleanUpBaseModel(Garp_Spawn_Model_Base $model) {
        throw new Exception('not implemented yet');
    }

    protected function _createI18nViews(Garp_Spawn_Model_Base $model) {
        throw new Exception('not implemented yet');
    }

    protected function _createJointView(Garp_Spawn_Model_Base $model) {
        throw new Exception('not implemented yet');
    }

    protected function __executeCustomSql() {
        throw new Exception('not implemented yet');
    }


}
