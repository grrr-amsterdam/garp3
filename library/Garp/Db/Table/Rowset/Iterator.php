<?php
/**
 * Garp_Db_Table_Rowset_Iterator
 * Provides a single loopable interface for Garp_Db_Table_Row and Garp_Db_Table_Rowset.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp_Db_Table_Rowset
 */
class Garp_Db_Table_Rowset_Iterator {
	/**
 	 * The result of the query, either a set or a single row.
 	 * @var Garp_Db_Table_Rowset|Garp_Db_Table_Row
 	 */
	protected $_result;


	/**
 	 * The function that's executed on all returned rows.
 	 * Must be valid input for array_map, can be an array with object and method,
 	 * or a closure, or a string referencing a function.
 	 * @var Mixed
 	 */
	protected $_function;


	/**
 	 * Class constructor
 	 * @param Garp_Db_Table_Row|Garp_Db_Table_Rowset $result The query result
 	 * @param Mixed $fn Valid input for array_map, can be an array with object and method,
 	 *                  or a closure, or a string referencing a function.
 	 * @return Void
 	 */
	public function __construct(&$result, $fn) {
		$this->setResult($result);
		$this->setFunction($fn);
	}


	/**
 	 * Process the result, execute the given function for every row.
 	 * Make sure to rewind rowsets at the end.
 	 * @return Void
 	 */
	public function walk() {
		$this->_beforeWalk();
		array_walk($this->_result, $this->_function);
		$this->_afterWalk();
	}


	/**
 	 * Callback before walking over the results.
 	 * Provides a single loopable interface, even for single Rows.
 	 * @return Void
 	 */
	protected function _beforeWalk() {
		if (!$this->_result instanceof Garp_Db_Table_Rowset) {
			$this->_result = array($this->_result);
		}
	}		


	/**
 	 * Callback after walking over the results.
 	 * Returns everything to its original state.
 	 * @return Void
 	 */
	protected function _afterWalk() {
		// return the pointer of a Rowset to 0
		if ($this->_result instanceof Garp_Db_Table_Rowset) {
			$this->_result->rewind();
		} else {
			// also, return results to the original format if it was no Rowset to begin with.
			$this->_result = $this->_result[0];
		}
	}


	/**
 	 * Get the result
 	 * @return Garp_Db_Table_Rowset|Garp_Db_Table_Row
 	 */
	public function getResult() {
		return $this->_result;
	}


	/**
 	 * Set the result
 	 * @param Garp_Db_Table_Row|Garp_Db_Table_Rowset $result The query result
 	 * @return Void
 	 */
	public function setResult(&$result) {
		if (!$result instanceof Garp_Db_Table_Row &&
			!$result instanceof Garp_Db_Table_Rowset) {
				throw new InvalidArgumentException(__METHOD__.' expects parameter 1 to be a'.
					'Garp_Db_Table_Row or Garp_Db_Table_Rowset. '.gettype($result).' given.');
		}
		$this->_result = $result;
	}		


	/**
 	 * Get the function
 	 * @return Mixed
 	 */
	public function getFunction($function) {
		return $this->_function;
	}


	/**
 	 * Set the function
 	 * @param Mixed $fn Valid input for array_map, can be an array with object and method,
 	 *                  or a closure, or a string referencing a function.
 	 * @return Void
 	 */
	public function setFunction($function) {
		if (!is_callable($function)) {
			throw new InvalidArgumentException(__METHOD__.' expects parameter 1 to be callable.');
		}
		$this->_function = $function;
	}
}
