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
	
	
	/**
 	 * Virtual properties. Used with setVirtual() when you wish to transport arbitrary values
 	 * thru Row objects.
 	 * @var Array
 	 */
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

		$currentFromPart = $select->getPart(Zend_Db_Select::FROM);
		if (!is_array($currentFromPart) || !array_key_exists('m', $currentFromPart)) {
			$select->from(array('m' => $matchName), Zend_Db_Select::SQL_WILDCARD, $matchSchema);
		}

		$select->joinInner(array('i' => $interName), $joinCond, array(), $interSchema)
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
     * ATTENTION
     * This method is copied from Zend_Db_Table_Row_Abstract.
     * It is altered to add the table name to the WHERE clause. So instead of
     *
     * WHERE id = 42
     *
     * the query becomes:
     *
     * WHERE MyStuff.id = 42
     *
     * Query a parent table to retrieve the single row matching the current row.
     *
     * @param string|Zend_Db_Table_Abstract $parentTable
     * @param string                        OPTIONAL $ruleKey
     * @param Zend_Db_Table_Select          OPTIONAL $select
     * @return Zend_Db_Table_Row_Abstract   Query result from $parentTable
     * @throws Zend_Db_Table_Row_Exception If $parentTable is not a table or is not loadable.
     */
    public function findParentRow($parentTable, $ruleKey = null, Zend_Db_Table_Select $select = null)
    {
        $db = $this->_getTable()->getAdapter();

        if (is_string($parentTable)) {
            $parentTable = $this->_getTableFromString($parentTable);
        }

        if (!$parentTable instanceof Zend_Db_Table_Abstract) {
            $type = gettype($parentTable);
            if ($type == 'object') {
                $type = get_class($parentTable);
            }
            require_once 'Zend/Db/Table/Row/Exception.php';
            throw new Zend_Db_Table_Row_Exception("Parent table must be a Zend_Db_Table_Abstract, but it is $type");
        }

        // even if we are interacting between a table defined in a class and a
        // table via extension, ensure to persist the definition
        if (($tableDefinition = $this->_table->getDefinition()) !== null
            && ($parentTable->getDefinition() == null)) {
            $parentTable->setOptions(array(Zend_Db_Table_Abstract::DEFINITION => $tableDefinition));
        }

        if ($select === null) {
            $select = $parentTable->select();
        } else {
            $select->setTable($parentTable);
        }

        $map = $this->_prepareReference($this->_getTable(), $parentTable, $ruleKey);

        // iterate the map, creating the proper wheres
        for ($i = 0; $i < count($map[Zend_Db_Table_Abstract::COLUMNS]); ++$i) {
            $dependentColumnName = $db->foldCase($map[Zend_Db_Table_Abstract::COLUMNS][$i]);
            $value = $this->_data[$dependentColumnName];
            // Use adapter from parent table to ensure correct query construction
            $parentDb = $parentTable->getAdapter();
            $parentColumnName = $parentDb->foldCase($map[Zend_Db_Table_Abstract::REF_COLUMNS][$i]);
			$parentColumn = $parentDb->quoteIdentifier($parentTable->getName()).'.';
            $parentColumn .= $parentDb->quoteIdentifier($parentColumnName, true);
            $parentInfo = $parentTable->info();

            // determine where part
            $type     = $parentInfo[Zend_Db_Table_Abstract::METADATA][$parentColumnName]['DATA_TYPE'];
            $nullable = $parentInfo[Zend_Db_Table_Abstract::METADATA][$parentColumnName]['NULLABLE'];
            if ($value === null && $nullable == true) {
                $select->where("$parentColumn IS NULL");
            } elseif ($value === null && $nullable == false) {
                return null;
            } else {
                $select->where("$parentColumn = ?", $value, $type);
            }

        }

        return $parentTable->fetchRow($select);
    }
	
		
	/**
	 * Return the value of the primary key(s) for this row.
	 * Extended to not return arrays when primary key is just one column.
	 * (which is true 99 out of a 100 times)
	 * @return Mixed
	 */
	public function getPrimaryKey($useDirty = true) {
		$primary = (array)$this->_getTable()->info(Zend_Db_Table::PRIMARY);
		$out = array();
		foreach ($primary as $key) {
			if (!isset($this->$key)) {
				throw new Garp_Db_Table_Row_Exception_PrimaryKeyNotInRow("$key was not in the row");
			}
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
	public function getRelated($binding = null) {
		if (is_null($binding)) {
			return $this->_related;
		}
		return $this->_related[$binding];
	}
	
	
	/**
 	 * Set arbitrary virtual value that is not a table column.
 	 * @param String $key
 	 * @param Mixed $value
 	 * @return Garp_Db_Table_Row $this
 	 */
	public function setVirtual($key, $value) {
		$this->_virtual[$key] = $value;
		return $this;
	}
	
	
	/**
 	 * Return all virtual values
 	 * @return Array
 	 */
	public function getVirtual() {
		return $this->_virtual;
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
     * Set row field value
     *
     * @param  string $columnName The column key.
     * @param  mixed  $value      The value for the property.
     * @return void
     * @throws Zend_Db_Table_Row_Exception
     */
    public function __set($columnName, $value) {
		try {
			parent::__set($columnName, $value);
		} catch (Zend_Db_Table_Row_Exception $e) {
			if (array_key_exists($columnName, $this->_related)) {
				$this->_related[$columnName] = $value;
			} elseif (array_key_exists($columnName, $this->_virtual)) {
				$this->_virtual[$columnName] = $value;
			} else {
				throw $e;
			}
		}
    }


	/**
 	 * Test existence of row field
 	 * @param String $columnName The column key.
 	 * @return Boolean
 	 */
	public function __isset($columnName) {
		// Check if native column from database.
		$result = parent::__isset($columnName);
		if (!$result) {
			// Check if "virtual" column added thru Garp modification.
			$result = array_key_exists($columnName, $this->_related) ||
				array_key_exists($columnName, $this->_virtual);
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
			if ($rowset instanceof Zend_Db_Table_Row_Abstract || $rowset instanceof Zend_Db_Table_Rowset_Abstract) {
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
