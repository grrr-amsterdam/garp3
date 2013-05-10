<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Model_Spawn_Relation_Set {
	const ERROR_RELATION_ALREADY_REGISTERED =
		"You're trying to add the '%s' %s relation, but there already is a %s %s relation registered in the '%s' model.";
	const ERROR_UNKNOWN_RELATION_NAME =
		"The '%s' relation was not registered.";
	const ERROR_WRONG_ARGUMENT_NUMBER =
		"%s::getRelations() needs either 0 or 2 arguments.";
	const ERROR_MODELNAME_IS_NO_STRING =
		'%s needs a model name as a string.';

	/**
	 * @var Array $_fields	Associative array, where the key is the name of the relation, and the value a Garp_Model_Spawn_Relation object.
	 */
	protected $_relations = array();

	/**
	 * @var Garp_Model_Spawn_Model_Base $_model
	 */
	protected $_model;
	
	
	public function __construct(Garp_Model_Spawn_Model_Abstract $model, array $config) {
		$this->_model = $model;

		foreach ($config as $relationName => &$relationParams) {
			$this->add($relationName, $relationParams);
		}
	}

	/**
	 * @param String 	$filterPropName 	Garp_Model_Spawn_Relation property to filter the request by
	 * @param Mixed 	$filterPropValue 	Value of the Garp_Model_Spawn_Relation property the result should contain.
	 *										Can also be an array, in which it will be considered an OR query.
	 * @return Array 						Associative array, where the key is the name of the relation,
	 *										and the value a Garp_Model_Spawn_Relation object.
	 */
	public function getRelations($filterPropName = null, $filterPropValue = null) {
		if (!$filterPropName) {
			return $this->_relations;
		}
		
		if (count(func_get_args()) !== 2) {
			$error = sprintf(self::ERROR_WRONG_ARGUMENT_NUMBER, get_class($this));
			throw new Exception($error);
		}

		$out = array();
		foreach ($this->_relations as $relName => $rel) {
			$filterPropNames 	= is_array($filterPropName) ? $filterPropName : array($filterPropName);
			$filterPropValues 	= is_array($filterPropName) ? $filterPropValue : array($filterPropValue);

			if ($rel->hasProperties($filterPropNames, $filterPropValues)) {
				$out[$relName] = $rel;
			}
		}
		return $out;
	}
	
	public function getSingularRelations() {
		$singularRels = array();

		foreach ($this->_relations as $relName => $relation) {
			$singularRels = $this->_addSingularRelation($singularRels, $relName, $relation);
		}
		
		return $singularRels;
	}
	
	public function getRelation($name) {
		if (array_key_exists($name, $this->_relations)) {
			return $this->_relations[$name];
		}

		$error = sprintf(self::ERROR_UNKNOWN_RELATION_NAME, $name);
		throw new Exception($error);
	}

	public function add($name, array $params, $preventDoubles = true) {
		if ($preventDoubles) {
			$this->_throwErrorOnDuplicateRegistration($name, $params);
		}
		
		$this->_relations[$name] = new Garp_Model_Spawn_Relation($this->_model, $name, $params);
		ksort($this->_relations);
	}
	
	protected function _throwErrorOnDuplicateRegistration($name, array $params) {
		if (!array_key_exists($name, $this->_relations)) {
			return;
		}
	
		$error = sprintf(
			self::ERROR_RELATION_ALREADY_REGISTERED,
			$name,
			$params['type'],
			$name,
			$this->_relations[$name]->type,
			$this->_model->id
		);

		throw new Exception($error);
	}

	static public function getBindingModelName($modelName1, $modelName2) {
		$modelNames = array($modelName1, $modelName2);
		sort($modelNames);
		return implode('', $modelNames);
	}
	

	/**
	 * @param String $modelName Name of the model.
	 * @param Int $index Optional index of this column, in case of a homosexual relation.
	 * @return String Database column / field name that corresponds to this model. E.g.: 'Users' -> 'users_id'
	 */
	static public function getRelationColumn($modelName, $index = null) {
		if (!is_string($modelName)) {
			$error = sprintf(self::ERROR_MODELNAME_IS_NO_STRING, __METHOD__);
			throw new Exception($error);
		}
		
		$modelNamespace = Garp_Model_Spawn_Util::camelcased2underscored($modelName);
		$relationColumn	= $modelNamespace . (is_null($index) ? '' : (string)$index) . '_id';
		return $relationColumn;
	}
	
	/**
	 * Adds the relation to the relation set, if it's singular.
	 * @param	Array						$relationSet	The set to add the relation to
	 * @param	String						$relName		The relation name
	 * @param	Garp_Model_Spawn_Relation	$relation		The relation instance, plural or singular
	 * @return 	Array 										The relation set with the added singular relation
	 */
	protected function _addSingularRelation(array $relationSet, $relName, Garp_Model_Spawn_Relation $relation) {
		if ($relation->isSingular()) {
			$relationSet[$relName] = $relation;
		}
		
		return $relationSet;
	}
}