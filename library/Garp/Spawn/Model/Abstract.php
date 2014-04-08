<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
abstract class Garp_Spawn_Model_Abstract {
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

	/** @var Garp_Spawn_Fields $fields */
	public $fields;
	
	/** @var Garp_Spawn_Behaviors $behaviors */
	public $behaviors;

	/** @var Garp_Spawn_Relation_Set $relations */
	public $relations;
	
	/**
	 * @var	Array	$unique		Column names that should jointly compose a unique key (optional)
	 */
	public $unique;
	

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
	public function materializePhpModels(Garp_Spawn_Model_Abstract $model) {
		$phpModel = new Garp_Spawn_Php_Renderer($model);
		$phpModel->save();
	}
	
	/**
	 * @return 	Bool 	Whether this is a base model containing one or more multilingual columns
	 */
	public function isMultilingual() {
		return false;
	}

	/**
	 * @return 	Bool 	Whether this is a i18n leaf model, derived from a multilingual base model
	 */
	public function isTranslated() {
		return false;
	}
	
	protected function _loadPropertiesFromConfig(ArrayObject $config) {
		foreach ($config as $propName => $propValue) {
			$this->_loadProperty($propName, $propValue);
		}

		//	complex types
		$this->fields 		= new Garp_Spawn_Fields($this, $config['inputs'], (array)$config['listFields']);
		$this->behaviors	= new Garp_Spawn_Behavior_Set($this, $config['behaviors']);
		$this->relations 	= new Garp_Spawn_Relation_Set($this, $config['relations']);
	}
	
	protected function _loadProperty($name, $value) {
		$indirectlyConfigurable = in_array($name, $this->_indirectlyConfigurableProperties);
		$exists 				= property_exists($this, $name);

		if (!$exists && !$indirectlyConfigurable && $name !== 'inputs') {
			throw new Exception("The {$name} property is not a valid Spawn model property.");
		}
		
		if (!$indirectlyConfigurable && $exists) {
			$this->{$name} = $value;
		}
	}
}