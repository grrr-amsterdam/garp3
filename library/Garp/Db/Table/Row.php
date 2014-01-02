<?php
/**
 * Garp_Db_Table_Row
 * Custom implementation of Zend_Db_Table_Row. Allows for automagic related result fetching.
 * @author Harmen Janssen, David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Table
 * @lastmodified $Date: $
 */
class Garp_Db_Table_Row extends Zend_Db_Table_Row_Abstract {
	/**
	 * Related rowsets
	 * @var Array
	 */
	protected $_related = array();
	
	
	protected $_virtual = array();
	
	
	/**
	 * Overwritten to also store $this->_related. This property was not returned, of course,
	 * so when serializing it would disappear. 
	 * @return array
	 */
	public function __sleep() {
		$props = parent::__sleep();
		$props[] = '_related';
		$props[] = '_virtual';
		return $props;
	}
	
	
	/**
     * ATTENTION:
     * This code is copied and altered from Zend_Db_Table_Abstract::findManyToManyRowset(). 
     * The alterations made are the following;
     * - manually trigger 'beforeFetch' callback on $intersectionTable and $matchTable
     * - manually trigger 'afterFetch' callback on $matchTable
     * - removed superfluous 'require' calls (Garp uses an autoloader)
     * - slightly changed the query, from:
     *     SELECT `m`.* FROM `tags_users` AS `i` INNER JOIN `tags` AS `m` ON `i`.`tag_id` = `m`.`id` WHERE (`i`.`user_id` = 18)
     *   to:
     *     SELECT `m`.* FROM `tags` AS `m` INNER JOIN `tags_users` AS `i` ON `i`.`tag_id` = `m`.`id` WHERE (`i`.`user_id` = 18)
     *   in order to make its meaning a little clearer, especially in the beforeFetch callback of the match table.
     * - changed space-indenting to tab-indenting ;-)
     * 
     * @param  string|Zend_Db_Table_Abstract  $matchTable
     * @param  string|Zend_Db_Table_Abstract  $intersectionTable
     * @param  string                         OPTIONAL $callerRefRule
     * @param  string                         OPTIONAL $matchRefRule
     * @param  Zend_Db_Table_Select           OPTIONAL $select
     * @return Zend_Db_Table_Rowset_Abstract Query result from $matchTable
     * @throws Zend_Db_Table_Row_Exception If $matchTable or $intersectionTable is not a table class or is not loadable.
     */
    public function findManyToManyRowset($matchTable, $intersectionTable, $callerRefRule = null, $matchRefRule = null, Zend_Db_Table_Select $select = null) {
		$db = $this->_getTable()->getAdapter();

		if (is_string($intersectionTable)) {
			$intersectionTable = $this->_getTableFromString($intersectionTable);
		}

		if (!$intersectionTable instanceof Zend_Db_Table_Abstract) {
			$type = gettype($intersectionTable);
			if ($type == 'object') {
				$type = get_class($intersectionTable);
			}
			throw new Zend_Db_Table_Row_Exception("Intersection table must be a Zend_Db_Table_Abstract, but it is $type");
		}

		// even if we are interacting between a table defined in a class and a
		// table via extension, ensure to persist the definition
		if (($tableDefinition = $this->_table->getDefinition()) !== null &&
			($intersectionTable->getDefinition() == null)) {
			$intersectionTable->setOptions(array(Zend_Db_Table_Abstract::DEFINITION => $tableDefinition));
		}

		if (is_string($matchTable)) {
			$matchTable = $this->_getTableFromString($matchTable);
		}

		if (! $matchTable instanceof Zend_Db_Table_Abstract) {
			$type = gettype($matchTable);
			if ($type == 'object') {
				$type = get_class($matchTable);
			}
			throw new Zend_Db_Table_Row_Exception("Match table must be a Zend_Db_Table_Abstract, but it is $type");
		}

		// even if we are interacting between a table defined in a class and a
		// table via extension, ensure to persist the definition
		if (($tableDefinition = $this->_table->getDefinition()) !== null &&
			($matchTable->getDefinition() == null)) {
			$matchTable->setOptions(array(Zend_Db_Table_Abstract::DEFINITION => $tableDefinition));
		}

		if ($select === null) {
			$select = $matchTable->select();
		} else {
			$select->setTable($matchTable);
		}

		// Use adapter from intersection table to ensure correct query construction
		$interInfo = $intersectionTable->info();
		$interDb   = $intersectionTable->getAdapter();
		$interName = $interInfo['name'];
		$interSchema = isset($interInfo['schema']) ? $interInfo['schema'] : null;
		$matchInfo = $matchTable->info();
		$matchName = $matchInfo['name'];
		$matchSchema = isset($matchInfo['schema']) ? $matchInfo['schema'] : null;

		$matchMap = $this->_prepareReference($intersectionTable, $matchTable, $matchRefRule);

		for ($i = 0; $i < count($matchMap[Zend_Db_Table_Abstract::COLUMNS]); ++$i) {
			$interCol = $interDb->quoteIdentifier('i' . '.' . $matchMap[Zend_Db_Table_Abstract::COLUMNS][$i], true);
			$matchCol = $interDb->quoteIdentifier('m' . '.' . $matchMap[Zend_Db_Table_Abstract::REF_COLUMNS][$i], true);
			$joinCond[] = "$interCol = $matchCol";
		}
		$joinCond = implode(' AND ', $joinCond);

		$select->from(array('m' => $matchName), Zend_Db_Select::SQL_WILDCARD, $matchSchema)
			   ->joinInner(array('i' => $interName), $joinCond, array(), $interSchema)
			   ->setIntegrityCheck(false);

		$callerMap = $this->_prepareReference($intersectionTable, $this->_getTable(), $callerRefRule);

		for ($i = 0; $i < count($callerMap[Zend_Db_Table_Abstract::COLUMNS]); ++$i) {
			$callerColumnName = $db->foldCase($callerMap[Zend_Db_Table_Abstract::REF_COLUMNS][$i]);
			$value = $this->_data[$callerColumnName];
			$interColumnName = $interDb->foldCase($callerMap[Zend_Db_Table_Abstract::COLUMNS][$i]);
			$interCol = $interDb->quoteIdentifier("i.$interColumnName", true);
			$interInfo = $intersectionTable->info();
			$type = $interInfo[Zend_Db_Table_Abstract::METADATA][$interColumnName]['DATA_TYPE'];
			$select->where($interDb->quoteInto("$interCol = ?", $value, $type));
		}

		// Manually trigger 'beforeFetch' thru the intersection table, so it may manipulate the SELECT object.
		$intersectionTable->notifyObservers('beforeFetch', array($intersectionTable, $select));
	
		// Same on the match table
		$matchTable->notifyObservers('beforeFetch', array($matchTable, $select));

		$stmt = $select->query();

		$config = array(
			'table'    => $matchTable,
			'data'     => $stmt->fetchAll(Zend_Db::FETCH_ASSOC),
			'rowClass' => $matchTable->getRowClass(),
			'readOnly' => false,
			'stored'   => true
		);

		$rowsetClass = $matchTable->getRowsetClass();
		if (!class_exists($rowsetClass)) {
			try {
				Zend_Loader::loadClass($rowsetClass);
			} catch (Zend_Exception $e) {
				throw new Zend_Db_Table_Row_Exception($e->getMessage(), $e->getCode(), $e);
			}
		}

		$rowset = new $rowsetClass($config);

		/**
		 * Perform 'afterFetch' callback on the match table
		 * NOTE: 'afterFetch' is NOT triggered on the intersection table. This might seem 
		 * inconsistent, but consider the following;
		 * 
		 * This delivers TagUser records to the observers:
		 *  $ TagUser.fetchAll()
		 * This is expected behavior.
		 * 
		 * However;
		 *  $ Tag.bindModel(User);
		 *  $ Tag.findManyToManyRowset(User);
		 * This would deliver User records to the observers of the TagUser model. This would be 
		 * very confusing, and more importantly, trigger incorrect behavior, because behaviors
		 * written to modify TagUser records would modify User records in ways impossible or
		 * simply unwanted.
		 * 
		 * This is why we've chosen to not trigger 'afterFetch' on the intersection table.
		 */
		$matchTable->notifyObservers('afterFetch', array($matchTable, $rowset));		
		return $rowset;
	}
	
		
	/**
	 * Return the value of the primary key(s) for this row.
	 * @return Mixed
	 */
	public function getPrimaryKey() {
		$primary = (array)$this->_getTable()->info(Zend_Db_Table::PRIMARY);
		$out = array();
		foreach ($primary as $key) {
			$out[] = $this->$key;
		}
		return count($out) > 1 ? $out : $out[0];
	}
	
	
	/**
	 * Set a related rowset as property of this row.
	 * @param String $binding An alias for storing the binding name
	 * @param Garp_Db_Table_Row|Garp_Db_Table_Rowset $rowset The related rowset
	 * @return Garp_Db_Table_Row $this
	 */
	public function setRelated($binding, $rowset) {
		$this->_related[$binding] = !is_null($rowset) ? $rowset : array();
		return $this;
	}
	
	
	/**
	 * Get a related rowset.
	 * @param String $binding The alias for the related rowset
	 * @return Garp_Db_Table_Row|Garp_Db_Table_Rowset
	 */
	public function getRelated($binding) {
		return $this->_related[$binding];
	}
	
	
	public function setVirtual($key, $value) {
		$this->_virtual[$key] = $value;
	}
	
	
	public function getVirtual() {
		return $this->_virtual;
	}
	
	
	/**
	 * Relate the fetched row to another record.
	 * @param String 					$modelName 	The other model
	 * @param Mixed 					$primaryKey	Primary key(s) of the other record. If compound key,
	 * 												the keys must be in the same order as the foreign key
	 * 												columns are specified in the referenceMap
	 * @param Garp_Util_Configuration	$options	Various options such as;
	 * ['rule']							String		The rule that defines this relation in the reference map
	 * ['postponeSave']					Boolean		Wether to save directly after filling the foreign key
	 * 												fields. If FALSE, the relation will not be saved 
	 * 												automatically. Client code will have to manually call               
	 * 												Garp_Db_Table_Row::save()                                           
	 * ['extraFields']					Array		Associative array of extra columns and values.                      
	 * 												In the case of a HABTM relationship it's sometimes                  
	 * 												possible to fill extra columns on the binding row.                  
	 * ['unrelateExisting]				Boolean		Wether to clear existing binding rows (in the case of HABTM)
	 * @return Mixed 								Either the result of the save() call, or, if $postponeSave is true, 
	 * 				 								$this, for chaining purposes.                                       
	 * @throws Garp_Db_Exception 					When trying to save an invalid relation.                            
	 */
	public function relate($modelName, $primaryKey, Garp_Util_Configuration $options = null) {
		$this->_normalizeOptionsForRelate($options);
		$thisModel		= $this->_getTable();
		$theOtherModel	= new $modelName();
		$primaryKey		= (array) $primaryKey;
		
		if (is_null($options['rule'])) {
			return $this->_resolveRelationRuleForRelate($modelName, $primaryKey, $options);
		}
		
		$refMap = $thisModel->getReference($modelName, $options['rule']);

		if (count($refMap['columns']) !== count($primaryKey)) {
			throw new Garp_Db_Exception('Primary key values don\'t match columns listed in the referenceMap.');
		}

		foreach ($refMap['columns'] as $i => $column) {
			$this->$column = $primaryKey[$i];
		}
		return !$options['postponeSave'] ? $this->save() : $this;
	}
	
	
	/**
	 * Destroy a relationship between two models
	 * @param String $modelName The other model
	 * @param Mixed $primaryKey Primary key(s) of the other record. If compound key,
	 * 							the keys must be in the same order as the foreign key
	 * 							columns are specified in the referenceMap
	 * @param String $rule The rule that defines this relation in the reference map
	 * @return Mixed
	 */
	public function unrelate($modelName, $primaryKey, $rule = null) {
		$thisModel		= $this->_getTable();
		$theOtherModel	= new $modelName();
		$primaryKey		= (array) $primaryKey;
		
		if (is_null($rule)) {
			return $this->_resolveRelationRuleForUnrelate($modelName, $primaryKey);
		}
		
		$refMap = $thisModel->getReference($modelName, $rule);
		if (count($refMap['columns']) !== count($primaryKey)) {
			throw new Garp_Db_Exception('Primary key values don\'t match columns listed in the referenceMap.');
		}

		foreach ($refMap['columns'] as $i => $column) {
			if ($this->$column == $primaryKey[$i]) {
				$this->$column = null;
			}
		}
		return $this->save();
	}


	/**
	 * Terminate the association with all records to this record.
	 * @param String $modelName The other model
	 * @return Int The amount of deleted rows
	 */
	public function unrelateAll($modelName) {
		$thisModel = $this->_getTable();
		$theOtherModel = new $modelName();

		switch ($this->_getRelationType($modelName)) {
			case 'belongsTo':
				if ($rule = $thisModel->findRuleForRelation($modelName)) {
					$reference = $thisModel->getReference($modelName, $rule);
					foreach ($reference['columns'] as $column) {
						$this->$column = null;
					}
					return $this->save();
				}
				throw new Garp_Db_Exception('Relationship between '.$modelName.' and '.get_class($thisModel).' not found.');
			break;
			case 'hasMany':
				if ($rule = $theOtherModel->findRuleForRelation(get_class($thisModel))) {
					$reference = $theOtherModel->getReference(get_class($thisModel), $rule);

					$data = array();
					$where = array();
					$thisPrimaryKey = (array)$this->getPrimaryKey();
					foreach ($reference['columns'] as $i => $column) {
						$data[$column] = null;
						$where[] = $theOtherModel->getAdapter()->quoteInto($column.' = ?', $thisPrimaryKey[$i]);
					}
					$where = implode(' AND ', $where);					
					return $theOtherModel->update($data, $where);
				}
				throw new Garp_Db_Exception('Relationship between '.$modelName.' and '.get_class($thisModel).' not found.');
			break;
			case 'hasAndBelongsToMany':
				$bindingModel = $thisModel->getBindingModel($theOtherModel);
				if ($rule = $bindingModel->findRuleForRelation(get_class($thisModel))) {
					$reference = $bindingModel->getReference(get_class($thisModel), $rule);

					$where = array();
					$thisPrimaryKey = (array)$this->getPrimaryKey();
					foreach ($reference['columns'] as $i => $column) {
						$where[] = $bindingModel->getAdapter()->quoteInto($column.' = ?', $thisPrimaryKey[$i]);
					}
					$where = implode(' AND ', $where);

					if (get_class($thisModel) === get_class($theOtherModel)) {
						//	homophile relation
						$rule2 = $bindingModel->findRuleForRelation(get_class($thisModel), $rule);
						$reference2 = $bindingModel->getReference(get_class($thisModel), $rule2);
						$where2 = array();

						foreach ($reference2['columns'] as $i => $column) {
							$where2[] = $bindingModel->getAdapter()->quoteInto($column.' = ?', $thisPrimaryKey[$i]);
						}
						$where = '('.$where.') OR ('.implode(' AND ', $where2).')';
					}

					return $bindingModel->delete($where);
				}
				throw new Garp_Db_Exception('Relationship between '.get_class($bindingModel).' and '.get_class($thisModel).' not found.');
			break;
		}
		return false;
	}

	
	/**
	 * We need a rule for saving a relation. If none is passed to self::relate, 
	 * this method is called to handle the absence. This method then recursively 
	 * calls self::relate again when it has resolved what rule to use.
	 * @param String $modelName The other model
	 * @param Mixed $primaryKey Primary key(s) of the other record. If compound key,
	 * 							the keys must be in the same order as the foreign key
	 * 							columns are specified in the referenceMap
	 * @param Array $options Various options, @see self::relate for more information.
	 * @return Mixed Either the result of the save() call, or, if $postponeSave is true, 
	 * 				 $this, for chaining purposes.
	 * @throws Garp_Db_Exception When trying to save an invalid relation.
	 */
	protected function _resolveRelationRuleForRelate($modelName, $primaryKey, Garp_Util_Configuration $options) {
		$thisModel		= $this->_getTable();
		$theOtherModel	= new $modelName();
		$primaryKey		= (array) $primaryKey;

		switch ($this->_getRelationType($modelName)) {
			case 'belongsTo':
				/**
				 * Make the parameters complete by adding the rule
				 * and calling relate() recursively
				 */
				$options['rule'] = $thisModel->findRuleForRelation($modelName);
				return $this->relate($modelName, $primaryKey, $options);
			break;
			case 'hasMany':
				/**
				 * If so, we know it's not a belongsTo relationship.
				 * To simplify things, a row can only save a belongsTo relationship.
				 * So we flip it here; find the other row and call relate() on it
				 * with reversed parameters.
				 */
				$theOtherRow = call_user_func_array(array($theOtherModel, 'find'), $primaryKey)->current();
				$options['rule'] = $theOtherModel->findRuleForRelation(get_class($thisModel));
				if (!$theOtherRow) {
					$primKeyStr = implode(',', $primaryKey);
					throw new Exception("Row from model \"$modelName\" with primary key ($primKeyStr) not found. Relation not possible.");
				}
				return $theOtherRow->relate(get_class($thisModel), $this->getPrimaryKey(), $options);
			break;
			case 'hasManyAndBelongsTo':
			default:
				return $this->_createBindingRow($thisModel, $theOtherModel, $primaryKey, $options);
			break;
		}
	}
	
	
	/**
	 * We need a rule for saving a relation. If none is passed to self::relate, 
	 * this method is called to handle the absence. This method then recursively 
	 * calls self::relate again when it has resolved what rule to use.
	 * @param String $modelName The other model
	 * @param Mixed $primaryKey Primary key(s) of the other record. If compound key,
	 * 							the keys must be in the same order as the foreign key
	 * 							columns are specified in the referenceMap
	 * @param String $rule The rule that defines this relation in the reference map
	 * @return Mixed Either the result of the save() call (or delete call in the case of HABTM).
	 * @throws Garp_Db_Exception When trying to save an invalid relation.
	 */
	protected function _resolveRelationRuleForUnrelate($modelName, $primaryKey) {
		$thisModel		= $this->_getTable();
		$theOtherModel	= new $modelName();
		$primaryKey = (array) $primaryKey;
		
		switch ($this->_getRelationType($modelName)) {
			case 'belongsTo':
				/**
				 * Make the parameters complete by adding the rule
				 * and calling unrelate() recursively
				 */
				$rule = $thisModel->findRuleForRelation($modelName);
				return $this->unrelate($modelName, $primaryKey, $rule);
			break;
			case 'hasMany':
				/**
				 * To simplify things, a row can only save a belongsTo relationship.
				 * So we flip it here; find the other row and call unrelate() on it
				 * with reversed parameters.
				 */
				$theOtherRow = call_user_func_array(array($theOtherModel, 'find'), $primaryKey)->current();
				if (!$theOtherRow) {
					$primKeyStr = implode(',', $primaryKey);
					throw new Exception("Row from model \"$modelName\" with primary key ($primKeyStr) not found. Relation not possible.");
				}
				$rule = $theOtherModel->findRuleForRelation(get_class($thisModel));
				return $theOtherRow->unrelate(get_class($thisModel), $this->getPrimaryKey(), $rule);
			break;
			case 'hasManyAndBelongsTo':
			default:
				return $this->_deleteBindingRow($thisModel, $theOtherModel, $primaryKey);
			break;
		}
	}
	

	/**
	 * Returns the type of relation between the model of the current row, and the given model.
	 * @param String $modelName The name of the other model
	 * @return String Relation type: either 'belongsTo', 'hasMany' or 'hasAndBelongsToMany'.
	 */
	protected function _getRelationType($modelName) {
		$thisModel		= $this->_getTable();
		$theOtherModel	= new $modelName();

		// Check if a rule exists in this model's reference map
		if ($thisModel->findRuleForRelation($modelName)) {
			return 'belongsTo';
		// Check if a rule exists in the other model's reference map
		} elseif ($theOtherModel->findRuleForRelation(get_class($thisModel))) {
			return 'hasMany';
		/**
		 * At this point the rule is not found in either model. This means either
		 * this is a many to many relation that needs a binding model, or the 
		 * relationship simply does not exist and is therefore invalid.
		 */
		} else {
			return 'hasAndBelongsToMany';
		}
	}
	
	
	/**
	 * Create a row in a binding table, which relates two rows.
	 * @param Garp_Model_Db $thisModel The parent table to this row
	 * @param Garp_Model_Db $theOtherModel The related table
	 * @param Array $primaryKey The primary key of the related table
	 * @param Garp_Util_Configuration $options Various options. @see self::relate For more information.
	 * @return Mixed The result of the save() call
	 */
	protected function _createBindingRow(Garp_Model_Db $thisModel, Garp_Model_Db $theOtherModel, array $primaryKey, Garp_Util_Configuration $options) {
		$bindingModel = $thisModel->getBindingModel($theOtherModel);
		$bindingRow = $bindingModel->createRow($options['extraFields']);

		$originalPostponeSave = $options['postponeSave'];
		$options['postponeSave'] = true;
		// Relate the first model...
		if ($rule1 = $bindingModel->findRuleForRelation(get_class($thisModel))) {
			// unrelate existing records
			if ($options['unrelateExisting']) {
				$this->unrelateAll(get_class($theOtherModel));
			}
			
			$options['rule'] = $rule1;
			$bindingRow->relate(get_class($thisModel), $this->getPrimaryKey(), $options);
			
			// ...and relate the second one
			if ($rule2 = $bindingModel->findRuleForRelation(get_class($theOtherModel), $rule1)) {
				$options['rule'] = $rule2;
				$bindingRow->relate(get_class($theOtherModel), $primaryKey, $options);

				/**
				 * Since save is postponed using the 4th parameter, call it now. 
				 * This is done because in most cases, the two foreign keys will together
				 * form the primary key and therefore the binding row cannot exist
				 * without both.
				 */
				return $originalPostponeSave ? $this : $bindingRow->save();
			} else {
				throw new Garp_Db_Exception('Model '.get_class($bindingModel).' has no relation rule for '.
											get_class($theOtherModel));
			}
		} else {
			throw new Garp_Db_Exception('Model '.get_class($bindingModel).' has no relation rule for '.
										get_class($thisModel));
		}
	}
	
	
	/**
	 * Delete a row from a binding table, effectively destroying
	 * the relationship.
	 * @param Garp_Model_Db $thisModel The parent table to this row
	 * @param Garp_Model_Db $theOtherModel The related table
	 * @param Array $primaryKey The primary key of the related table
	 * @return Int Number of deleted rows
	 */
	protected function _deleteBindingRow(Garp_Model_Db $thisModel, Garp_Model_Db $theOtherModel, array $primaryKey) {
		$bindingModel = $thisModel->getBindingModel($theOtherModel);
		$rule1 = $bindingModel->findRuleForRelation(get_class($thisModel));
		$rule2 = $bindingModel->findRuleForRelation(get_class($theOtherModel));
		if (!$rule1 || !$rule2) {
			throw new Garp_Db_Exception('Model '.get_class($bindingModel).' has no relation '.
										'rule specified for '.get_class($thisModel).' or '.get_class($theOtherModel));
		}
		$refMap = $bindingModel->info(Zend_Db_Table::REFERENCE_MAP);
		// create new select object to fetch the binding row
		$select = $bindingModel->select();
		// create WHERE clause for rules
		foreach (array($rule1, $rule2) as $theOtherRow => $rule) {
			$foreignKeyColumns = (array)$refMap[$rule]['columns'];
			foreach ($foreignKeyColumns as $i => $column) {
				if ($theOtherRow) {
					$key = $primaryKey[$i];
				} else {
					$key = (array)$this->getPrimaryKey();
					$key = $key[$i];
				}
				$select->where($column.' = ?', $key);
			}
		}
		// DELETE the binding row
		$where = implode(' ', $select->getPart(Zend_Db_Select::WHERE));
		return $bindingModel->delete($where);
	}
	
	
	/**
	 * Make sure default values are present in the $options array.
	 * @param Garp_Util_Configuration $options Various options
	 * @return Void
	 */
	protected function _normalizeOptionsForRelate(Garp_Util_Configuration $options) {
		$options->setDefault('rule', null)
				->setDefault('extraFields', array())
				->setDefault('postponeSave', false);
	}
	
	
    /**
     * Retrieve row field value
     * Modified to also return related rowsets.
     * @param  string $columnName The user-specified column name.
     * @return string             The corresponding column value.
     * @throws Zend_Db_Table_Row_Exception if the $columnName is not a column in the row.
     */
	public function __get($columnName) {
		try {
			$result = parent::__get($columnName);
		} catch (Zend_Db_Table_Row_Exception $e) {
			if (array_key_exists($columnName, $this->_related)) {
				$result = $this->_related[$columnName];
			} elseif (array_key_exists($columnName, $this->_virtual)) {
				$result = $this->_virtual[$columnName];
			} else {
				throw $e;
			}
		}
		return $result;
	}
	
	
	/**
     * Returns the column/value data as an array.
     * Modified to include related and virtual rowsets
     * @return array
     */
    public function toArray() {
		$data = parent::toArray();
		foreach ($this->_related as $key => $rowset) {
			if ($rowset) {
				$data[$key] = $rowset->toArray();
			} else {
				$data[$key] = $rowset;
			}
		}
		foreach ($this->_virtual as $key => $value) {
			$data[$key] = $value;
		}
		return $data;
	}
}
