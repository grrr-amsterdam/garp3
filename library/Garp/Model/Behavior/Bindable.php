<?php
/**
 * Garp_Model_Behavior_Bindable
 * Binds fetched results with related data.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class Garp_Model_Behavior_Bindable extends Garp_Model_Behavior_Core {
	/**
	 * Wether to execute before or after regular observers.
	 * @var String
	 */
	protected $_executionPosition = self::EXECUTE_FIRST;
		
	
	/**
	 * Configuration.
	 * @return Void
	 */
	protected function _setup($config) {}
	
	
	/**
	 * AfterFetch callback, combines results with related records.
	 * @param Array $args
	 * @return Void
	 */
	public function afterFetch(&$args) {
		$model = $args[0];
		$data  = $args[1];
		$this->_combineResultsWithBindings($model, $data);
	}
	
	
	/**
	 * Check if there are bindings to fetch related records and
	 * start the related rowset fetching.
	 * @param Garp_Model $model The model that spawned this data
	 * @param Garp_Db_Table_Row|Garp_Db_Table_Rowset $data The fetched root data
	 * @return Void
	 */
	protected function _combineResultsWithBindings(Garp_Model $model, $data) {
		$tableName = $model->getName();

		if ($data) {
			$bindings = $model->getBindings();
			if (!empty($bindings)) {
				// check if it's a rowset or a single row
				if (!$data instanceof Garp_Db_Table_Rowset) {
					$data = array($data);
				}
				foreach ($bindings as $binding => $bindOptions) {
					/**
					 * We keep tabs on the outer call to fetch() by checking getRecursion. 
					 * If it is 0, this is the first call in the chain. That way we can 
					 * clean up the recursion when we're done, since all subsequent fetch() calls
					 * will happen within this one and the if ($cleanup) line ahead will only 
					 * fire on the first fetch(). Look at it like this:
					 * 
					 * fetch()
					 *   cleanup = 1
					 * 	   fetch()
					 *       fetch()
					 *     fetch()
					 *       fetch()
					 *     fetch()
					 *       fetch() 
					 *   if (cleanup) resetRecursion()
					 * 
					 */
					$cleanup = false;
					if (Garp_Model_Db_BindingManager::getRecursion(get_class($model), $binding) == 0) {
						$cleanup = true;
					}
					
					if (Garp_Model_Db_BindingManager::isAllowedFetch(get_class($model), $binding)) {
						Garp_Model_Db_BindingManager::registerFetch(get_class($model), $binding);
						
						foreach ($data as $datum) {
							// there's no relation possible if the primary key is not among the fetched columns
							$prim = (array)$model->info(Zend_Db_Table::PRIMARY);
							foreach ($prim as $key) {
								try {
									$datum->$key;
								} catch (Exception $e) {
									break 2;
								}
							}
							$relatedRowset = $this->_getRelatedRowset($model, $datum, $bindOptions);
							$datum->setRelated($binding, $relatedRowset);
						}
					}
					
					if ($cleanup) {
						Garp_Model_Db_BindingManager::resetRecursion(get_class($model), $binding);
					}
				}
				
				// return the pointer to 0
				if ($data instanceof Garp_Db_Table_Rowset) {
					$data->rewind();
				}
			}
		}
	}

	
	/**
	 * Find a related recordset.
	 * @param Garp_Model $model The model that spawned this data
	 * @param Garp_Db_Row $row The row object
	 * @param Garp_Util_Configuration $options Various relation options
	 * @return String The name of the method.
	 */
	protected function _getRelatedRowset(Garp_Model $model, Garp_Db_Table_Row $row, Garp_Util_Configuration $options) {
		/**
		 * An optional passed SELECT object will be passed by reference after every query.
		 * This results in an error when 'clone' is not used, because correlation names will be 
		 * used twice (since they were set during the first iteration). Using 'clone' makes sure
		 * a brand new SELECT object is used every time that hasn't been soiled by a possible 
		 * previous query.
		 */
		$conditions = is_null($options['conditions']) ? null : clone $options['conditions'];
		$modelClass = $options['modelClass'];
		if (!$modelClass instanceof Zend_Db_Table_Abstract) {
			$modelClass = new $modelClass();
		}
		/**
 		 * Do not cache related queries. The "outside" query should be the only 
 		 * query that's cached.
 		 */
		$originalCacheQueriesFlag = $modelClass->getCacheQueries();
		$modelClass->setCacheQueries(false);
		$modelName = get_class($modelClass);
		$relatedRowset = null;
		
		// many to many
		if (!empty($options['bindingModel'])) {
			$relatedRowset = $row->findManyToManyRowset($modelClass, $options['bindingModel'], $options['rule2'], $options['rule'], $conditions);
		} else {
			/**
		 	 * 'mode' is used to clear ambiguity with homophilic relationships. For example,
		 	 * a Model_Doc can have have child Docs and one parent Doc. The conditionals below can never tell 
		 	 * which method to call (findParentRow or findDependentRowset) from the referenceMap.
		 	 * Therefore, we can help the decision-making by passing "mode". This can either be 
		 	 * "parent" or "dependent", which will then force a call to findParentRow and findDependentRowset, 
		 	 * respectively.
		 	 */
			if (is_null($options['mode'])) {
				// belongs to
				try {
					$model->getReference($modelName, $options['rule']);
					$relatedRowset = $row->findParentRow($modelClass, $options['rule'], $conditions);
				} catch(Exception $e) {
					if (!Garp_Content_Relation_Manager::isInvalidReferenceException($e)) {
						throw $e;
					}
					try {
						// one to many - one to one	
						$otherModel = new $modelName();
						// The following line triggers an exception if no reference is available
						$otherModel->getReference(get_class($model), $options['rule']);
						$relatedRowset = $row->findDependentRowset($modelClass, $options['rule'], $conditions);
					} catch (Exception $e) {
						if (!Garp_Content_Relation_Manager::isInvalidReferenceException($e)) {
							throw $e;
						}
						$bindingModel = $model->getBindingModel($modelName);
						$relatedRowset = $row->findManyToManyRowset($modelClass, $bindingModel, $options['rule2'], $options['rule'], $conditions);
					}
				}
			} else {
				switch ($options['mode']) {
					case 'parent':
						$relatedRowset = $row->findParentRow($modelClass, $options['rule'], $conditions);
					break;
					case 'dependent':
						$relatedRowset = $row->findDependentRowset($modelClass, $options['rule'], $conditions);
					break;
					default:
						throw new Garp_Model_Exception('Invalid value for "mode" given. Must be either "parent" or '.
														'"dependent", but "'.$options['mode'].'" was given.');
					break;
				}
			}
		}
		// Reset the cacheQueries value. It's a static property,
		// so leaving it FALSE will affect all future fetch() calls to this 
		// model. Not good.
		$modelClass->setCacheQueries($originalCacheQueriesFlag);
		return $relatedRowset;
	}
}
