<?php
/**
 * Garp_Content_Manager
 * Handles various crud methods
 * @author Harmen Janssen, David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Garp_Content_Manager {
	/**
	 * The currently manipulated model
	 * @var Garp_Model
	 */
	protected $_model;

	
	/**
	 * Class constructor
	 * @param Garp_Model|String $model The model to execute methods on
	 * @return Void
	 */
	public function __construct($model) {
		if (is_string($model)) {
			$model = new $model();
		}
		$this->_model = $model;
		if (!$this->_model instanceof Garp_Model) {
			throw new Garp_Content_Exception('The selected model must be a Garp_Model.');
		}
	}
	
	
	/**
	 * Return the model
	 * @return Garp_Model
	 */
	public function getModel() {
		return $this->_model;
	}

	
	/**
	 * Fetch results from a model
	 * @param Array $options Various fetching options (e.g. limit, sorting, etc.)
	 * @return Array
	 */
	public function fetch(array $options = null) {
		try {
			$this->_checkAcl('fetch');
		} catch (Garp_Auth_Exception $e) {
			$this->_checkAcl('fetch_own');
		}

		if ($this->_model instanceof Garp_Model_Db) {
			$options = $options instanceof Garp_Util_Configuration ? $options : new Garp_Util_Configuration($options);
			$options
				->setDefault('sort', array())
				->setDefault('start', null)
				->setDefault('limit', null)
				->setDefault('query', false)
				->setDefault('fields', Zend_Db_Table_Select::SQL_WILDCARD)
				->setDefault('group', array())
				->setDefault('rule', null)
				->setDefault('bindingModel', null)
				->setDefault('filterForeignKeys', false)
			;
			$options['sort']   = (array)$options['sort'];
			$options['fields'] = (array)$options['fields'];
			$tableName         = $this->_model->getJointView() ?: $this->_model->getName();
			$options           = (array)$options;
			$modelInfo         = $this->_model->info();
			$referenceMap      = $modelInfo['referenceMap'];

			// SELECT
			// ============================================================
			$select = $this->_model->select();
			$select->setIntegrityCheck(false);

			// FROM
			// ============================================================
			$fields = $options['fields'];
			// If filterForeignKeys is true, filter out the foreign keys 
			if ($options['filterForeignKeys']) {
				$fields = $this->_filterForeignKeyColumns($fields, $referenceMap);
			}
			$select->from($tableName, $fields);

			// WHERE
			// ============================================================
			if ($options['query'] && !empty($options['query'])) {
				$related = array();
				/**
				 * Check for other model names in the conditions. These are indicated by a dot (".") in the name.
				 * If available, add these models as joins to the Select object.
				 * The format is <related-model-name>.<primary-key> => <value>.
				 */
				foreach ($options['query'] as $column => $value) {
					if (strpos($column, '.') !== false) {
						$related[$column] = $value;
						unset($options['query'][$column]);
					}
				}

				$this->_addJoinClause($select, $related, $options['rule'], $options['bindingModel']);

				// Add WHERE clause if there still remains something after 
				// filtering.
				if ($options['query']) {
					$select->where($this->_createWhereClause($options['query']));
				}
			}

			// GROUP
			// ============================================================
			$select->group($options['group']);

			// ORDER
			// ============================================================
			// Prefix native columns with the table name (e.g. "id" becomes 
			// "Thing.id")
			// Note that we create a mock table object based on the joint view 
			// to collect column info.
			// This should be more accurate than reading that info from the table.
			$mockTable = new Zend_Db_Table(array(
				Zend_Db_Table_Abstract::NAME => $tableName,
				Zend_Db_Table_Abstract::PRIMARY => $this->_model->info(Zend_Db_Table_Abstract::PRIMARY)
			));
			$nativeColumns = $mockTable->info(Zend_Db_Table_Abstract::COLS);

			$select->order(array_map(function ($s) use ($tableName, $nativeColumns) {
				$nativeColTest = preg_replace('/(ASC|DESC)$/', '', $s);
				$nativeColTest = trim($nativeColTest);

				if (in_array($nativeColTest, $nativeColumns) && strpos($s, '.') === false) {
					$s = $tableName.'.'.$s;
				}
				return $s;
			}, $options['sort']));

			// LIMIT
			// ============================================================
			// Do not limit when a COUNT(*) is performed, this skews results.
			$isCountQuery = count($fields) == 1 && !empty($fields[0]) && strtolower($fields[0]) == 'count(*)';
			if (!$isCountQuery) {
				$select->limit($options['limit'], $options['start']);
			}			
			$results = $this->_model->fetchAll($select)->toArray();
		} else {
			$results = $this->_model->fetchAll();
		}

		foreach ($results as &$result) {
			foreach ($result as $column => $value) {
				if (strpos($column, '.') !== false) {
					$keyParts = explode('.', $column, 2);
					$newKey = $keyParts[1];
					$relModelKey = Garp_Util_String::strReplaceOnce($this->_model->getNameWithoutNamespace(), '', $keyParts[0]);
					$result['relationMetadata'][$relModelKey][$newKey] = $value;
					unset($result[$column]);
				}
			}
		}
		return $results;
	}


	/**
	 * Count records according to given criteria
	 * @param Array $options Options
	 * @return Int
	 */
	public function count(array $options = null) {
		if ($this->_model instanceof Garp_Model_Db) {
			$options['fields'] = 'COUNT(*)';
			try {
				$result = $this->fetch($options);
				return !empty($result[0]['COUNT(*)']) ? $result[0]['COUNT(*)'] : 0;
			} catch (Zend_Db_Statement_Exception $e) {
				/**
				 * @todo When fetching results
				 * filtered using a HABTM relationship, extra
				 * meta field are returned from the binding table.
				 * This results in an SQL error; when using COUNT()
				 * and returning results from multiple tables a 
				 * GROUP BY clause is mandatory. This must be fixed in 
				 * the future.
				 */
				return 1000;
			}
		} else {
			return $this->_model->count();
		}
	}


	/**
	 * Create new record
	 * @param Array $data The new record's data as key => value pairs.
	 * @return Mixed The primary key of the new record
	 */
	public function create(array $data) {
		$this->_checkAcl('create');
		$pk = $this->_model->insert($data);
		return $pk;
	}
	
	
	/**
	 * Update existing record
	 * @param Array $data The record's new data
	 * @return Int The amount of updated rows
	 */
	public function update(array $data) {
		// check if primary key is available
		$prim = $this->_model->info(Zend_Db_Table::PRIMARY);
		if (!is_array($prim)) {
			$prim = array($prim);
		}

		$where = array();
		foreach ($prim as $key) {
			if (!array_key_exists($key, $data)) {
				throw new Garp_Content_Exception('Primary key '.$key.' not available in data');
			}
			$where[] = $this->_model->getAdapter()->quoteInto($key.' = ?', $data[$key]);
			unset($data[$key]);
		}
		$where = implode(' AND ', $where);

		try {
			/**
 		 	 * First, see if the user is allowed to update everything
 		 	 */
			$this->_checkAcl('update');
		} catch (Garp_Auth_Exception $e) {
			/**
 		 	 * If that fails, check if the user is allowed to update her own material AND if the current item is hers.
 		 	 */
			$this->_checkAcl('update_own');

			/**
 			 * Good, the user is allowed to 'update_own'. In that case we have to check if the current item is actually the user's.
 			 */
			if (!$this->_itemBelongsToUser($data, $where)) {
				throw new Garp_Auth_Exception('You are only allowed to edit your own material.');
			}
		}
		return $this->_model->update($data, $where);
	}
	
	
	/**
	 * Delete (a) record(s)
	 * @param Array $where WHERE clause, specifying which records to delete
	 * @return Boolean
	 */
	public function destroy(array $where) {
		$where = $this->_createWhereClause($where, 'AND', false);
		try {
			/**
 		 	 * First, see if the user is allowed to update everything
 		 	 */
			$this->_checkAcl('destroy');
			$this->_model->delete($where);
		} catch (Garp_Auth_Exception $e) {
			/**
 		 	 * If that fails, check if the user is allowed to update her own material AND if the current item is hers.
 		 	 */
			$this->_checkAcl('destroy_own');

			/**
 			 * Good, the user is allowed to 'destroy_own'. In that case we have to check if the current item is actually the user's.
 			 */
			$rows = $this->_model->fetchAll($where);
			foreach ($rows as $row) {
				if (!$this->_itemBelongsToUser($row->toArray())) {
					throw new Garp_Auth_Exception('You are only allowed to delete your own material.');
				}
				$row->delete();
			}
		}
	}
	
	
	/**
	 * Relate entities to each other, optionally removing previous existing relations.
	 * @param Array $options
	 * @return Boolean
	 */
	public function relate(array $options) {
		$this->_checkAcl('relate');

		extract($options);
		if (!isset($primaryKey) || !isset($model) || !isset($foreignKeys)) {
			throw new Garp_Content_Exception('Not enough options. "primaryKey", "model" and "foreignKeys" are required.');
		}
		$model         = Garp_Content_Api::modelAliasToClass($model);
		$primaryKey    = (array)$primaryKey;
		$foreignKeys   = (array)$foreignKeys;
		$rule          = isset($rule) ? $rule : null;
		$rule2         = isset($rule2) ? $rule2 : null;
		$bindingModel  = isset($bindingModel) ? 'Model_' . $bindingModel : null;
		$bidirectional = isset($bidirectional) ? $bidirectional : null;

		if (array_key_exists('unrelateExisting', $options) && $options['unrelateExisting']) {
			Garp_Content_Relation_Manager::unrelate(array(
				'modelA'        => $this->_model,
				'modelB'        => $model,
				'keyA'          => $primaryKey,
				'rule'          => $rule,
				'ruleB'         => $rule2,
				'bindingModel'  => $bindingModel,
				'bidirectional' => $bidirectional,
			));
		}

		$success = $attempts = 0;

		foreach ($foreignKeys as $i => $relationData) {
			if (!array_key_exists('key', $relationData)) {
				throw new Garp_Content_Exception('Foreign key is a required key.');
			}

			$foreignKey  = $relationData['key'];
			$extraFields = array_key_exists('relationMetadata', $relationData) ? $relationData['relationMetadata'] : array();

			if (Garp_Content_Relation_Manager::relate(array(
				'modelA'        => $this->_model,
				'modelB'        => $model,
				'keyA'          => $primaryKey,
				'keyB'          => $foreignKey,
				'extraFields'   => $extraFields,
				'rule'          => $rule,
				'ruleB'         => $rule2,
				'bindingModel'  => $bindingModel,
				'bidirectional' => $bidirectional,
			))) {
				$success++;
			}
			$attempts++;
		}
		return $success == $attempts;
	}
	
	
	public function unrelate() {
		throw new Exception('This method is not yet implemented.');
		//	TODO
	}


	public function unrelateAll() {
		throw new Exception('This method is not yet implemented.');
		//	TODO
	}


	/**
	 * Create a WHERE clause for use with Zend_Db_Select
	 * @param Array $query WHERE options
	 * @param String $separator AND/OR
	 * @param Boolean $useJointView Wether to use the *_joint view or the table.
	 * @return String WHERE clause
	 */
	protected function _createWhereClause(array $query, $separator = 'AND', $useJointView = true) {
		$where = array();
		$adapter = $this->_model->getAdapter();
		$nativeColumns = $this->_model->info(Zend_Db_Table_Abstract::COLS);
		if ($useJointView) {
			$tableName = $this->_model->getJointView() ?: $this->_model->getName();
			$mockTable = new Zend_Db_Table(array(
				Zend_Db_Table_Abstract::NAME => $tableName,
				Zend_Db_Table_Abstract::PRIMARY => $this->_model->info(Zend_Db_Table_Abstract::PRIMARY)
			));
			$nativeColumns = $mockTable->info(Zend_Db_Table_Abstract::COLS);
		} else {
			$tableName = $this->_model->getName();
		}
		
		foreach ($query as $column => $value) {
			if (strtoupper($column) === 'OR' && is_array($value)) {
				$where[] = $this->_createWhereClause($value, 'OR');
			} elseif (is_array($value)) {
				$where[] = $adapter->quoteInto($adapter->quoteIdentifier($tableName).'.'.$column.' IN(?)', $value);
			} elseif (is_null($value)) {
				if (substr($column, -2) == '<>') {
					$column = preg_replace('/<>$/', '', $column);
					$where[] = $column.' IS NOT NULL';
				} else {
					$where[] = $column.' IS NULL';
				}
			} elseif (is_scalar($value)) {				
				// Use $refColumn to see if this column is native to the current 
				// model.
				$refColumn = null;
				if (!preg_match('/(>=?|<=?|like|<>)/i', $column, $matches)) {
					$refColumn = $column;
					$column = $adapter->quoteIdentifier($column).' =';
				} else {
					// explode column so the actual column name can be quoted
					$parts = explode(' ', $column);
					$refColumn = $parts[0];
					$column = $adapter->quoteIdentifier($parts[0]).' '.$parts[1];
				}

				if (strpos($refColumn, '.') === false && in_array($refColumn, $nativeColumns)) {
					$column = $adapter->quoteIdentifier($tableName).'.'.$column;
				}
				$where[] = $adapter->quoteInto($column.' ?', $value);
			}
		}
		return '('.implode(" $separator ", $where).')';
	}


	/**
	 * Add a JOIN clause to a Zend_Db_Select object
	 * @param Zend_Db_Select $select The select object
	 * @param Array $related Collection of related models
	 * @param String $rule Used to figure out the relationship metadata from the referencemap
	 * @param String $bindingModel Binding model used in HABTM relations
	 * @return Void
	 */
	protected function _addJoinClause(Zend_Db_Select $select, array $related, $rule = null, $bindingModel = null) {
		foreach ($related as $filterModelName => $filterValue) {
			$fieldInfo = explode('.', $filterModelName, 2);
			$filterModelName = Garp_Content_Api::modelAliasToClass($fieldInfo[0]);

			$filterColumn = $fieldInfo[1];
			$filterModel = new $filterModelName();
			/**
			 * Determine wether a negation clause (e.g. !=) is requested
			 * and normalize the filterColumn.
			 */
			$negation = strpos($filterColumn, '<>') !== false;
			$filterColumn = str_replace(' <>', '', $filterColumn);

			if ($filterModelName === get_class($this->_model)) {
				/*	This is a homophile relation and the current condition touches the homophile model.
					The following condition prevents a 'relatable' list to include the current record,
					because a record cannot be related to itself.
				*/
				$select->where($filterModel->getJointView().'.'.$filterColumn.' != ?', $filterValue);
			}

			try {
				// the other model is a child
				$reference = $filterModel->getReference(get_class($this->_model), $rule);
				$this->_addHasManyClause(array(
					'select'		=> $select,
					'filterModel'	=> $filterModel,
					'reference' 	=> $reference,
					'filterColumn'	=> $filterColumn,
					'filterValue'	=> $filterValue,
					'negation'		=> $negation
				));
			} catch (Zend_Db_Table_Exception $e) {
				try {
					// the other model is the parent
					$reference = $this->_model->getReference(get_class($filterModel), $rule);
					$this->_addBelongsToClause(array(
						'select'		=> $select,
						'reference'		=> $reference,
						'filterColumn'	=> $filterColumn,
						'filterValue'	=> $filterValue,
						'negation'		=> $negation
					));
				} catch (Zend_Db_Table_Exception $e) {
					try {
						// the models are equal; a binding model is needed
						$this->_addHasAndBelongsToManyClause(array(
							'select'		=> $select,
							'filterModel'	=> $filterModel,
							'filterColumn'	=> $filterColumn,
							'filterValue'	=> $filterValue,
							'negation'		=> $negation,
							'bindingModel'  => $bindingModel
						));
					} catch (Zend_Db_Table_Exception $e) {
						throw $e;
					}
				}
			}
		}
	}
	
	
	/**
	 * Add a hasMany/hasOne filter to a Zend_Db_Select object.
	 * Example query:
	 * SELECT * FROM users
	 * INNER JOIN comments ON comments.user_id = users.id
	 * WHERE comments.id = 5
	 * 
	 * @param Array $options Collection of options containing;
	 * ['select'] 		Zend_Db_Select	The select object
	 * ['filterModel']	Garp_Model_Db 	The filtering model
	 * ['filterColumn']	String			The column used as the query filter
	 * ['filterValue']	Mixed			The value used as the query filter
	 * ['reference']	Array			The relation as in the reference map of the model
	 * ['negation']		Boolean			Wether the query should include or exclude 
	 * 									matches found by $filterValue
	 * @return Void
	 */
	protected function _addHasManyClause(array $options) {
		// keys of $options available in the local space as variables
		extract($options);
		$select->distinct();
		
		$filterModelName = $filterModel->getName();
		$thisTableName = $this->_model->getJointView() ?: $this->_model->getName();
		// in the case of homophile relationships...
		if ($filterModelName == $thisTableName) {
			$filterModelName = $filterModelName.'_2';
		}
		
		foreach ($reference['refColumns'] as $i => $column) {
			if ($column === $filterColumn) {
				$joinColumn = $thisTableName.'.'.$column;
				/**
				 * Map the index of the found column to the foreign key column.
				 * Note that these columns are paired by index, 
				 * so the order in the reference map must be the same.
				 */
				$foreignKeyColumn = $filterModelName.'.'.$reference['columns'][$i];
				break;
			}
		}
		if (!isset($joinColumn)) {
			throw new Garp_Content_Exception('The relationship between '.get_class($this->_model).' and '.
											 get_class($filterModel).' cannot be determined from the '.
											 'reference map.');
		}
		
		$bindingCondition = $foreignKeyColumn.' = '.$joinColumn;
		$bindingCondition .= $filterModel->getAdapter()->quoteInto(' AND '.$filterModelName.'.'.$filterColumn.' = ?', $filterValue);
		
		$select->joinLeft(
			array($filterModelName => $filterModelName),
			$bindingCondition,
			array()
		);		
		/**
		 * Cause MySQL developers are fucking cunts, ([NULL] != 35) === FALSE.
		 * So in the case of a negation an extra WHERE clause is needed that
		 * checks for NULL.
		 */
		$nullFix = $negation ? " OR $foreignKeyColumn IS NULL" : '';
		$operator = $negation ? '!=' : '=';
		$select->where("({$filterModelName}.$filterColumn $operator ?".$nullFix.")", $filterValue);
	}
	
	
	/**
	 * Add a belongsto filter to a Zend_Db_Select object.
	 * Example query:
	 * SELECT * FROM comments
	 * WHERE comments.user_id = 35 
	 * 
	 * @param Array $options Collection of options containing;
	 * ['select'] 		Zend_Db_Select	The select object
	 * ['filterColumn']	String			The column used as the query filter
	 * ['filterValue']	Mixed			The value used as the query filter
	 * ['reference']	Array			The relation as in the reference map of the model
	 * ['negation']		Boolean			Wether the query should include or exclude 
	 * 									matches found by $filterValue
	 * @return Void
	 */
	protected function _addBelongsToClause(array $options) {
		$thisTableName = $this->_model->getJointView() ?: $this->_model->getName();
		// keys of $options available in the local space as variables
		extract($options);
		foreach ($reference['refColumns'] as $i => $column) {
			if ($column === $filterColumn) {
				/**
				 * Map the index of the found column to the foreign key column.
				 * Note that these columns are paired by index, 
				 * so the order in the reference map must be the same.
				 */
				$filterColumn = $thisTableName.'.'.$reference['columns'][$i];
				break;
			}
		}
		/**
		 * Cause MySQL developers are fucking cunts, ([NULL] != 35) === FALSE.
		 * So in the case of a negation an extra WHERE clause is needed that
		 * checks for NULL.
		 */
		$nullFix = $negation ? " OR $filterColumn IS NULL" : '';
		$operator = $negation ? '!=' : '=';
		$select->where("($filterColumn $operator ?$nullFix)", $filterValue);
	}
	
	
	/**
	 * Add a hasAndBelongsToMany filter to a Zend_Db_Select object.
	 * Example query:
	 * SELECT *
	 * FROM tags
	 * LEFT JOIN tags_users ON tags_users.tag_id = tags.id AND user_id = 35
	 * INNER JOIN `users` ON users.id = tags_users.user_id
	 * WHERE user_id 35 // in the case of negation, this'll be "WHERE user_id IS NULL"
	 * 
	 * @param Array $options Collection of options containing;
	 * ['select'] 		Zend_Db_Select	The select object
	 * ['filterModel']	Garp_Model_Db 	The filtering model
	 * ['filterColumn']	String			The column used as the query filter
	 * ['filterValue']	Mixed			The value used as the query filter
	 * ['negation']		Boolean			Wether the query should include or exclude 
	 * 									matches found by $filterValue
	 * @return Void
	 */
	protected function _addHasAndBelongsToManyClause(array $options) {
		// keys of $options available in the local space as variables
		extract($options);
		if (!isset($bindingModel)) {
			$modelNames = array($this->_model->getNameWithoutNamespace(), $filterModel->getNameWithoutNamespace());
			sort($modelNames);
			$bindingModelName = 'Model_'.implode('', $modelNames);
		} else {
			$bindingModelName = 'Model_'.$bindingModel;
		}
		$bindingModel = new $bindingModelName();
		$thisTableName = $this->_model->getJointView() ?: $this->_model->getName();
		$bindingModelTable = $bindingModel->getName();

		$reference = $bindingModel->getReference(get_class($this->_model));
		foreach ($reference['refColumns'] as $i => $column) {
			if ($column === $filterColumn) {
				$bindingModelForeignKeyField = $reference['columns'][$i];
				$foreignKeyField = $column;
				break;
			}
		}

		$reference = $bindingModel->getReference(get_class($filterModel), $this->_findSecondRuleKeyForHomophiles($filterModel, $bindingModel));
		foreach ($reference['refColumns'] as $i => $column) {
			if ($column === $filterColumn) {
				$filterField = $reference['columns'][$i];
				break;
			}
		}

		$bindingCondition = $bindingModelTable.'.'.$bindingModelForeignKeyField.' = '.$thisTableName.'.'.$foreignKeyField;
		$bindingCondition .= $bindingModel->getAdapter()->quoteInto(' AND '.$bindingModelTable.'.'.$filterField.' = ?', $filterValue);
		if ($this->_isHomophile($filterModel)) {
			$bindingCondition .= ' OR '.$bindingModelTable.'.'.$filterField.' = '.$thisTableName.'.'.$foreignKeyField;
			$bindingCondition .= $bindingModel->getAdapter()->quoteInto(' AND '.$bindingModelTable.'.'.$bindingModelForeignKeyField.' = ?', $filterValue);
		}
		
		$tmpBindingColumns = $bindingModel->info(Zend_Db_Table::COLS);
		$bindingColumns = array();
		foreach ($tmpBindingColumns as $bc) {
			if (in_array($bc, array($bindingModelForeignKeyField, $filterField))) {
				continue;
			}
			$bindingColumns[$bindingModel->getNameWithoutNamespace().'.'.$bc] = $bc;
		}
		
		$select->joinLeft($bindingModelTable, $bindingCondition, $bindingColumns);

		if ($negation) {
			$select->where($bindingModelTable.'.'.$filterField.' IS NULL');
		} else {
			$select->where($bindingModelTable.'.'.$filterField.' = ?', $filterValue);
			if ($this->_isHomophile($filterModel)) {
				$select->orWhere($bindingModelTable.'.'.$bindingModelForeignKeyField.' = ?', $filterValue);
				$select->group('id');
			}
		}

		// Allow behaviors to modify the SELECT object
		$bindingModel->notifyObservers('beforeFetch', array($bindingModel, $select));
	}
	

	/**
 	 * Filter columns that are foreign keys.
 	 * @param Array $fields All columns
 	 * @param Array $referenceMap The model's referenceMap
 	 * @return Array
 	 */
	protected function _filterForeignKeyColumns($fields, $referenceMap) {
		$out = array();
		$foreignKeys = array();
		// create an array of foreign keys...
		foreach ($referenceMap as $relName => $relConfig) {
			$foreignKeys = array_merge($foreignKeys, (array)$relConfig['columns']);
		}
		// ...and return the values that are not in that array.
		return array_diff($fields, $foreignKeys);
	}

	
	/**
	 * Checks if this is a homophile relation: an association between records of the same model.
	 * @param Garp_Model_Db $filterModel
	 * @return Boolean
	 */
	protected function _isHomophile(Garp_Model_Db $filterModel) {
		return get_class($this->_model) === get_class($filterModel);
	}


	/**
	* In case of a homophile relation, this function returns the key to the second rule, to prevent the same rule being returned twice, because both rules in a homophile binding model point to the same related model.
	* @param Garp_Model_Db $filterModel
	* @return StringOrNull Returns the name of the second rule key in the reference map, if this is a homophile relation. Otherwise, null is returned.
	*/
	protected function _findSecondRuleKeyForHomophiles($filterModel, $bindingModel) {
		$homophileSecondRuleKey = null;

		if ($this->_isHomophile($filterModel)) {
			$bindingReferenceMap = $bindingModel->info(Zend_Db_Table_Abstract::REFERENCE_MAP);

			$foundRelevantRule = false;
			foreach ($bindingReferenceMap as $ruleKey => $rule) {
				if ($rule['refTableClass'] === get_class($this->_model)) {
					if ($foundRelevantRule) {
						$homophileSecondRuleKey = $ruleKey;
					}
					$foundRelevantRule = !$foundRelevantRule;
				}
			}
		}

		return $homophileSecondRuleKey;
	}
	
	
	/**
	 * Check to see if the current model supports the requested method
	 * @param String $method The method
	 * @return Boolean 
	 * @throws Garp_Content_Exception If the method is not supported
	 */
	protected function _checkAcl($method) {
		if (!Garp_Auth::getInstance()->isAllowed(get_class($this->_model), $method)) {
			throw new Garp_Auth_Exception('You are not allowed to execute the requested action.');
		}
	}


	/**
 	 * Check a record belongs to the currently logged in user.
 	 * This check is based on the author_id column.
 	 * @param Array $data The record data. Primary key must be present here.
 	 * @param String $where A WHERE clause to find the record
 	 * @return Boolean
 	 */
	protected function _itemBelongsToUser($data, $where = false) {
		$userData = Garp_Auth::getInstance()->getUserData();
		$userId = $userData['id'];
		if (!array_key_exists('author_id', $data)) {
			if (!$where) {
				return false;
			}
			// fetch the record based on the given WHERE clause
			$row = $this->_model->fetchRow($where);
			if (!$row || !$row->author_id) {
				return false;
			}
			$data = $row->toArray();
		}
		return $userId == $data['author_id'];
	}
}
