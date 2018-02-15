<?php
/**
 * A representation of a MySQL view that includes the labels of related hasOne and belongsTo records.
 *
 * @package Garp_Spawn_Db_View
 * @author  David Spreekmeester <david@grrr.nl>
 */
abstract class Garp_Spawn_Db_View_Abstract implements Garp_Spawn_Db_View_Protocol {
    /**
     * @var Garp_Spawn_Model_Base
     */
    protected $_model;

    protected $_schema;

    public function __construct(
        Garp_Spawn_Model_Base $model,
        Garp_Spawn_Db_Schema_Interface $schema
    ) {
        $this->setModel($model);
        $this->_schema = $schema;
    }

    /**
     * Deletes all views in the database with given postfix.
     *
     * @param  string                         $postfix The postfix for this type of view,
     *                                                 f.i. '_joint'
     * @param  Garp_Spawn_Db_Schema_Interface $schema
     * @return void
     */
    public static function deleteAllByPostfix($postfix, Garp_Spawn_Db_Schema_Interface $schema) {
        $config = Zend_Registry::get('config');
        $dbName = $config->resources->db->params->dbname;

        $views = $schema->fetchViewsByPostfix($dbName, $postfix);
        foreach ($views as $view) {
            $viewName = $view['table_name'];
            $schema->dropView($viewName);
        }
    }

    /**
     * @return bool Result of this creation query.
     */
    public function create() {
        $sql = $this->renderSql();
        if (!$sql) {
            return false;
        }
        return $this->_schema->query($sql);
    }

    public function getTableName() {
        $model        = $this->getModel();
        $tableFactory = new Garp_Spawn_Db_Table_Factory($model);
        $table        = $tableFactory->produceConfigTable();

        return $table->name;
    }

    /**
     * @return string
     */
    public function getModel() {
        return $this->_model;
    }

    /**
     * @param string $model
     * @return void
     */
    public function setModel($model) {
        $this->_model = $model;
    }

    /**
     * @param  string $selectString A string containing the SELECT query, to use as the foundation
     *                              of the view.
     * @return string               The string representing the full CREATE statement for the view
     */
    protected function _renderCreateView($selectString) {
        $name = $this->getName();
        return "CREATE SQL SECURITY INVOKER VIEW `{$name}` AS " . $selectString;
    }
}
