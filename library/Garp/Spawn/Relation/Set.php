<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Spawn_Relation_Set {
	const ERROR_RELATION_ALREADY_REGISTERED =
		"You're trying to add the '%s' %s relation, but there already is a %s %s relation registered in the '%s' model.";
	const ERROR_UNKNOWN_RELATION_NAME =
		"The '%s' relation was not registered.";
	const ERROR_WRONG_ARGUMENT_NUMBER =
		"%s::getRelations() needs either 0 or 2 arguments.";
	const ERROR_MODELNAME_IS_NO_STRING =
		'%s needs a model name as a string.';
	const ERROR_RELATION_TO_NON_EXISTING_MODEL =
		"The '%s' model defines a %s relation to unexisting model '%s'.";


	/**
	 * @todo: 	Deze default relations zouden beter naar Behavior/Type/Authorable kunnen.
	 * 			Maar tot nu toe worden in die laag alleen extra velden toegevoegd, geen relaties.
	 */
	protected $_defaultBaseRelations = array(
		'Author' => array(
			'model' => 'User',
			'type' => 'hasOne',
			'inverse' => false,
			'label' => 'Created by'
		),
		'Modifier' => array(
			'model' => 'User',
			'type' => 'hasOne',
			'inverse' => false,
			'editable' => false,
			'label' => 'Modified by'
		)
	);

	/**
	 * @var Array $_fields	Associative array, where the key is the name of the relation, and the value a Garp_Spawn_Relation object.
	 */
	protected $_relations = array();

	/**
	 * @var Garp_Spawn_Model_Base $_model The model in which this relation set is defined.
	 */
	protected $_model;
	
	
	public function __construct(Garp_Spawn_Model_Abstract $model, array $config) {
		$this->_setModel($model);
		$this->_addDefaultBaseRelations();

		foreach ($config as $relationName => &$relationParams) {
			$this->add($relationName, $relationParams);
		}
	}

	/**
	 * @return Garp_Spawn_Model_Base
	 */
	public function getModel() {
		return $this->_model;
	}
	
	/**
	 * @param String 	$filterPropName 	Garp_Spawn_Relation property to filter the request by
	 * @param Mixed 	$filterPropValue 	Value of the Garp_Spawn_Relation property the result should contain.
	 *										Can also be an array, in which it will be considered an OR query.
	 * @return Array 						Associative array, where the key is the name of the relation,
	 *										and the value a Garp_Spawn_Relation object.
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

		$relation = new Garp_Spawn_Relation($this->_model, $name, $params);
		$this->addRaw($relation);
	}

	public function addRaw(Garp_Spawn_Relation $relation) {
		$this->_relations[$relation->name] = $relation;
		ksort($this->_relations);
	}

	/**
	 * Adds the mirrored set of relations to the remote models.
	 * Event hasOne Group results in Group hasMany Event.
	 * Profile belongsTo User results in User hasMany Profile.
	 * Event hasAndBelongsToMany Tag results in Tag hasAndBelongsToMany Event.
	 */
	public function addMirrored() {
		// $this->_mirrorHabtmRelations();
		$this->_mirrorHasManyRelations();
	}

	static public function getBindingModelName($modelAlias1, $modelAlias2) {
		$modelNames = array($modelAlias1, $modelAlias2);
		sort($modelNames);
		return $modelNames[0] . $modelNames[1];
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
		
		$modelNamespace = Garp_Spawn_Util::camelcased2underscored($modelName);
		$relationColumn	= $modelNamespace . (is_null($index) ? '' : (string)$index) . '_id';
		return $relationColumn;
	}

	/**
	 * @param Garp_Spawn_Model_Base $model
	 */
	protected function _setModel($model) {
		$this->_model = $model;
		return $this;
	}

	protected function _mirrorHasManyRelations() {
		$singularRelations = $this->getSingularRelations();

		foreach ($singularRelations as $relationName => $relation) {
			if (!$relation->inverse) {
				continue;
			}

			$this->_mirrorRelationsInModel($relation);
		}
	}
	
	protected function _mirrorHabtmRelations() {
		$habtmRelations = $this->getRelations('type', array('hasAndBelongsToMany'));

		foreach ($habtmRelations as $relationName => $relation) {
			$this->_mirrorRelationsInModel($relation);
		}
	}
	
	protected function _mirrorRelationsInModel(Garp_Spawn_Relation $relation) {
		$this->_throwErrorIfRelatedModelDoesNotExist($relation);

		$modelSet 			= Garp_Spawn_Model_Set::getInstance();
		$remoteModel 		= $modelSet[$relation->model];
		$mirroredRelation 	= $relation->mirror($remoteModel);
		$remoteModel->relations->addRaw($mirroredRelation);
	}
	
	protected function _throwErrorIfRelatedModelDoesNotExist(Garp_Spawn_Relation $relation) {
		$model 		= $this->getModel();
		$modelSet 	= Garp_Spawn_Model_Set::getInstance();

		if (array_key_exists($relation->model, $modelSet)) {
			return;
		}

		$error = sprintf(
			self::ERROR_RELATION_TO_NON_EXISTING_MODEL,
			$model->id,
			$relation->type,
			$relation->model
		);

		throw new Exception($error);
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
	
	/**
	 * Adds the relation to the relation set, if it's singular.
	 * @param	Array						$relationSet	The set to add the relation to
	 * @param	String						$relName		The relation name
	 * @param	Garp_Spawn_Relation	$relation		The relation instance, plural or singular
	 * @return 	Array 										The relation set with the added singular relation
	 */
	protected function _addSingularRelation(array $relationSet, $relName, Garp_Spawn_Relation $relation) {
		if ($relation->isSingular()) {
			$relationSet[$relName] = $relation;
		}
		
		return $relationSet;
	}

	protected function _addDefaultBaseRelations() {
		if (get_class($this->getModel()) !== 'Garp_Spawn_Model_Base') {
			return;
		}

		foreach ($this->_defaultBaseRelations as $defRelName => $defRelParams) {
			$this->add($defRelName, $defRelParams);
		}
	}
}