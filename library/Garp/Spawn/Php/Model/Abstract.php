<?php
/**
 * Garp_Spawn_Php_Model_Abstract
 * Generated PHP model.
 *
 * @package Garp_Spawn
 * @author David Spreekmeester <david@grrr.nl>
 */
abstract class Garp_Spawn_Php_Model_Abstract implements Garp_Spawn_Php_Model_Protocol {
    /**
     * @var Garp_Spawn_Model_Abstract
     */
    protected $_model;

    public function __construct(Garp_Spawn_Model_Abstract $model) {
        $this->setModel($model);
    }

    /**
     * Saves the model file, if applicable. A model that exists and should not be overwritten
     * will not be touched by this method.
     *
     * @return void
     */
    public function save() {
        $path       = $this->getPath();
        $content    = $this->render();
        $overwrite  = $this->isOverwriteEnabled();

        if (!$overwrite && file_exists($path)) {
            return true;
        }

        if (!file_put_contents($path, $content)) {
            $model = $this->getModel();
            throw new Exception("Could not generate {$model->id}" . get_class());
        }
        return true;
    }

    /**
     * @return Garp_Spawn_Model_Abstract
     */
    public function getModel() {
        return $this->_model;
    }

    /**
     * @param Garp_Spawn_Model_Abstract $model
     * @return Garp_Spawn_Php_Model_Abstract
     */
    public function setModel($model) {
        $this->_model = $model;
        return $this;
    }

    public function getTableName() {
        $model        = $this->getModel();
        $tableFactory = new Garp_Spawn_Db_Table_Factory($model, $this->_schema);
        $table        = $tableFactory->produceConfigTable();

        return $table->name;
    }

    /**
     * Render line with tabs and newlines
     *
     * @param string $content Line to add
     * @param int $tabs Level of indentation
     * @param int $newlines Number of trailing newlines
     * @return string
     */
    protected function _rl($content, $tabs = 0, $newlines = 1) {
        return str_repeat("    ", $tabs) . $content . str_repeat("\n", $newlines);
    }
}
