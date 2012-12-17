<?php
/**
 * Garp_Model_Behavior_Weighable
 * The presence of this behavior indicates this model
 * has a relationship to another model and the sorting of this
 * model can be custom thru a "weight" column.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Behavior
 * @lastmodified $Date: $
 */
class Garp_Model_Behavior_Weighable extends Garp_Model_Behavior_Abstract {
	/**
	 * The key used to store the foreign key column name in the config array
	 * @var String
	 */
	const FOREIGN_KEY_COLUMN_KEY = 'foreignKeyColumn';
	
	
	/**
	 * The key used to store the weight column name in the config array
	 * @var String
	 */
	const WEIGHT_COLUMN_KEY = 'weightColumn';
	
	
	/**
	 * Store relationships, the foreign keys and the weight column here
	 * @var Array
	 */
	protected $_relationConfig = array();
	
	
	/**
	 * Set relationship configuration.
	 * Must look like this:
	 * array(
	 *   'Model_User' => array(
	 *     'foreignKeyColumn' => 'user_id',
	 *     'weightColumn' => 'user_weight'
	 *   )
	 * )
	 * This will be validated immediately.
	 * @param Array $config Configuration values
	 * @return Array The modified array
	 */
	protected function _setup($config) {
		foreach ($config as $model => $relationOptions) {
			if (!is_array($relationOptions) ||
				!array_key_exists(self::FOREIGN_KEY_COLUMN_KEY, $relationOptions) ||
				!array_key_exists(self::WEIGHT_COLUMN_KEY, $relationOptions)) {
				throw new Garp_Model_Behavior_Exception('Insufficient configuration values found. '.
					'\''.self::FOREIGN_KEY_COLUMN_KEY.'\' and \''.self::WEIGHT_COLUMN_KEY.'\' must be present.');
			}
		}
		
		$this->_relationConfig = $config;
	}
	
	
	/**
	 * BeforeFetch callback: adds an order clause on the weight column.
	 * @param Array $args Arguments associated with this event
	 * @return Void
	 */
	public function beforeFetch(array &$args) {
		$select = $args[1];
		// Distill the WHERE class from the Select object
		$where = $select->getPart(Zend_Db_Select::WHERE);
		
		// Save the existing ORDER clause.
		$originalOrder = $select->getPart(Zend_Db_Select::ORDER);
		$select->reset(Zend_Db_Select::ORDER);
		
		/**
		 * If a registered foreign key (see self::_relationConfig) is found, this query is 
		 * considered to be a related fetch() command, and an ORDER BY clause is added with
		 * the registered weight column.
		 */
		foreach ($where as $w) {
			foreach ($this->_relationConfig as $model => $modelRelationConfig) {
				if (strpos($w, $modelRelationConfig[self::FOREIGN_KEY_COLUMN_KEY]) !== false) {
					$select->order($modelRelationConfig[self::WEIGHT_COLUMN_KEY].' DESC');
				}
			}
		}
		
		// Return the existing ORDER clause, only this time '<weight-column> DESC' will be in front of it
		foreach ($originalOrder as $order) {
			// [0] = column, [1] = direction
			$select->order($order[0].' '.$order[1]);
		}
	}
	
	
	/**
	 * BeforeInsert callback, find and insert the current highest 'weight' value + 1 
	 * in the weight column
	 * @param Array $args 
	 * @return Void
	 */
	public function beforeInsert(&$args) {
		$model = $args[0];
		$data = &$args[1];
		foreach ($this->_relationConfig as $foreignModel => $modelRelationConfig) {
			$foreignKey = $modelRelationConfig[self::FOREIGN_KEY_COLUMN_KEY];
			// only act if the foreign key column is filled
			if (!empty($data[$foreignKey])) {
				$maxWeight = $this->findHighestWeight($model, $data[$foreignKey], $modelRelationConfig);
				$data[$modelRelationConfig[self::WEIGHT_COLUMN_KEY]] = ($maxWeight+1);
			}
		}
	}
	
	
	/**
	 * BeforeUpdate callback, find and insert current highest 'weight' value + 1, if it is null
	 * @param Array $args
	 * @return Void
	 */
	public function beforeUpdate(&$args) {
		$model = $args[0];
		$data = &$args[1];
		$where = $args[2];
		
		foreach ($this->_relationConfig as $foreignModel => $modelRelationConfig) {
			$foreignKey = $modelRelationConfig[self::FOREIGN_KEY_COLUMN_KEY];
			$weightColumn = $modelRelationConfig[self::WEIGHT_COLUMN_KEY];
			/**
			 * Only act if the foreign key field is filled, since weight is calculated per foreign key.
			 * This means duplicate weight values might occur in the database, but the foreign key 
			 * will always differ.
			 * You should be able to add a UNIQUE INDEX to your table containing the foreign key column and the
			 * weight column.
			 */
			if (array_key_exists($foreignKey, $data) && $data[$foreignKey]) {
				/**
				 * If the weight column is not given in the new data, fetch it. 
				 * This is quite the pickle, since the given WHERE clause might update (and thus fetch)
				 * multiple records, but we can only provide one set of modified data to apply to every 
				 * matching record. 
				 * This is currently not fixed. If a WHERE clause is given that matches multiple records,
				 * the current weight of the first record found is used.
				 */
				if (!array_key_exists($weightColumn, $data)) {
					$data[$weightColumn] = $this->findCurrentWeight($model, $where, $data[$foreignKey], $modelRelationConfig);
				}
			
				// only act if the foreign key column is filled, and the weight column is null
				if (!$data[$weightColumn]) {
					$maxWeight = $this->findHighestWeight($model, $data[$foreignKey], $modelRelationConfig);
					$data[$weightColumn] = ($maxWeight+1);
				}
			}
		}
	}
	
	
	/**
	 * Find the highest weight value for a certain relationship with a foreign key
	 * @param Garp_Model $model
	 * @param Int $foreignKey
	 * @param Array $modelRelationConfig
	 * @return Int
	 */
	public function findHighestWeight(Garp_Model $model, $foreignKey, array $modelRelationConfig) {
		$foreignKeyColumn = $model->getAdapter()->quoteIdentifier($modelRelationConfig[self::FOREIGN_KEY_COLUMN_KEY]);
		$weightColumn	  = $model->getAdapter()->quoteIdentifier($modelRelationConfig[self::WEIGHT_COLUMN_KEY]);
		$select = $model->select()
						->from($model->getName(), array('max' => 'MAX('.$weightColumn.')'))
						->where($foreignKeyColumn.' = ?', $foreignKey)
						;

		$result = $model->fetchRow($select);
		if ($result && $result->max) {
			return $result->max;
		}
		return 0;
	}
	
	
	/**
	 * Return the current weight of a set of records.
	 * Note that only the first record found will be used. Working with multiple records
	 * (which is possible using Zend's update() functionality) is not implemented.
	 * @param Garp_Model $model
	 * @param String $where
	 * @param Array $modelRelationConfig 
	 * @return Int
	 */
	public function findCurrentWeight(Garp_Model $model, $where, $foreignKey, array $modelRelationConfig) {
		$foreignKeyColumn = $model->getAdapter()->quoteIdentifier($modelRelationConfig[self::FOREIGN_KEY_COLUMN_KEY]);
		$weightColumn	  = $modelRelationConfig[self::WEIGHT_COLUMN_KEY];
		$select = $model->select()
						->from($model->getName(), array('weight' => $weightColumn))
						->where($foreignKeyColumn.' = ?', $foreignKey)
						;
		$where = (array)$where;
		foreach ($where as $w) {
			$select->where($w);
		}
		$result = $model->fetchRow($select);
		if ($result && $result->weight) {
			return $result->weight;
		}
		return 0;
	}
}
