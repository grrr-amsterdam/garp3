<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Model_Spawn_Fields {
	public $listFieldNames = array();

	/** @var Array $_fields	Numeric array, where the key is the position of the field, and the value a Garp_Model_Spawn_Field object. */
	protected $_fields = array();

	/** @var Garp_Model_Spawn_Model */
	protected $_model;
	
	protected $_defaultFields = array(
		'id' => array(
			'type' => 'numeric',
			'editable' => false,
			'visible' => false,
			'primary' => true
		)
	);
	
	protected $_configuredListFields;
	

	public function __construct(Garp_Model_Spawn_Model $model, StdClass $configuredInputs, Array $configuredListFields) {
		$this->_model = $model;
		$this->_configuredListFields = $configuredListFields;

		foreach ($configuredInputs as $fieldName => $fieldParams) {
			$this->add('config', $fieldName, $fieldParams);
		}
		
		$this->_addDefaultFields();
	}
	
	
	public function onAfterSingularRelationsDefinition() {
		$this->listFieldNames = $this->_listListFields();
	}
	

	/**
	* Add a field to the fields registry.
	* @param String $origin Context in which this field is added. Can be 'config', 'default', 'relation' or 'behavior'.
	*/
	public function add($origin, $name, $params = null) {
		if (!array_key_exists($name, $this->_fields)) {
			$field = new Garp_Model_Spawn_Field($origin, $name, $params);
			if ($origin === 'default')
				array_unshift($this->_fields, $field);
			else
				$this->_fields[] = $field;
		}
		else throw new Exception("The '{$name}' field is already registered for this model.");
	}
	
	
	/**
	 * @return Array Numeric array of Garp_Model_Spawn_Field objects, where the key is the field position
	 */
	public function getFields($filterPropName = null, $filterPropValue = null) {
		if ($filterPropName) {
			$out = array();
			foreach ($this->_fields as $position => $field) {
				if ($field->{$filterPropName} == $filterPropValue)
					$out[$position] = $field;
			}
			return $out;
		} else return $this->_fields;
	}


	/**
	 * @return Array Numeric array of names of Garp_Model_Spawn_Field objects
	 */
	public function getFieldNames($filterPropName = null, $filterPropValue = null) {
		$out = array();
		$fields = $this->getFields($filterPropName, $filterPropValue);

		foreach ($fields as $position => $field) {
			$out[$position] = $field->name;
		}
		
		return $out;
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
	

	protected function _addDefaultFields() {
		foreach ($this->_defaultFields as $fieldName => $fieldParams) {
			$this->add('default', $fieldName, (object)$fieldParams);
		}
	}
}
