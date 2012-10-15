<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Model_Spawn_Model {
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


	public function __construct(Garp_Model_Spawn_Config_Model_Abstract $config) {
		$this->_loadPropertiesFromConfig($config);

		$this->behaviors->onAfterSingularRelationsDefinition();
		$this->fields->onAfterSingularRelationsDefinition();
	}


	/**
	 * Creates php models.
	 */
	public function materializePhpModels(Garp_Model_Spawn_ModelSet $modelSet) {
		$phpModel = new Garp_Model_Spawn_Php_Renderer($this);
		$phpModel->save();
	}


	/**
	 * Creates extended model files, if necessary.
	 */
	public function materializeExtendedJsModels(Garp_Model_Spawn_ModelSet $modelSet) {
		$jsExtendedModel = new Garp_Model_Spawn_Js_Model_Extended($this->id, $modelSet);
		$jsExtendedModelOutput = $jsExtendedModel->render();

		$jsAppExtendedFile = new Garp_Model_Spawn_Js_Model_File_AppExtended($this);
		$jsAppExtendedFile->save($jsExtendedModelOutput);

		if ($this->module === 'garp') {
			$jsGarpExtendedFile = new Garp_Model_Spawn_Js_Model_File_GarpExtended($this);
			$jsGarpExtendedFile->save($jsExtendedModelOutput);
		}
	}


	/**
	 * Creates base model file.
	 */
	public function renderBaseModel(Garp_Model_Spawn_ModelSet $modelSet) {
		$jsBaseModel = new Garp_Model_Spawn_Js_Model_Base($this->id, $modelSet);
		$jsBaseFile = new Garp_Model_Spawn_Js_Model_File_Base($this);
		return $jsBaseModel->render();
	}

	
	protected function _loadPropertiesFromConfig(Garp_Model_Spawn_Config_Model_Abstract $config) {
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
		$this->behaviors = new Garp_Model_Spawn_Behaviors($this, $config['behaviors']);
		$this->relations = new Garp_Model_Spawn_Relations($this, $config['relations']);
	}
}