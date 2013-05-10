<?php
/**
 * Generated PHP model
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Spawn
 */
class Garp_Model_Spawn_Php_Model_Extended extends Garp_Model_Spawn_Php_Model_Abstract {
	public function render() {
		$model 			= $this->getModel();
		$parentClass	= $this->_getParentClass();

		$out = 
			  $this->_rl("<?php")
			. $this->_rl("class Model_{$model->id} extends {$parentClass} {", 0)
			. $this->_rl("public function init() {", 1)
			. $this->_rl('parent::init();', 2)
			. $this->_rl('}', 1)
			. $this->_rl("}", 0, 0)
		;

		return $out;
	}
	
	protected function _getParentClass() {
		$model 				= $this->getModel();
		$parentNamespace	= $this->_getParentNamespace();
		$parentClass 		= $parentNamespace . $model->id;

		return $parentClass;
	}
	
	protected function _getParentNamespace() {
		$model 			= $this->getModel();
		$modelClass 	= get_class($model);
		$dynamicBase 	= $modelClass === 'Garp_Model_Spawn_Model_Base';
		$isGarp			= $model->module === 'garp';
		$namespace		= $dynamicBase && $isGarp ? 'G_Model_' : 'Model_Base_';
		
		return $namespace;
	}
}