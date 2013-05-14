<?php
/**
 * Generated PHP model
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Spawn
 */
class Garp_Spawn_Php_Model_Factory {
	const TYPE_BASE 		= 'Base';
	const TYPE_EXTENDED		= 'Extended';
	const TYPE_BINDING_BASE	= 'BindingBase';
	const TYPE_LOCALIZED	= 'Localized';
	
	/**
	 * @var Garp_Spawn_Php_Model_Abstract $_model
	 */
	protected $_model;
	
	
	public function __construct(Garp_Spawn_Model_Abstract $model) {
		$this->setModel($model);
	}

	/**
	 * @param	Int		$type			Model type to be produced.
	 *									Must be one of the self::TYPE_* constants
	 * @param	Mixed	[$argument]		Second argument to pass along to the Php model class
	 */
	public function produce($type, $argument = null) {
		$model 			= $this->getModel();
		$class = $this->_getClass($type);

		return new $class($model, $argument);
	}

	/**
	 * @return Garp_Spawn_Php_Model_Abstract
	 */
	public function getModel() {
		return $this->_model;
	}
	
	/**
	 * Chaining method.
	 * @param Garp_Spawn_Php_Model_Abstract $model
	 */
	public function setModel($model) {
		$this->_model = $model;
		return $this;
	}

	/**
	 * @param	Int							$type		Model type to be produced.
	 *													Must be one of the self::TYPE_* constants.
	 */	
	protected function _getClass($type) {
		return 'Garp_Spawn_PHP_Model_' . $type;
	}

}