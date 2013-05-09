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
	public $simpleSelect;

	/** Whether this relation field is editable in the cms. For instance, hasMany relations of which the opposite side is belongsTo (instead of hasOne), are not editable. */
	public $editable;
	
	/** Whether a singular relation (hasOne / belongsTo) also implicates a hasMany relation from the remote to the local model. */
	public $inverse;

	/** In case of a hasMany relation, the rule name of the opposite (hasOne / belongsTo) side of the relation. */
	public $oppositeRule = null;

	/** hasAndBelongsToMany and hasMany relations can be weighable, i.e. their relational order is defined by an end user. */
	public $weighable = false;

	/** Normally, the relation type sets whether a relation is required. A belongsTo relation makes it required, all other types do not.
	* 	However, this is overridable, so that belongsTo relations are not required. Deletion of parents will still delete this record as well, as with normal belongsTo relations,
	* 	but the association field will not be required.
	*/
	public $required;

	/**
	 * In case of a hasAndBelongsToMany relation, extra columns can be added to the binding model.
	 */
	public $inputs;

	/**
	 * Multiple relations that are defined as inline (not default behavior) do not appear as a separate
	 * tab in the cms, but as a set of fields on the form.
	 */
	public $inline;

	/** @var Garp_Model_Spawn_Model_Base $_model The local model in which this relation is defined. */
	protected $_localModel;

	/** @var Array $_types Allowed relation types. */
	protected $_types = array('hasOne', 'belongsTo', 'hasMany', 'hasAndBelongsToMany');


	/**
	 * @param 	String $name 	Name of the relation, such as 'User' or 'Author'
	 */
	public function __construct(Garp_Model_Spawn_Model_Abstract $localModel, $name, array $params) {
		$this->_localModel = $localModel;
		$this->name = $name;

		$this->_validate($name, $params);
		$this->_appendDefaults($name, $params);

		foreach ($params as $paramName => $paramValue) {
			$this->{$paramName} = $paramValue;
		}

		$this->_addRelationColumn();
		$this->_addRelationFieldInLocalModel();
		$this->_addOppositeRule();
// if ($localModel->id === 'Celebrity' && $name === 'Movie') {
// 	Zend_Debug::dump($params);
// 	Zend_Debug::dump($this);
// 	exit;
// 	exit;
// }

	}
	
	
	public function isPlural() {
		if ($this->type)
			return $this->type === 'hasAndBelongsToMany' || $this->type === 'hasMany';
		else throw new Exception("Relation::type is not available yet, so you cannot use isPlural() at this point in your code.");
	}

	
	public function isSingular() {
		if ($this->type)
			return $this->_isSingularByArg($this->type);
		else throw new Exception("Relation::type is not available yet, so you cannot use isSingular() at this point in your code. Use self::_isSingularByArg() within the class.");
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
	
	/**
	 * @param	Array	$propNames		Numeric array of property names
	 * @param	Array	$propValues		Corresponding numeric array of property values.
	 * 									Can be nested to support multiple values by an OR operator.
	 */
	public function hasProperties(array $propNames, array $propValues) {
		foreach ($propNames as $propIndex => $propName) {
			$valuesForThisProp = (array)($propValues[$propIndex]);

			if (!in_array($this->{$propName}, $valuesForThisProp)) {
				return false;
			}			
		}

		return true;
	}
	
	
	/**
	 * @return Garp_Model_Spawn_Model_Base 	A model object, representing the binding model
	 * 									between two hasAndBelongsToMany related models.
	 */
	public function getBindingModel() {
		$habtmModelId = Garp_Model_Spawn_Relations::getBindingModelName($this->model, $this->_localModel->id);

		if ($this->type === 'hasAndBelongsToMany') {
			$isHomo		= $this->model === $this->_localModel->id;
			$relLabel1	= $isHomo ? $this->name . '1' : $this->name;
			$relLabel2 	= $isHomo ? $this->name . '2' : $this->_localModel->id;

			$config = array(
				'listFields' => $this->column,
				'inputs' => $this->inputs ?: array(),
				'relations' => array(
					$relLabel1 => array(
						'type' => 'belongsTo',
						'model' => $this->model
					),
					$relLabel2 => array(
						'type' => 'belongsTo',
						'model' => $this->_localModel->id
					)
				)
			);

			if ($this->weighable) {
				$weightCol1 = Garp_Model_Spawn_Util::camelcased2underscored($relLabel1 . $relLabel2) . '_weight';
				$weightCol2 = Garp_Model_Spawn_Util::camelcased2underscored($relLabel2 . $relLabel1) . '_weight';
				$config['inputs'][$weightCol1] = array('type' => 'numeric');
				$config['inputs'][$weightCol2] = array('type' => 'numeric');
			}

			$model = new Garp_Model_Spawn_Model_Binding(
				new Garp_Model_Spawn_Config_Model_Binding(
					$habtmModelId,
					new Garp_Model_Spawn_Config_Storage_PhpArray(array($habtmModelId => $config)),
					new Garp_Model_Spawn_Config_Format_PhpArray
				)
			);

			/**
			* @todo: ATTENZIONE! Onderstaande moet liefst nog wel anders opgelost worden.
			* moet BindingModel niet toch een aparte class worden? */
			$relFields = $model->fields->getFields('origin', 'relation');
			foreach ($relFields as $field) {
				$model->fields->alter($field->name, 'primary', true);
			}

			return $model;
		} else throw new Exception("You can only call ". get_class($this) .".getBindingModel() on hasAndBelongsToMany relations.");
	}


	/**
	 * Check the provided relation type to see if this is a singular relation.
	 * @param String $relationType Type of the relation, i.e. hasOne, belongsTo, hasMany or hasAndBelongsToMany.
	 */
	protected function _isSingularByArg($relationType) {
		return $relationType === 'hasOne' || $relationType === 'belongsTo';
	}


	protected function _validate($name, array $params) {
		if (!array_key_exists('type', $params)) {
			throw new Exception("The 'type' property is obligated in the definition of the {$name} relation.");
		} else {
			foreach ($params as $paramName => $paramValue) {
				switch ($paramName) {
					case 'type':
						if (!in_array($paramValue, $this->_types)) {
							throw new Exception("The '{$param['type']}' relation type for {$name} is invalid. Try: ".implode($this->_types, ", "));
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
							throw new Exception("'{$paramName}' is not a valid parameter for the {$this->_localModel->id} > {$name} relation field configuration. Try: ".implode($publicProps, ", "));
						}
				}
			}
		}
	}
	
	
	protected function _appendDefaults($name, array &$params) {
		//	during execution of this method, self::isSingular() is not yet available.
		if (!array_key_exists('model', $params) || !$params['model'])
			$params['model'] = $name;

		if (!array_key_exists('label', $params) || !$params['label'])
			$params['label'] = $name;
		
		if (!array_key_exists('limit', $params) && $this->_isSingularByArg($params['type']))
			$params['limit'] = 1;

		if (!array_key_exists('inverse', $params))
			$params['inverse'] = $this->_isSingularByArg($params['type']);

		if (!array_key_exists('editable', $params))
			$params['editable'] = true;
			
		if (!array_key_exists('required', $params))
			$params['required'] = $params['type'] === 'belongsTo';

		if (!array_key_exists('inline', $params))
			$params['inline'] = false;
	}


	protected function _addRelationColumn() {
		$this->column = $this->isSingular() ?
			Garp_Model_Spawn_Relations::getRelationColumn($this->name) :
			Garp_Model_Spawn_Relations::getRelationColumn($this->_localModel->id)
		;
	}


	/** Registers this relation as a Field in the Model. */
	protected function _addRelationFieldInLocalModel() {
		if ($this->isSingular()) {
			$column = Garp_Model_Spawn_Relations::getRelationColumn($this->name);
			$fieldParams = array(
				'type' => 'numeric',
				'editable' => false,
				'visible' => false,
				'required' => $this->required
			);
			$this->_localModel->fields->add('relation', $column, $fieldParams);
		}
	}	
	
	protected function _addOppositeRule() {
		if ($this->isPlural() && !$this->oppositeRule) {
			$this->oppositeRule = $this->_localModel->id;
		}
	}
}