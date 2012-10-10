<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Model_Spawn_Relations {
	/** @var Array $_fields	Associative array, where the key is the name of the relation, and the value a Garp_Model_Spawn_Relation object. */
	protected $_relations = array();

	/** @var Garp_Model_Spawn_Model $_model */
	protected $_model;

	/**
	 * @todo: deze default relations moeten naar de configlaag verplaatst worden.
	 */
	static protected $_defaultRelations = array(
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
	
	
	public function __construct(Garp_Model_Spawn_Model $model, array $config) {
		$this->_model = $model;

		foreach ($config as $relationName => &$relationParams) {
			$this->add($relationName, $relationParams);
		}
	}


	/**
	 * @param String $filterPropName Garp_Model_Spawn_Relation property to filter the request by
	 * @param Mixed $filterPropValue Value of the Garp_Model_Spawn_Relation property the result should contain. Can also be an array, in which it will be considered an OR query.
	 * @return Array Associative array, where the key is the name of the relation, and the value a Garp_Model_Spawn_Relation object.
	 */
	public function getRelations($filterPropName = null, $filterPropValue = null) {
		if ($filterPropName) {
			if (count(func_get_args()) !== 2) {
				throw new Exception(get_class($this) . "::getRelations() needs either 0 or 2 arguments.");
			}

			$out = array();
			foreach ($this->_relations as $relName => $rel) {
				$filterPropValue = (array)$filterPropValue;
				foreach ($filterPropValue as $v) {
					if ($rel->{$filterPropName} == $v) {
						$out[$relName] = $rel;
						break;
					}
				}
			}
			return $out;
		} else return $this->_relations;
		
	}
	
	
	public function getRelation($name) {
		if (array_key_exists($name, $this->_relations))
			return $this->_relations[$name];
		else throw new Exception("The '{$name}' relation was not registered.");
	}


	public function add($name, array $params, $preventDoubles = true) {
		if (!array_key_exists($name, $this->_relations)) {
			$this->_relations[$name] = new Garp_Model_Spawn_Relation($this->_model, $name, $params);
			ksort($this->_relations);
		} elseif ($preventDoubles) {
			throw new Exception("You're trying to add the '{$name}' {$params['type']} relation, but there already is a {$name} {$this->_relations[$name]->type} relation registered in the '{$this->_model->id}' model.");
		}
	}
	

	/**
	 * @param Array &$models Numeric array of Garp_Model_Spawn_Model objects
	 */
	static public function defineDefaultRelations(Garp_Model_Spawn_ModelSet &$models) {
		foreach ($models as &$model) {
			foreach (self::$_defaultRelations as $defRelName => $defRelParams) {
				if (!count($model->relations->getRelations('name', $defRelName))) {
					$model->relations->add($defRelName, $defRelParams);
				}
			}
		}
	}


	/**
	 * @param Array &$models Numeric array of Garp_Model_Spawn_Model objects
	 */
	static public function defineHasMany(Garp_Model_Spawn_ModelSet &$models) {
		//	inverse singular relations to multiple relations from the other model
		foreach ($models as &$model) {
			$singularRelations = $model->relations->getRelations('type', array('hasOne', 'belongsTo'));

			foreach ($singularRelations as $relationName => $relation) {
				if ($relation->inverse) {
					if (array_key_exists($relation->model, $models)) {
						$remoteModel = &$models[$relation->model];

						$hasManyRelParams = array();
						$hasManyRelParams['type'] = 'hasMany';
						$hasManyRelParams['model'] = $model->id;
						$hasManyRelParams['column'] = 'id';
						$hasManyRelParams['editable'] = $relation->type !== 'belongsTo';
						$hasManyRelParams['oppositeRule'] = $relationName;
						$hasManyRelParams['weighable'] = $relation->weighable;

						$remoteModel->relations->add($model->id, $hasManyRelParams, false);
					} else throw new Exception("The '{$model->id}' model defines a {$relation->type} relation to unexisting model '{$relation->model}'.");
				}
			}
		}
	}


	/**
	 * @param Array &$models Numeric array of Garp_Model_Spawn_Model objects
	 */
	static public function defineHasAndBelongsToMany(Garp_Model_Spawn_ModelSet &$models) {
		//	inverse singular relations to multiple relations from the other model
		foreach ($models as &$model) {
			$habtmRelations = $model->relations->getRelations('type', array('hasAndBelongsToMany'));

			foreach ($habtmRelations as $relationName => $relation) {
				if (array_key_exists($relation->model, $models)) {
					$remoteModel = &$models[$relation->model];

					$habtmRelParams = array();
					$habtmRelParams['type'] = 'hasAndBelongsToMany';
					$habtmRelParams['model'] = $model->id;
					$habtmRelParams['column'] = 'id';
					$habtmRelParams['editable'] = true;
					$habtmRelParams['oppositeRule'] = $relationName;
					$habtmRelParams['weighable'] = $relation->weighable;

					$remoteModel->relations->add($model->id, $habtmRelParams, false);
				} else throw new Exception("The '{$model->id}' model defines a {$relation->type} relation to unexisting model '{$relation->model}'.");
			}
		}
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
		return Garp_Model_Spawn_Util::camelcased2underscored($modelName) . (is_null($index) ? '' : (string)$index) . '_id';
	}
}