<?php
/**
 * This class represents a set of abstract model fields.
 * These fields are abstract in the sense of being decoupled from database or generated php files.
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Spawn_Fields {
	public $listFieldNames = array();

	/** @var Array $_fields	Numeric array, where the key is the position of the field, and the value a Garp_Spawn_Field object. */
	protected $_fields = array();

	/** @var Garp_Spawn_Model_Base */
	protected $_model;
	
	protected $_configuredListFields;
	

	public function __construct(Garp_Spawn_Model_Abstract $model, array $configuredInputs, array $configuredListFields) {
		$this->_model = $model;
		$this->_configuredListFields = $configuredListFields;

		foreach ($configuredInputs as $fieldName => $fieldParams) {
			$this->add('config', $fieldName, $fieldParams);
		}
	}
	
	
	public function onAfterSingularRelationsDefinition() {
		$this->_addWeighableRelationFields();

		$this->listFieldNames = $this->_listListFields();
	}
	

	/**
	* Add a field to the fields registry.
	* @param String $origin Context in which this field is added. Can be 'config', 'default', 'relation' or 'behavior'.
	* @param String $name Field name.
	*/
	public function add($origin, $name, array $params = array()) {
		if (!$this->exists($name)) {
			$field = new Garp_Spawn_Field($origin, $name, $params);
			if ($origin === 'default')
				array_unshift($this->_fields, $field);
			else
				$this->_fields[] = $field;
		}
		else throw new Exception("The '{$name}' field is already registered for this model.");
	}


	/**
	* Delete a field from the fields registry.
	* @param String $name Field name.
	*/
	public function delete($name) {
		if ($this->exists($name)) {
			foreach ($this->_fields as $i => $field) {
				if ($field->name === $name) {
					unset($this->_fields[$i]);
					break;
				}
			}
		} else throw new Exception("The '{$name}' field is not registered for this model.");
	}
	
	
	/**
	* Alter a field in the fields registry.
	* @param String $name Field name.
	* @param String $prop Field property name, f.i. 'type'
	* @param Mixed $value The new field value
	*/
	public function alter($name, $prop, $value) {
		foreach ($this->_fields as &$field) {
			if ($field->name === $name) {
				$field->{$prop} = $value;
			}
		}
	}
	
	public function getField($name) {
		foreach ($this->_fields as $position => $field) {
			if ($field->name === $name) {
				return $field;
			}
		}
		
		$model = $this->getModel();
		throw new Exception("No field by the name of {$name} was found in the {$model->id} model.");
	}
	
	public function getModel() {
		return $this->_model;
	}
	
	/**
	 * @return Array Numeric array of Garp_Spawn_Field objects, where the key is the field position
	 */
	public function getFields($filterPropName = null, $filterPropValue = null) {
		if ($filterPropName) {
			if (count(func_get_args()) !== 2) {
				throw new Exception(get_class($this) . "::getFields() needs either 0 or 2 arguments.");
			}

			$out = array();
			foreach ($this->_fields as $position => $field) {
				if ($field->{$filterPropName} == $filterPropValue) {
					/*	if this field is a relation field, make sure the field label is the relation label,
						as seen from the current model. */
					if ($field->origin === 'relation') {
						$rels = $this->_model->relations->getRelations('name', $field->label);
						if ($rels) {
							$currentRel = current($rels);
							$field->label = $currentRel->label;
						}
					}
					$out[$position] = $field;
				}
			}
			return $out;
		} else return $this->_fields;
	}


	/**
	 * @return Array Numeric array of names of Garp_Spawn_Field objects
	 */
	public function getFieldNames($filterPropName = null, $filterPropValue = null) {
		$out = array();
		$fields = $this->getFields($filterPropName, $filterPropValue);

		foreach ($fields as $position => $field) {
			$out[$position] = $field->name;
		}
		
		return $out;
	}

	/**
	 * @return Array Containing fields that are used as list fields.
	 */
	public function getListFieldNames() {
		$listFieldNames = $this->listFieldNames;
		$fieldDefs 		= array();

		$self = $this;
		$isSuitable = function($item) use ($self) {
			return $self->isSuitableListFieldName($item);
		};

		$suitableFieldNames = array_filter($listFieldNames, $isSuitable);
		if (!$suitableFieldNames) {
			$suitableFieldNames = array('id');
		}
		return $suitableFieldNames;
	}

	/**
	 * Checks wether a field can be used as list field.
	 * @param String $listFieldName
	 * @return Boolean
	 */
	public function isSuitableListFieldName($listFieldName) {	
		try {
			$field = $this->getField($listFieldName);
		} catch (Exception $e) {
			return;
		}

		if ($field && $field->isSuitableAsLabel()) {
			return true;
		}
	}

	
	public function exists($name) {
		foreach ($this->_fields as $field) {
			if ($field->name === $name)
				return true;
		}
		return false;
	}


	protected function _listListFields() {
		if ($this->_configuredListFields) {
			return $this->_configuredListFields;
		} else {
			$listFields = array();
			
			if ($this->exists('image_id')) {
				$listFields[] = 'image_id';
			}

			if ($this->exists('name')) {
				$listFields[] = 'name';
			} elseif (
				$this->exists('first_name') &&
				$this->exists('last_name_prefix') &&
				$this->exists('last_name')
			) {
				$listFields[] = 'first_name';
				$listFields[] = 'last_name_prefix';
				$listFields[] = 'last_name';
			} else throw new Exception("The {$this->_model->id} model does not have any listFields configured, nor a 'name' or 'first_name' / 'last_name_prefix' / 'last_name' input field.");
			
			return $listFields;
		}
	}


	protected function _addWeighableRelationFields() {
		$weighableRels = $this->_model->relations->getRelations('weighable', true);
		$fieldConfig = array(
			'type' => 'numeric',
			'required' => false,
			'default' => 0,
			'editable' => false,
			'visible' => false
		);

		foreach ($weighableRels as $relName => $rel) {
			$this->add('relation', Garp_Spawn_Util::camelcased2underscored($relName.'_weight'), $fieldConfig);
		}
	}
}
