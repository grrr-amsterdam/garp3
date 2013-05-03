<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
abstract class Garp_Model_Spawn_Model_Abstract {
	public $id;
	public $order;
	public $label;
	public $description;
	public $route;
	public $creatable;
	public $deletable;
	public $quickAddable;

	/** @var Boolean $visible Whether this model shows up in the cms index. */
	public $visible;
	
	/** @var String $module Module for this model. */
	public $module;

	/** @var Garp_Model_Spawn_Fields $fields */
	public $fields;
	
	/** @var Garp_Model_Spawn_Behaviors $behaviors */
	public $behaviors;

	/** @var Garp_Model_Spawn_Relations $relations */
	public $relations;
	

	/**
	 * These properties cannot be configured directly from the configuration because of their complexity.
	 */
	protected $_indirectlyConfigurableProperties = array('fields', 'listFields', 'behaviors', 'relations');


	public function __construct(ArrayObject $config) {
		$this->_loadPropertiesFromConfig($config);

		$this->behaviors->onAfterSingularRelationsDefinition();
		$this->fields->onAfterSingularRelationsDefinition();
	}

	/**
	 * Creates php models.
	 */
	public function materializePhpModels(Garp_Model_Spawn_Model_Abstract $model) {
		$phpModel = new Garp_Model_Spawn_Php_Renderer($model);
		$phpModel->save();
	}
	
	public function isMultilingual() {
		return false;
	}
	
	protected function _loadPropertiesFromConfig(ArrayObject $config) {
		foreach ($config as $propName => $propValue) {
			if (
				!in_array($propName, $this->_indirectlyConfigurableProperties) &&
				property_exists($this, $propName)
			) {
				$this->{$propName} = $propValue;
			}
		}

		//	complex types
		$this->fields = new Garp_Model_Spawn_Fields($this, $config['inputs'], (array)$config['listFields']);
		$this->behaviors = new Garp_Model_Spawn_BehaviorSet($this, $config['behaviors']);
		$this->relations = new Garp_Model_Spawn_Relations($this, $config['relations']);
	}
}