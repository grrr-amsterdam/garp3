<?php
/**
 * Produces a Table instance from a model.
 *
 * @package Garp_Spawn_Db_Table
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Spawn_Db_Table_Factory {
    /**
     * @var Garp_Spawn_Model_Abstract
     */
    protected $_model;

    /**
     * @var Garp_Spawn_Db_Schema_Interface
     */
    protected $_schema;

    /**
     * @param  Garp_Spawn_Model_Abstract $model
     * @param  Garp_Spawn_Db_Schema_Interface $schema
     * @return void
     */
    public function __construct(
        Garp_Spawn_Model_Abstract $model,
        Garp_Spawn_Db_Schema_Interface $schema
    ) {
        $this->setModel($model);
        $this->_schema = $schema;
    }

    /**
     * Produces a Garp_Spawn_Db_Table_Abstract instance, based on the spawn configuration.
     *
     * @return Garp_Spawn_Db_Table_Abstract
     */
    public function produceConfigTable() {
        $model = $this->getModel();
        $createStatement = $this->_renderCreateFromConfig();
        return $this->_produceTable($createStatement);
    }

    /**
     * Produces a Garp_Spawn_Db_Table_Abstract instance, based on the live database.
     *
     * @return Garp_Spawn_Db_Table_Abstract
     */
    public function produceLiveTable() {
        $model = $this->getModel();
        $createStatement = $this->_renderCreateFromLive();
        return $this->_produceTable($createStatement);
    }

    /**
     * @return Garp_Spawn_Model_Abstract
     */
    public function getModel(): Garp_Spawn_Model_Abstract {
        return $this->_model;
    }

    /**
     * @param Garp_Spawn_Model_Abstract $model
     * @return Garp_Spawn_Db_MySql_Table_Factory
     */
    public function setModel($model) {
        $this->_model = $model;

        return $this;
    }

    /**
     * Create table object.
     *
     * @param  string $createStatement
     * @return Garp_Spawn_Db_Table_Abstract
     */
    protected function _produceTable(string $createStatement): Garp_Spawn_Db_Table_Abstract {
        $model      = $this->getModel();
        $tableClass = $this->_getTableClass($model);
        return new $tableClass($createStatement, $this->_schema, $model);
    }

    protected function _renderCreateFromConfig() {
        $model     = $this->getModel();
        $tableName = $this->_getTableName();
        $fields    = $this->_getUnilingualFields();
        return $this->_schema->tables()->renderCreateStatement(
            $tableName,
            $fields,
            $model->relations->getRelations(),
            $model->unique
        );
    }

    protected function _getUnilingualFields() {
        $model = $this->getModel();

        $fields = $model->isMultilingual() ?
            $fields = $model->fields->getFields('multilingual', false) :
            $model->fields->getFields();

        return $fields;
    }

    protected function _renderCreateFromLive(): string {
        $tableName = $this->_getTableName($this->getModel());
        return $this->_schema->tables()->describe($tableName);
    }

    protected function _getTableName(): string {
        return $this->getModel()->getTableName();
    }

    /**
     * @return string Class of the table type that is to be returned
     */
    protected function _getTableClass(): string {
        return $this->getModel()->getTableClassName();
    }

}

