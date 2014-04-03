<?php
/**
 * Garp_Model_Db
 * Model implementation for database tables.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Model
 * @lastmodified $Date: $
 */
abstract class Garp_Model_Db extends Zend_Db_Table_Abstract implements Garp_Model, Garp_Util_Observer, Garp_Util_Observable {
	/**
	 * The table name.
	 * @var String
	 */
	protected $_name;
	

	/**
	 * Default sorting of queries
	 * @var Mixed Any type that's accepted by Zend_Db_Select::order()
	 */
	protected $_defaultOrder = null;

	
	/**
	 * Collection of model names that might be bound to this model in the future.
	 * This is used by the core Cachable behavior. Models that are in this array
	 * also get their cache cleared when a record of this model is updated.
	 * @var Array
	 */
	protected $_bindable = array();
	
	
	/**
	 * Custom rowset class
	 * @var String
	 */
	protected $_rowsetClass = 'Garp_Db_Table_Rowset';
	
	
	/**
	 * Custom row class
	 * @var String
	 */
	protected $_rowClass = 'Garp_Db_Table_Row';
	
	
	/**
	 * Collection of observers
	 * @var Array
	 */
	protected $_observers = array();
	
	
	/**
	 * Wether to cache queries
	 * @var Boolean
	 */
	public $cacheQueries = true;
	
	
    /**
     * Initialize object
     * Called from {@link __construct()} as final step of object instantiation.
     * @return Void
     */
	public function init() {
		/**
		 * Register core behaviors and also this model itself, 
		 * to allow for callback methods in the models themselves.
		 */
		$this->registerObserver($this)
			 ->registerObserver(new Garp_Model_Behavior_Bindable())
			 ->registerObserver(new Garp_Model_Behavior_DefaultSortable())
			 ->registerObserver(new Garp_Model_Behavior_Cachable())
		;
	}
	
	
	/**
	 * Get name without namespace
	 * @return String 
	 */
	public function getNameWithoutNamespace() {
		$name = get_class($this);
		$name = explode('_', $name);
		return array_pop($name);
	}
	
	
	/**
	 * Convenience method for creating SELECT objects
     * @param string|array|Zend_Db_Table_Select $where  OPTIONAL An SQL WHERE clause or Zend_Db_Table_Select object.
     * @param string|array                      $order  OPTIONAL An SQL ORDER clause.
     * @param int                               $count  OPTIONAL An SQL LIMIT count.
     * @param int                               $offset OPTIONAL An SQL LIMIT offset.
     * @return Zend_Db_Table_Select
	 */
	public function createSelect($where = null, $order = null, $count = null, $offset = null) {
		$select = $this->select();

		if ($where !== null) {
			$this->_where($select, $where);
		}

		if ($order !== null) {
			$this->_order($select, $order);
		}

		if ($count !== null || $offset !== null) {
			$select->limit($count, $offset);
		}
		return $select;
	}
	
	
	/**
	 * Retrieve many-to-many binding model. (e.g; "Model_User" + "Model_Tag" becomes "Model_TagUser")
	 * @param Garp_Model_Db $theOtherModel The other model
	 * @return Garp_Model_Db The binding model.
	 */
	public function getBindingModel(Garp_Model_Db $theOtherModel) {
		$modelNames = array($this->getNameWithoutNamespace(), $theOtherModel->getNameWithoutNamespace());
		sort($modelNames);
		$bindingModel = 'Model_'.implode('', $modelNames);
		
		/**
		 * Here we load the bindingModel class. There is no check to see if 
		 * the file can be loaded without throwing a fatal error that doesn't
		 * require disc access, which is a waste of performance.
		 * Therefore, just trigger the error: it's the developer's responsibility
		 * to make sure live code never asks for an invalid relationship.
		 */
		return new $bindingModel();
	}

	
	
	/**
	 * Check to see if this model has a reference to a
	 * relation with another model.
	 * @param String $modelName The name of the other model
	 * @param String $nameException Optional rule name that should not be returned, for homophile relations.
	 * @return Mixed String on success, FALSE on failure
	 */
	public function findRuleForRelation($modelName, $nameException = null) {
		if (!is_array($nameException) && $nameException) {
			$nameException = array($nameException);
		} elseif (!is_array($nameException)) {
			$nameException = array();
		}

	/**
 __   _      ___      _____     _  __  
| || | ||   / _ \\   / ____||  | |/ // 
| '--' ||  / //\ \\ / //---`'  | ' //  
| .--. || |  ___  ||\ \\___    | . \\  
|_|| |_|| |_||  |_|| \_____||  |_|\_\\ 
`-`  `-`  `-`   `-`   `----`   `-` --` 
                                       
   ___      __       _____     ____      ______  
  / _ \\   | ||     |  ___||  |  _ \\   /_   _// 
 / //\ \\  | ||     | ||__    | |_| ||  `-| |,-  
|  ___  || | ||__   | ||__    | .  //     | ||   
|_||  |_|| |____//  |_____||  |_|\_\\     |_||   
`-`   `-`  `----`   `-----`   `-` --`     `-`'
 	 */
		$nameException[] = 'Author';
		$nameException[] = 'Modifier';
		
		foreach ($this->_referenceMap as $refKey => $refOptions) {
			if (
				//$refKey !== $nameException &&
				!in_array($refKey, $nameException) &&
				$refOptions['refTableClass'] === $modelName
			) {
				return $refKey;
			}
		}
		return false;
	}
		
	
	/**
	 * Return default order for this model
	 * @return String|Array
	 */
	public function getDefaultOrder() {
		return $this->_defaultOrder;
	}
	
	
	/**
	 * Get bindable models
	 * @return Array
	 */
	public function getBindableModels() {
		return $this->_bindable;
	}
	
	
	/**
	 * Bind model. This activates a relation between models. With the next 
	 * fetch operation related records from these models will be fetched 
	 * alongside the originally requested records.
	 * 
	 * @param String|Garp_Model_Db $alias	 		 An alias for the relationship. This name is used in fetched
	 * 											 		 rows to store the related records. If $options['modelClass']
	 * 											 		 is not set, the alias is also assumed to be the classname.
	 * @param Garp_Util_Configuration|Array $options	 Various relation options. Note; for many-to-many relations
	 * 						 					 		 the name of the binding model must be given in
	 * 						 					 		 $options['bindingModel'].
	 * @return Garp_Model $this
	 */
	public function bindModel($alias, $options = null) {
		if ($alias instanceof Garp_Model_Db) {
			$alias = get_class($alias);
		}
		if (!is_null($options) && !$options instanceof Garp_Util_Configuration) {
			$options = new Garp_Util_Configuration($options);
		}
		$this->notifyObservers('beforeBindModel', array($this, $alias, &$options));
		Garp_Model_Db_BindingManager::storeBinding(get_class($this), $alias, $options);
		return $this;
	}
	
	
	/**
	 * Unbind model. Deactivate a relationship between models.
	 * @param String $modelName The name of the model
	 * @return Garp_Model $this
	 */
	public function unbindModel($alias) {
		$this->notifyObservers('beforeUnbindModel', array($this, &$alias));
		Garp_Model_Db_BindingManager::removeBinding(get_class($this), $alias);
		return $this;
	}
	
	
	/**
	 * Unbind all models.
	 * @return Garp_Model $this
	 */
	public function unbindAllModels() {
		foreach ($this->getBindings() as $alias => $binding) {
			$this->unbindModel($alias);
		}
		return $this;
	}
	
	
	/**
	 * Return all bound models
	 * @return Array
	 */
	public function getBindings() {
		return Garp_Model_Db_BindingManager::getBindings(get_class($this));
	}
	
	
	/**
	 * Modified Zend_Db_Table CRUD methods. 
	 * (overwritten to support observers)
	 * ----------------------------------------------------------------------
	 */

		
    /**
     * Fetches all rows.
     * Honors the Zend_Db_Adapter fetch mode.
     * @param string|array|Zend_Db_Table_Select $where  OPTIONAL An SQL WHERE clause or Zend_Db_Table_Select object.
     * @param string|array                      $order  OPTIONAL An SQL ORDER clause.
     * @param int                               $count  OPTIONAL An SQL LIMIT count.
     * @param int                               $offset OPTIONAL An SQL LIMIT offset.
     * @return Zend_Db_Table_Rowset_Abstract The row results per the Zend_Db_Adapter fetch mode.
     */
    public function fetchAll($where = null, $order = null, $count = null, $offset = null) {
		if (!($where instanceof Zend_Db_Table_Select)) {
			$select = $this->createSelect($where, $order, $count, $offset);
		} else {
			$select = $where;
		}
		return $this->_improvedFetch($select, 'fetchAll');
    }


    /**
     * Fetches one row in an object of type Zend_Db_Table_Row_Abstract,
     * or returns null if no row matches the specified criteria.
     *
     * @param string|array|Zend_Db_Table_Select $where  OPTIONAL An SQL WHERE clause or Zend_Db_Table_Select object.
     * @param string|array                      $order  OPTIONAL An SQL ORDER clause.
     * @param int                               $offset OPTIONAL An SQL OFFSET value.
     * @return Zend_Db_Table_Row_Abstract|null The row results per the
     *     Zend_Db_Adapter fetch mode, or null if no row found.
     */
    public function fetchRow($where = null, $order = null, $offset = null) {
		if (!($where instanceof Zend_Db_Table_Select)) {
			$select = $this->createSelect($where, $order, 1);
		} else {
			$select = $where->limit(1);
		}
		return $this->_improvedFetch($select, 'fetchRow');
    }


	/**
	 * A utility method that extends both fetchRow and fetchAll.
	 * @param Zend_Db_Select $select Select object passed to either parent::fetchRow or parent::fetchAll
	 * @param String $method The real method, 'fetchRow' or 'fetchAll'
	 * @return Mixed
	 */
	protected function _improvedFetch(Zend_Db_Select $select, $method) {
		if ($method != 'fetchRow' && $method != 'fetchAll') {
			throw new Garp_Model_Exception('\'method\' must be "fetchRow" or "fetchAll".');
		}
		
		/**
		 * Observers are allowed to set $results. This way, behaviors can swoop in 
		 * and use a different source when fetching records based on certain parameters.
		 * For instance, the Cachable behavior might fetch data from the cache
		 * instead of the database.
		 */		
		$results = null;
		$this->notifyObservers('beforeFetch', array($this, $select, &$results));
		if (is_null($results)) {
			$results = parent::$method($select);
			$this->notifyObservers('afterFetch', array($this, &$results, $select));
		}
		return $results;
	}


	/**
     * Inserts a new row.
     * @param  array  $data  Column-value pairs.
     * @return mixed         The primary key of the row inserted.
     */
	public function insert(array $data) {
		$this->notifyObservers('beforeInsert', array($this, &$data));
		$pkData = parent::insert($data);
		$this->notifyObservers('afterInsert', array($this, $data, $pkData));		
		return $pkData;
	}
	
	
	/**
     * Updates existing rows.
     * @param  array        $data  Column-value pairs.
     * @param  array|string $where An SQL WHERE clause, or an array of SQL WHERE clauses.
     * @return int          The number of rows updated.
     */
	public function update(array $data, $where) {
		$this->notifyObservers('beforeUpdate', array($this, &$data, &$where));
		$result = parent::update($data, $where);
		$this->notifyObservers('afterUpdate', array($this, $result, $data, $where));
		return $result;
	}
	
	
	/**
     * Deletes existing rows.
     * @param  array|string $where SQL WHERE clause(s).
     * @return int          The number of rows deleted.
     */
    public function delete($where) {
		$this->notifyObservers('beforeDelete', array($this, &$where));
		$result = parent::delete($where);
		$this->notifyObservers('afterDelete', array($this, $result, $where));
		return $result;
    }
    
	
	/**
	 * Observable methods
	 * ----------------------------------------------------------------------
	 */
	
	/**
	 * Register observer. The observer will then listen to events broadcasted
	 * from this class.
	 * @param Garp_Util_Observer $observer The observer
	 * @param String $name Optional custom name
	 * @return Garp_Util_Observable $this
	 */
	public function registerObserver(Garp_Util_Observer $observer, $name = false) {
		$name = $name ?: $observer->getName();
		$this->_observers[$name] = $observer;
		return $this;
	}
	
	
	/**
	 * Unregister observer. The observer will no longer listen to 
	 * events broadcasted from this class.
	 * @param Garp_Util_Observer|String $observer The observer or its name
	 * @return Garp_Util_Observable $this
	 */
	public function unregisterObserver($observer) {
		if (!is_string($observer)) {
			$observer = $observer->getName();
		}
		unset($this->_observers[$observer]);
		return $this;
	}
	
	
	/**
	 * Broadcast an event. Observers may implement their reaction however
	 * they please. The Observable does not expect a return action.
	 * If Observers are allowed to modify variables passed, make sure
	 * $args contains references instead of values.
	 * @param String $event The event name
	 * @param Array $args The arguments you wish to pass to the observers
	 * @return Garp_Util_Observable $this
	 */
	public function notifyObservers($event, array $args = array()) {
		$first = $middle = $last = array();
		
		// Distribute observers to the different arrays
		foreach ($this->_observers as $observer) {
			// Core helpers may define when they are executed; first or last.
			if ($observer instanceof Garp_Model_Helper_Core) {
				if (Garp_Model_Helper_Core::EXECUTE_FIRST === $observer->getExecutionPosition()) {
					$first[] = $observer;
				} elseif (Garp_Model_Helper_Core::EXECUTE_LAST === $observer->getExecutionPosition()) {
					$last[] = $observer;
				}
			} else {
				// Regular observers are always executed in the middle
				$middle[] = $observer;
			}
		}
		
		// Do the actual execution
		foreach (array($first, $middle, $last) as $observerCollection) {
			foreach ($observerCollection as $observer) {
				$observer->receiveNotification($event, $args);
			}
		}
		return $this;
	}
	

	/**
	 * @author David Spreekmeester | grrr.nl
	 */
	public function getObserver($name) {
		print '<pre>';
		print_r($this->_observers);
		print '</pre>';
		exit;
		
	}
	
	
	/**
	 * Observer methods
	 * ----------------------------------------------------------------------
	 */
	
	/**
	 * Receive events. This method looks for a method named after 
	 * the event (e.g. when the event is "beforeFetch", the method 
	 * executed will be "beforeFetch"). Subclasses may implement
	 * this to act upon the event however they wish.
	 * @param String $event The name of the event
	 * @param Array $params Collection of parameters (contextual to the event)
	 * @return Void
	 */
	public function receiveNotification($event, array $params = array()) {
		if (method_exists($this, $event)) {
			$this->{$event}($params);
		}
	}
	
	
	/**
	 * Return table name
	 * @return String
	 */
	public function getName() {
		return $this->_name;
	}
}
