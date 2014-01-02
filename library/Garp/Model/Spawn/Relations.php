<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Model_Spawn_Relations {
	/** @var Array $_fields	Associative array, where the key is the name of the relation, and the value a Garp_Model_Spawn_Relation object. */
	protected $_relations = array();

	/** @var Garp_Model_Spawn_Model $_model */
	protected $_model;
	
	static protected $_defaultRelations = array(
		'Author' => array(
			'model' => 'User',
			'type' => 'hasOne',
			'inverse' => false
		),
		'Modifier' => array(
			'model' => 'User',
			'type' => 'hasOne',
			'inverse' => false,
			'editable' => false
		)
	);
	
	
	public function __construct(Garp_Model_Spawn_Model $model, StdClass $config) {
		$this->_model = $model;

		foreach ($config as $relationName => $relationParams) {
			$this->add($relationName, $relationParams);
		}
	}
	

	/**
	 * @param String $filterPropName Garp_Model_Spawn_Relation property to filter the request by
	 * @param Mixed $filterPropValue Value of the Garp_Model_Spawn_Relation property the result should contain. Can also be an array, in which it will be considered an OR query.
	 */
	public function getRelations($filterPropName = null, $filterPropValue = null) {
		if ($filterPropName) {
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


	public function add($name, StdClass $params) {
		if (!array_key_exists($name, $this->_relations))
			$this->_relations[$name] = new Garp_Model_Spawn_Relation($this->_model, $name, $params);
		else throw new Exception("The '{$name}' relation is already registered in the '{$this->_model->id}' model.");
	}
	

	/**
	 * @param Array &$models Numeric array of Garp_Model_Spawn_Model objects
	 */
	static public function defineDefaultRelations(Array &$models) {
		foreach ($models as &$model) {
			foreach (self::$_defaultRelations as $defRelName => $defRelParams) {
				$model->relations->add($defRelName, (object)$defRelParams);
			}
		}
	}


	/**
	 * @param Array &$models Numeric array of Garp_Model_Spawn_Model objects
	 */
	static public function defineHasMany(Array &$models) {
		//	inverse singular relations to multiple relations from the other model
		//TODO - dit is niet altijd waar. Denk bijvoorbeeld aan User.profile_id; je kunt niet vanuit een profile meerdere Users gaan selecteren.
		foreach ($models as $model) {
			$singularRelations = $model->relations->getRelations('type', array('hasOne', 'belongsTo'));
			foreach ($singularRelations as $relationName => $relation) {
				if ($relation->inverse) {
					if (array_key_exists($relation->model, $models)) {
						$remoteModel = $models[$relation->model];
						$hasManyRelParams = new StdClass();

						$hasManyRelParams->type = 'hasMany';
						$hasManyRelParams->model = $model->id;
						$hasManyRelParams->column = 'id';
					
						//$remoteModel->relations->add($relationName, $hasManyRelParams);
						$remoteModel->relations->add($model->id, $hasManyRelParams);
					} else throw new Exception("The '{$model->id}' model defines a {$relation->type} relation to unexisting model '{$relation->model}'.");
				}
			}
		}
	}
	

	/**
	 * @param Array &$models Associative array of Garp_Model_Spawn_Model objects, where the key is the model id.
	 */
	static public function defineHasAndBelongsToMany(Array &$models) {
		$habtmConfig = Garp_Model_Spawn_ConfigFile::loadHabtm();
		if (!is_array($habtmConfig))
			throw new Exception("The hasAndBelongsToMany relations configuration should be an array.");

		foreach ($habtmConfig as $relation) {
			$relModelNames = explode("_", $relation);

			foreach ($relModelNames as $i => $relModelName) {
				if (!array_key_exists($relModelName, $models))
					throw new Exception("The hasAndBelongsToMany configuration defines non-existent model '{$relModelName}'");

				if (
					!(
						$i === 1 ||
						$relModelName === $relModelNames[(int)!$i]
					)
				) {
					$remoteModelName = $relModelNames[(int)!$i];
					$relParams = new StdClass();
					$relParams->type = 'hasAndBelongsToMany';
					$relParams->model = $remoteModelName;
					$models[$relModelName]->relations->add($remoteModelName, $relParams);
				}
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
		return Garp_Model_Spawn_Util::camelcased2underscored($modelName).(is_null($index) ? '' : (string)$index).'_id';
	}
}