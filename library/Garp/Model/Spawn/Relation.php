<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Model_Spawn_Relation {
	/** @var String $model The remote model which is referenced in this relation. */
	public $model;
	public $name;
	public $type;
	public $label;
	public $limit;
	public $column;

	/** Whether this relation field is editable in the cms. */
	public $editable;
	
	/* Whether a singular relation (hasOne / belongsTo) also implicates a hasMany relation from the remote to the local model. */
	public $inverse;

	/** @var Garp_Model_Spawn_Model $_model The local model in which this relation is defined. */
	protected $_localModel;

	/** @var Array $_types Allowed relation types. */
	protected $_types = array('hasOne', 'belongsTo', 'hasMany', 'hasAndBelongsToMany');


	/**
	 * @param 	String $name 	Name of the relation, such as 'User' or 'Author'
	 */
	public function __construct(Garp_Model_Spawn_Model $localModel, $name, StdClass $params) {
		$this->_localModel = $localModel;
		$this->name = $name;

		$this->_validate($name, $params);
		$this->_appendDefaults($name, $params);

		foreach ($params as $paramName => $paramValue) {
			$this->{$paramName} = $paramValue;
		}

		$this->_addRelationColumn();
		$this->_addRelationFieldInLocalModel();
	}
	
	
	public function isSingular() {
		return $this->type === 'hasOne' || $this->type === 'belongsTo';
	}


	public function getParams() {
		$out = new StdClass();
		$refl = new ReflectionObject($this);
		$reflProps = $refl->getProperties(ReflectionProperty::IS_PUBLIC);
	    foreach ($reflProps as $reflProp) {
			$out->{$reflProp->name} = $this->{$reflProp->name};
		}

		return $out;
	}

	protected function _validate($name, StdClass $params) {
		if (!property_exists($params, 'type')) {
			throw new Exception("The 'type' property is obligated in the definition of the {$name} relation.");
		} else {
			foreach ($params as $paramName => $paramValue) {
				switch ($paramName) {
					case 'type':
						if (!in_array($paramValue, $this->_types)) {
							throw new Exception("The '{$param->type}' relation type for {$name} is invalid. Try: ".implode($this->_types, ", "));
						}
					break;
					case 'name':
						throw new Exception("The relation name cannot be defined as a property for the {$name} relation. Instead, it should be the key of the relation.");
					break;
					default:
						if (!property_exists($this, $paramName)) {
							$refl = new ReflectionObject($this);
							$reflProps = $refl->getProperties(ReflectionProperty::IS_PUBLIC);
						    $publicProps = array();
							foreach ($reflProps as $reflProp) {
								if ($reflProp->name !== 'name')
									$publicProps[] = $reflProp->name;
							}
							throw new Exception("'{$paramName}' is not a valid parameter for a model field configuration. Try: ".implode($publicProps, ", "));
						}
				}
			}
		}
	}
	
	
	protected function _appendDefaults($name, StdClass &$params) {
		if (!property_exists($params, 'model') || !$params->model)
			$params->model = $name;

		if (!property_exists($params, 'label') || !$params->label)
			$params->label = $name;
		
		if (!property_exists($params, 'limit') && $this->isSingular($params->type))
			$params->limit = 1;
		
		if (!property_exists($params, 'inverse') && $this->isSingular($params->type))
			$params->inverse = true;
			
		if (!property_exists($params, 'editable'))
			$params->editable = true;
	}


	protected function _addRelationColumn() {
		$this->column = $this->isSingular($this->type) ?
			Garp_Model_Spawn_Relations::getRelationColumn($this->name) :
			Garp_Model_Spawn_Relations::getRelationColumn($this->_localModel->id)
		;
	}


	/** Registers this relation as a Field in the Model. */
	protected function _addRelationFieldInLocalModel() {
		if ($this->isSingular($this->type)) {
			$column = Garp_Model_Spawn_Relations::getRelationColumn($this->name);
			$fieldParams = array(
				'type' => 'numeric',
				'editable' => false,
				'visible' => false,
				'required' => ($this->type === 'belongsTo')
			);
			$this->_localModel->fields->add('relation', $column, (object)$fieldParams);
		}
	}
}