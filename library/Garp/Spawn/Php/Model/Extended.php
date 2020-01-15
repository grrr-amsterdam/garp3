<?php
/**
 * Generated PHP model
 *
 * @package Garp_Spawn_Php_Model
 * @author  David Spreekmeester <david@grrr.nl>
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Spawn_Php_Model_Extended extends Garp_Spawn_Php_Model_Abstract {
    const MODEL_DIR = '/modules/default/Model/';


    public function getPath() {
        $model = $this->getModel();
        return APPLICATION_PATH . self::MODEL_DIR . $model->id . '.php';
    }

    public function isOverwriteEnabled() {
        return false;
    }

    public function render() {
        $model = $this->getModel();
        $parentClass = $this->_getParentClass();

        $out
            = $this->_rl("<?php")
            . $this->_rl("class Model_{$model->id} extends {$parentClass} {", 0)
            . $this->_rl("}", 0, 0);

        return $out;
    }

    protected function _getParentClass() {
        $model = $this->getModel();
        $parentNamespace = $this->_getParentNamespace();
        $parentClass = $parentNamespace . $model->id;

        return $parentClass;
    }

    protected function _getParentNamespace() {
        $model = $this->getModel();
        $modelClass = get_class($model);
        $dynamicBase = $modelClass === 'Garp_Spawn_Model_Base';
        $isGarp = $model->module === 'garp';
        $namespace = $dynamicBase && $isGarp ? 'Garp_Model_Db_' : 'Model_Base_';

        return $namespace;
    }
}

