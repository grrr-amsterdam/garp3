<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Model_Spawn_Model {
	public $id;
	public $order;
	public $label;
	public $route;
	public $creatable;
	public $quickAddable;

	/** @var Boolean $visible Whether this model shows up in the cms index. */
	public $visible;
	
	/** @var String $module Optional module for this model. If empty, the model is part of the default (application) module. Could also be 'garp'. */
	public $module;

	/** @var Garp_Model_Spawn_Fields $fields */
	public $fields;
	
	/** @var Garp_Model_Spawn_Behaviors $behaviors */
	public $behaviors;

	/** @var Garp_Model_Spawn_Relations $relations */
	public $relations;


	/**
	 * Class constructor
	 * @return Void
	 */
	public function __construct($modelId) {
		$this->id = $modelId;

		$configFile = new Garp_Model_Spawn_ConfigFile($modelId);
		
		$this->order = $configFile->getOrder();
		$this->label = $configFile->getLabel();
		$this->route = $configFile->getRoute();
		$this->module = $configFile->getModule();
		$this->creatable = $configFile->getCreatable();
		$this->deletable = $configFile->getDeletable();
		$this->quickAddable = $configFile->getQuickAddable();
		$this->visible = $configFile->getVisible();

		$this->_addModelDefaults();

		$this->fields = new Garp_Model_Spawn_Fields($this, $configFile->getInputs(), $configFile->getListFields());
		$this->behaviors = new Garp_Model_Spawn_Behaviors($this, $configFile->getBehaviors());
		$this->relations = new Garp_Model_Spawn_Relations($this, $configFile->getRelations());
		$this->fields->onAfterSingularRelationsDefinition();
	}


	/**
	 * Creates database tables, writes base model files and, if necessary, extended model file scaffolds.
	 */
	public function realize() {
		echo "{$this->id}\n";

		$phpModel = new Garp_Model_Spawn_Php_Renderer($this);
		$phpModel->save();

		$jsModel = new Garp_Model_Spawn_Js_Renderer($this);
		$jsModel->save();

		echo "\n";
	}
	
	
	protected function _addModelDefaults() {
		if (!$this->order)
			$this->order = "created DESC";

		if (!$this->label)
			$this->label = ucfirst(Garp_Model_Spawn_Util::underscored2readable(Garp_Model_Spawn_Util::camelcased2underscored($this->id)));

		if (is_null($this->creatable))
			$this->creatable = true;

		if (is_null($this->deletable))
			$this->deletable = true;

		if (is_null($this->quickAddable))
			$this->quickAddable = false;

		if (is_null($this->visible))
			$this->visible = true;
	}
}
