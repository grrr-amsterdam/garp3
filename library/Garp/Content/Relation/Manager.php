<?php
/**
 * Garp_Content_Relation_Manager
 * Manages relationships between records.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Garp_Content_Relation_Manager {
	/**
 	 * Relate records.
 	 * @param Array|Garp_Util_Configuration $options Lots o' options:
 	 * 'modelA'    String|Garp_Model_Db    The first model or classname thereof
 	 * 'modelB'    String|Garp_Model_Db    The second model or classname thereof
 	 * 'keyA'      Mixed                   Primary key(s) of the first model
 	 * 'keyB'      Mixed                   Primary key(s) of the second model
 	 * 'rule'      String                  The rule that stores this relationship in one of the reference maps
 	 * 'extraFields' Array                 Extra fields that can be saved with a HABTM record
 	 * @return Boolean Success
 	 */
	public static function relate($options) {
		self::_normalizeOptionsForRelate($options);
		$modelA  = $options['modelA'];
		$modelB  = $options['modelB'];

		try {
			/**
 		 	 * If this succeeds, it's a regular relationship where the foreign key 
 		 	 * resides inside modelA. Continue as usual.
 		 	 */
			$reference = $modelA->getReference(get_class($modelB), $options['rule']);
		} catch (Exception $e) {
			if (!self::isInvalidReferenceException($e)) {
				throw $e;
			}
			try {
				/**
 		 	 	 * If this succeeds, the foreign key resides in the modelA.
 		 	 	 * Flip modelA and modelB and keyA and keyB in order to normalize the given configuration.
 		 	 	 * Call self::relate() recursively with these new options.
 		 	 	 */
				$reference = $modelB->getReference(get_class($modelA), $options['rule']);

				$keyA = $options['keyA'];
				$keyB = $options['keyB'];
				$options['modelA'] = $modelB;
				$options['modelB'] = $modelA;
				$options['keyA'] = $keyB;
				$options['keyB'] = $keyA;
				return Garp_Content_Relation_Manager::relate($options);
			} catch (Exception $e) {
				if (!self::isInvalidReferenceException($e)) {
					throw $e;
				}
				/**
 			 	 * Goody, we're dealing with a hasAndBelongsToMany relationship here. 
 			 	 * Try to construct the intersection model and save the relation
 			 	 * that way.
 			 	 */
				return self::_relateHasAndBelongsToMany($options);
			}
		}

		$rowA = call_user_func_array(array($options['modelA'], 'find'), (array)$options['keyA']);
		if (!count($rowA)) {
			$errorMsg = sprintf('Row of type %s with primary key (%s) not found.', $modelA->getName(), implode(',', (array)$options['keyA']));
			throw new Garp_Content_Relation_Exception($errorMsg);
		}
		$rowA = $rowA->current();
		self::_addForeignKeysToRow($rowA, $reference, $options['keyB']);
		return $rowA->save();
	}


	/**
 	 * Since it's such a different case than the other two types, hasAndBelongsToMany gets its own method.
 	 * @param Garp_Util_Configuration $options
 	 * @return Boolean Success
	 */
	protected static function _relateHasAndBelongsToMany($options) {
		$modelA = $options['modelA'];
		$modelB = $options['modelB'];
		$keyA   = $options['keyA'];
		$keyB   = $options['keyB'];
		$ruleA  = $options['ruleA'];
		$ruleB  = $options['ruleB'];
		$bindingModel = !$options['bindingModel'] ? $modelA->getBindingModel($modelB) : $options['bindingModel'];

		/**
 		 * Warning: assumptions are made!
 		 * - at this point, we assume the bindingModel has no relationships 
 		 *   to models other than modelA and modelB (so we don't need a rule or anything
 		 *   to find the right reference in the referenceMap)
 		 * - also, we assume the references can be found from the bindingModel. There will be
 		 *   no trying nor catching, if the reference is not here, we just crash the heck out of it.
 		 */ 
		$referenceA = $bindingModel->getReference(get_class($modelA), $ruleA);
		$referenceB = $bindingModel->getReference(get_class($modelB), $ruleB);

		// The only place where extraFields is used: to fill fields other than the primary key references in the binding row
		$bindingRow = $bindingModel->createRow($options['extraFields']);
		self::_addForeignKeysToRow($bindingRow, $referenceA, $keyA);
		self::_addForeignKeysToRow($bindingRow, $referenceB, $keyB);
		$success = $bindingRow->save();

		// Homophyllic relations must be saved bidirectionally
		if ($modelA->getName() == $modelB->getName()) {
			$bidirectionalRow = $bindingModel->createRow($options['extraFields']);
			self::_addForeignKeysToRow($bidirectionalRow, $referenceA, $keyB);
			self::_addForeignKeysToRow($bidirectionalRow, $referenceB, $keyA);
			$success = $success && $bidirectionalRow->save();
		}
		
		return $success;
	}


	/**
 	 * Provide a set of options with keys you can rely on.
 	 * @param Array|Garp_Util_Configuration $options
 	 * @return Void
 	 */
	protected static function _normalizeOptionsForRelate(&$options) {
		$options = ($options instanceof Garp_Util_Configuration) ? $options : new Garp_Util_Configuration($options);
		$options->obligate('modelA')
			->obligate('modelB')
			->obligate('keyA')
			->obligate('keyB')
			->setDefault('rule', null)
			->setDefault('ruleA', null)
			->setDefault('ruleB', null)
			->setDefault('extraFields', array())
			->setDefault('bindingModel', null)
			;
		// use models, not class names
		if (is_string($options['modelA'])) {
			$options['modelA'] = new $options['modelA']();
		}
		if (is_string($options['modelB'])) {
			$options['modelB'] = new $options['modelB']();
		}
		if (is_string($options['bindingModel'])) {
			$options['bindingModel'] = new $options['bindingModel']();
		}
		// Important to collect fresh data
		$options['modelA']->setCacheQueries(false);
		$options['modelB']->setCacheQueries(false);
		
		$options['keyA'] = (array)$options['keyA'];
		$options['keyB'] = (array)$options['keyB'];

		// allow 'rule' key to be set when 'ruleA' is meant
		if ($options['rule'] && !$options['ruleA']) {
			$options['ruleA'] = $options['rule'];
		// also allow it the other way around
		} else if ($options['ruleA'] && !$options['rule']) {
			$options['rule'] = $options['ruleA'];
		}
	}


	/**
 	 * Unrelate records.
 	 * @param Array|Garp_Util_Configuration $options Lots o' options:
 	 * 'modelA'    String|Garp_Model_Db    The first model or classname thereof
 	 * 'modelB'    String|Garp_Model_Db    The second model or classname thereof
 	 * 'keyA'      Mixed                   Primary key(s) of the first model
 	 * 'keyB'      Mixed                   Primary key(s) of the second model
 	 * 'rule'      String                  The rule that stores this relationship in one of the reference maps
 	 * @return Boolean Success
 	 */
	public static function unrelate($options) {
		self::_normalizeOptionsForUnrelate($options);
		$modelA  = $options['modelA'];
		$modelB  = $options['modelB'];

		try {
			/**
 		 	 * If this succeeds, it's a regular relationship where the foreign key 
 		 	 * resides inside modelA. Continue as usual.
 		 	 */
			$reference = $modelA->getReference(get_class($modelB), $options['rule']);
		} catch (Exception $e) {
			if (!self::isInvalidReferenceException($e)) {
				throw $e;
			}
			try {
				/**
 		 	 	 * If this succeeds, the foreign key resides in the modelA.
 		 	 	 * Flip modelA and modelB and keyA and keyB in order to normalize the given configuration.
 		 	 	 * Call self::relate() recursively with these new options.
 		 	 	 */
				$reference = $modelB->getReference(get_class($modelA), $options['rule']);

				$keyA = $options['keyA'];
				$keyB = $options['keyB'];
				$options['modelA'] = $modelB;
				$options['modelB'] = $modelA;
				$options['keyA'] = $keyB;
				$options['keyB'] = $keyA;
				return Garp_Content_Relation_Manager::unrelate($options);
			} catch (Exception $e) {
				if (!self::isInvalidReferenceException($e)) {
					throw $e;
				}
				/**
 			 	 * Goody, we're dealing with a hasAndBelongsToMany relationship here. 
 			 	 * Try to construct the intersection model and save the relation
 			 	 * that way.
 			 	 */
				return self::_unrelateHasAndBelongsToMany($options);
			}
		}

		/**
 		 * Figure out which model is leading. This depends on which of the two keys is provided.
 		 * This kind of flips the query around. For instance, when keyA is given, the query is 
 		 * something like this:
 		 * UPDATE modelA SET foreignkey = NULL WHERE primarykey = keyA
 		 * When keyB is given however, the query goes something like this:
 		 * UPDATE modelA SET foreignkey = NULL WHERE foreignkey = keyB
 		 */
		$query = 'UPDATE `'.$modelA->getName().'` SET ';
		$columnsToValues = array();
		foreach ($reference['columns'] as $column) {
			$columnsToValues[] = '`'.$column.'` = NULL';
		}
		$columnsToValues = implode(' AND ', $columnsToValues);
		$query .= $columnsToValues;
		$whereColumnsToValues = array();
		if ($options['keyA']) {
			$useColumns = 'refColumns';
			$useKeys = 'keyA';
		} else {
			$useColumns = 'columns';
			$useKeys = 'keyB';
		}
		foreach ($reference[$useColumns] as $i => $column) {
			$whereColumnsToValues[] = '`'.$column.'` = '.$options[$useKeys][$i];
		}
		$whereColumnsToValues = implode(' AND ', $whereColumnsToValues);
		$query .= ' WHERE ';
		$query .= $whereColumnsToValues;

		return $modelA->getAdapter()->query($query);
	}


	/**
 	 * Since it's such a different case than the other two types, hasAndBelongsToMany gets its own method.
 	 * @param Garp_Util_Configuration $options
 	 * @return Boolean Success
	 */
	protected static function _unrelateHasAndBelongsToMany($options) {
		$modelA = $options['modelA'];
		$modelB = $options['modelB'];
		$keyA   = $options['keyA'];
		$keyB   = $options['keyB'];
		$ruleA  = $options['ruleA'];
		$ruleB  = $options['ruleB'];
		$bindingModel = !$options['bindingModel'] ? $modelA->getBindingModel($modelB) : $options['bindingModel'];

		/**
 		 * Warning: assumptions are made!
 		 * - at this point, we assume the bindingModel has no relationships 
 		 *   to models other than modelA and modelB (so we don't need a rule or anything
 		 *   to find the right reference in the referenceMap)
 		 * - also, we assume the references can be found from the bindingModel. There will be
 		 *   no trying nor catching, if the reference is not here, we just crash the heck out of it.
 		 */ 
		$referenceA = $bindingModel->getReference(get_class($modelA), $ruleA);
		$referenceB = $bindingModel->getReference(get_class($modelB), $ruleB);

		// Construct WHERE clause
		$where = array();
		$createWhereBit = function($reference, $values) {
			$w = array();
			foreach ($reference['columns'] as $i => $column) {
				$w[] = '`'.$column.'` = '.$values[$i];
			}
			$w = implode(' AND ', $w);
			return '('.$w.')';
		};

		if ($keyA) {
			$where[] = $createWhereBit($referenceA, $keyA);
		}
		if ($keyB) {
			$where[] = $createWhereBit($referenceB, $keyB);
		}

		$where = '('.implode(' AND ', $where).')';

		// Homophyllic relations must be deleted bidirectionally
		if ($modelA->getName() == $modelB->getName()) {
			$homoWhere = array();
			if ($keyA) {
				$homoWhere[] = $createWhereBit($referenceB, $keyA);
			}
			if ($keyB) {
				$homoWhere[] = $createWhereBit($referenceA, $keyB);
			}

			$where .= ' OR ('.implode(' AND ', $homoWhere).')';
		}
		
		return $bindingModel->delete($where);
	}


	/**
 	 * Provide a set of options with keys you can rely on.
 	 * @param Array|Garp_Util_Configuration $options
 	 * @return Void
 	 */
	public static function _normalizeOptionsForUnrelate(&$options) {
		$options = ($options instanceof Garp_Util_Configuration) ? $options : new Garp_Util_Configuration($options);
		$options->obligate('modelA')
			->obligate('modelB')
			->setDefault('keyA', null)
			->setDefault('keyB', null)
			->setDefault('rule', null)
			->setDefault('ruleA', null)
			->setDefault('ruleB', null)
			->setDefault('bindingModel', null)
			;
		if (!$options['keyA'] && !$options['keyB']) {
			throw new Garp_Content_Relation_Exception('Either keyA or keyB must be provided when unrelating.');
		}

		// use models, not class names
		if (is_string($options['modelA'])) {
			$options['modelA'] = new $options['modelA']();
		}
		if (is_string($options['modelB'])) {
			$options['modelB'] = new $options['modelB']();
		}
		if (is_string($options['bindingModel'])) {
			$options['bindingModel'] = new $options['bindingModel']();
		}
		// Important to collect fresh data
		$options['modelA']->setCacheQueries(false);
		$options['modelB']->setCacheQueries(false);

		$options['keyA'] = (array)$options['keyA'];
		$options['keyB'] = (array)$options['keyB'];

		// allow 'rule' key to be set when 'ruleA' is meant
		if ($options['rule'] && !$options['ruleA']) {
			$options['ruleA'] = $options['rule'];
		// also allow it the other way around
		} else if ($options['ruleA'] && !$options['rule']) {
			$options['rule'] = $options['ruleA'];
		}
	}


	/**
 	 * Fill foreign key columns in a row.
 	 * @param Zend_Db_Table_Row_Abstract $row The row object
 	 * @param Array $reference The reference from the referencemap
 	 * @param Array $values The foreign key values
 	 * @return Void Edits the row by reference
 	 */
	protected static function _addForeignKeysToRow(Zend_Db_Table_Row_Abstract &$row, array $reference, array $values) {
		foreach ($reference['columns'] as $i => $column) {
			$row->{$column} = $values[$i];
		}
	}


	/**
 	 * Unfortunately, almost all the Zend exceptions coming from Zend_Db_Table_Abstract are of type 
 	 * Zend_Db_Table_Exception, so we cannot check wether a query fails or wether there is no binding possible.
 	 * This method checks wether the exception describes an invalid reference.
 	 * @param Exception $e
 	 * @return Boolean
 	 */
	static public function isInvalidReferenceException(Exception $e) {
		return stripos($e->getMessage(), 'No reference') !== false;
	}
}
