<?php
/**
 * Garp_Model_Db
 * Model implementation for database tables.
 * @author $Author: $
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
 	 * The join table name. This is the name of an SQL view containing the 
 	 * original table along with display fields for all possible belongsTo 
 	 * related records.
 	 * @var String
 	 */
	protected $_jointView;

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
	protected static $_cacheQueries = true; 

	/**
 	 * Configuration.
 	 * @var Array
 	 */
	protected $_configuration = array();

	/**
 	 * List fields
 	 * @var Array
 	 */
	protected $_listFields = array();

	/**
 	 * Parent model containing only the unilingual columns
 	 * @var Garp_Model_Db
 	 */
	protected $_unilingualModel;

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
 	 * Get the unilingual parent of a model.
 	 * Used for multilingual models (@see self::isMultilingual)
 	 * @return Garp_Model_Db
 	 */
	public function getUnilingualModel() {
		if ($this->_unilingualModel) {
			return new $this->_unilingualModel;
		}
		return $this;
	}

	/**
 	 * Check if this is an i18n model.
 	 * @return Boolean
 	 */
	public function isMultilingual() {
		$field_config = $this->getFieldConfiguration();
		foreach ($field_config as $config) {
			if ($config['multilingual']) {
				return true;
			}
		}
		return false;
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
 	 * Get namespace
 	 * @return String
 	 */
	public function getNamespace() {
		$name = get_class($this);
		$name = explode('_', $name);
		return array_shift($name);
	}

	/**
 	 * Get list fields
 	 * @return Array
 	 */
	public function getListFields() {
		return $this->_listFields;
	}

	/**
 	 * Get configuration
 	 * @param String $key Pick one specific configuration item.
 	 * @return Array
 	 */
	public function getConfiguration($key = null) {
		if (!$key) {
			return $this->_configuration;
		}
		if (!array_key_exists($key, $this->_configuration)) {
			throw new Exception("'$key' is not a valid configuration key.");
		}
		return $this->_configuration[$key];
	}

	/**
 	 * Get field configuration
 	 * @param String $column
 	 * @return Array
 	 */
	public function getFieldConfiguration($column = null) {
		$fields = $this->getConfiguration('fields');
		if (!$column) {
			return $fields;
		}
		foreach ($fields as $key => $value) {
			if ($value['name'] == $column) {
				return $value;
			}
		}
		return null;
	}

	/**
 	 * Convert array to WHERE clause
 	 * @param Array $data
 	 * @param Boolean $and Wether to use AND or OR
 	 * @return String
 	 */
	public function arrayToWhereClause(array $data, $and = true) {
		$out = array();
		$adapter = $this->getAdapter();
		foreach ($data as $key => $value) {
			$quotedKey = $adapter->quoteIdentifier($key);
			$quotedValue = $adapter->quote($value);
			$out[] = "$quotedKey = $quotedValue";
		}
		$glue = $and ? 'AND' : 'OR';
		$out = implode(" $glue ", $out);
		return $out;
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
	 * @param Garp_Model_Db|String $theOtherModel The other model or its classname
	 * @return Garp_Model_Db The binding model.
	 */
	public function getBindingModel($theOtherModel) {
		$bindingModelName = $this->getBindingModelName($theOtherModel);

		/**
		 * Here we load the bindingModel class. There is no check to see if 
		 * the file can be loaded without throwing a fatal error that doesn't
		 * require disc access, which is a waste of performance.
		 * Therefore, just trigger the error: it's the developer's responsibility
		 * to make sure live code never asks for an invalid relationship.
		 */
		return new $bindingModelName();
	}

	/**
 	 * Get bindingModel name
 	 */
	public function getBindingModelName($theOtherModel) {
		if (!$theOtherModel instanceof Garp_Model_Db) {
			$theOtherModel = new $theOtherModel();
		}
		$modelNames = array($this->getNameWithoutNamespace(), $theOtherModel->getNameWithoutNamespace());
		sort($modelNames);
		$namespace = 'Model_';

		// The following makes sure the namespace used is the same as that of 
		// the given models, but only if they both use the same namespace.
		$thisNamespace = $this->getNamespace();
		$theOtherNamespace = $theOtherModel->getNamespace();
		if ($thisNamespace === $theOtherNamespace && $thisNamespace !== 'Model') {
			$namespace = $thisNamespace.'_Model_';
		}
		$bindingModelName = $namespace.implode('', $modelNames);
		return $bindingModelName;
	}

	/**
	 * Return default order for this model
	 * @return String|Array
	 */
	public function getDefaultOrder() {
		return $this->_defaultOrder;
	}

	/**
 	 * Makes _getReferenceMapNormalized() available to the public.
 	 * @return Array
 	 */
	public function getReferenceMapNormalized() {
		return $this->_getReferenceMapNormalized();
	} 

	/**
 	 * Generates an ON clause from a referenceMap, 
 	 * for use in a JOIN statement.
 	 * @param String $ref
 	 * @param String $thisAlias
 	 * @param String $refAlias
 	 * @return String
 	 */
	public function refMapToOnClause($refModel, $thisAlias = null, $refAlias = null) {
		$thisAlias   = $thisAlias ?: $this->getName();
		$thisAdapter = $this->getAdapter();
		$thisAlias   = $thisAdapter->quoteIdentifier($thisAlias);
		
		$ref        = $this->getReference($refModel);
		$refModel   = new $refModel();
		$refAdapter = $refModel->getAdapter();
		$refAlias   = $refAlias ?: $refModel->getName();
		$refAlias   = $refAdapter->quoteIdentifier($refAlias);

		$on = array();
		foreach ($ref['columns'] as $i => $col) {
			$col = $thisAdapter->quoteIdentifier($col);
			$refCol = $refAdapter->quoteIdentifier($ref['refColumns'][$i]);
			$_on = "{$thisAlias}.{$col} = {$refAlias}.{$refCol}";
			$on[] = $_on;
		}

		$on = implode(' AND ', $on);
		return $on;
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
	public function bindModel($alias, $options = array()) {
		if ($alias instanceof Garp_Model_Db) {
			$alias = get_class($alias);
		}

		if (!is_array($options) && !$options instanceof Garp_Util_Configuration) {
			throw new Exception('$options must be an array or Garp_Util_Configuration');
		}

		if (empty($options['modelClass']) && empty($options['rule']) && substr($alias, 0, 6) !== 'Model_') {
			// Assume $alias is actually a rule and fetch the required info from 
			// the reference.
			$referenceMap = $this->_getReferenceMapNormalized();
			if (empty($referenceMap[$alias])) {
				throw new Exception('Not enough options given. Alias '.$alias.' is not usable as a rule.');
			}
			$reference = $referenceMap[$alias];
			$options['modelClass'] = $reference['refTableClass'];
			$options['rule'] = $alias;
		}

		if (is_array($options)) {
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
 	 * Fetch neighbours
 	 * @param String $sortColumn The column that determines the neighbours
 	 * @param Mixed $sortValue The value of the sortColumn on the middle record
 	 * @param Zend_Db_Select $select SELECT object can be filled with additional query parameters.
 	 * @return Array
 	 */
	public function fetchNeighbors($sortColumn, $sortValue, Zend_Db_Select $select = null) {
		$select = $select ?: $this->select();

		$prevSelect = clone $select;
		$nextSelect = clone $select;

		$prevSortOrder = $sortColumn.' DESC';
		$nextSortOrder = $sortColumn.' ASC';

		$quotedSortColumn = $this->getAdapter()->quoteIdentifier($sortColumn);
		$prevSelect->where($quotedSortColumn.' < ?', $sortValue)->order($prevSortOrder);
		$nextSelect->where($quotedSortColumn.' > ?', $sortValue)->order($nextSortOrder);

		$neighbours = array(
			'prev' => $this->fetchRow($prevSelect),
			'next' => $this->fetchRow($nextSelect)
		);
		return $neighbours;
	}

	/**
 	 * Fetch all records created by a certain someone.
 	 * @param Int $authorId
	 * @param Zend_Db_Select $select
	 * @return Zend_Db_Table_Rowset_Abstract
	 */
	public function fetchByAuthor($authorId, Zend_Db_Select $select = null) {
		$select = $select ?: $this->select();
		$select->where('author_id = ?', $authorId);

		$result = $this->fetchAll($select);
		return $result;
	}

	/**
 	 * Shortcut method for fetching record by id
 	 * @param Int $id
 	 * @return Zend_Db_Table_Row
 	 */
	public function fetchById($id) {
		$select = $this->select()->where('id = ?', $id);
		return $this->fetchRow($select);
	}

	/**
 	 * Shortcut method for fetching record by slug
 	 * @param String $slug
 	 * @return Zend_Db_Table_Row
 	 */
	public function fetchBySlug($slug) {
		$select = $this->select()->where('slug = ?', $slug);
		return $this->fetchRow($select);
	}

	/**
 	 * Quote an array of values
 	 * @param Array $values
 	 * @return Void
 	 */
	public function quoteValues(array &$values) {
		$adapter = $this->getAdapter();
		$quoteInto = function(&$item) use ($adapter) {
			$item = $adapter->quote($item);
		};
		array_walk($values, $quoteInto);
	}

	/**
 	 * Extract primary key information from a WHERE clause and construct a cache
 	 * key from it.
 	 * @param Mixed $where
 	 * @return String
     */
    public function extractPrimaryKey($where) {
      	if (is_array($where)) {
            $where = implode(' AND ', $where);
        }
        $pkColumns = $this->info(Zend_Db_Table_Abstract::PRIMARY);
        $pkValues = array();
        foreach ($pkColumns as $pk) {
            $regexp = '/(?:`?'.preg_quote($this->getName()).'`?\.)?`?(?:'.preg_quote($pk).')`?\s?=\s?(?:(?P<q>[\'"])(?P<value>(?:(?!\k<q>).)*)\k<q>|(?P<rest>\w*))/';
            if (preg_match($regexp, $where, $matches)) {
                // Note: backreference "rest" is there to catch unquoted
                // values. (id = 100 instead of id = "100")
                if (!empty($matches['rest'])) {
                    $value = $matches['rest'];
        		} else {
            		$value = $matches['value'];
        		}
        		$pkValues[$pk] = $value;
        	}
        }
		return $pkValues;
    }

	/**
 	 * Get wether this model caches queries
 	 * @return Boolean
 	 */
	public function getCacheQueries() {
		return self::$_cacheQueries;
	}

	/**
 	 * Set wether this model caches queries
 	 * @param $flag Boolean 
 	 * @return $this
 	 */
	public function setCacheQueries($flag) {
		self::$_cacheQueries = $flag;
		return $this;
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
		$results = -1;
		$this->notifyObservers('beforeFetch', array($this, $select, &$results));
		// Results was untouched, fetch a live result.
		if ($results === -1) {
			$results = parent::$method($select);
			$this->notifyObservers('afterFetch', array($this, &$results, $select));
		}
		return $results;
	}

	/**
	 * Returns the number of records in the database, optionally limited by the provided select object.
	 * @return Int Number of records
	 */
	public function count(Zend_Db_Select $select = null) {
		if (!$select) {
			$select = $this->select();
		}
		$select->from($this->getName(), array('count' => new Zend_Db_Expr('COUNT(*)')));
		if ($row = $this->fetchRow($select)) {
			return (int)$row->count;
		}
		return 0;
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
	 * Return an observer
	 * @param String $name The name of the observer
	 * @return Garp_Util_Observer Or null if not found
	 */
	public function getObserver($name) {
		return array_key_exists($name, $this->_observers) ? $this->_observers[$name] : null;
	}

	/**
 	 * Return all observers
 	 * @return Array
 	 */
	public function getObservers() {
		return $this->_observers;
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

	/**
 	 * Return joint view name
 	 * @return String
 	 */
	public function getJointView() {
		return $this->_jointView;
	}
}
