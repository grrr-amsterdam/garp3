<?php
/**
 * Garp_Db_PrimaryKeyExtractor
 * Extracts primary keys from a WHERE clause
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp_Db
 */
class Garp_Db_PrimaryKeyExtractor {
	/**
 	 * @var Garp_Model_Db
 	 */
	protected $_model;

	/**
 	 * @var Mixed
 	 */
	protected $_where;

	/**
 	 * Class constructor
 	 * @param Zend_Db_Table_Abstract $model
 	 * @param Mixed $where
 	 * @return Void
 	 */
	public function __construct(Zend_Db_Table_Abstract $model, $where) {
		$this->_model = $model;
		if (is_array($where)) {
			$where = implode(' AND ', $where);
		}
		$this->_where = $where;
	}

	/**
	 * Extract primary key information from a WHERE clause and construct a cache key from it.
	 * @return Array
	 */
	public function extract() {
		$pkColumns = $this->_model->info(Zend_Db_Table_Abstract::PRIMARY);
		$pkValues = array();
		$table = $this->_model->getName();
		$where = $this->_where;
		// Lose the parentheses, they mean nothing to us (more importantly, "((key = value))" fails)
		// @fixme A string primary key containing a "(" will not be returned. 
		$where = str_replace(array('(', ')'), '', $where);
		foreach ($pkColumns as $pk) {
			$regexp = '/(?:`?'.preg_quote($table).'`?\.|\s|^){1}`?(?:'.preg_quote($pk).')`?\s?=\s?(?:(?P<q>[\'"])(?P<value>(?:(?!\k<q>).)*)\k<q>|(?P<rest>\w*))/';
			if (!preg_match($regexp, $where, $matches)) {
				continue;
			}
			// Note: backreference "rest" is there to catch unquoted
			// values. (id = 100 instead of id = "100")
			$value = $matches['value'];
			if (!empty($matches['rest'])) {
				$value = $matches['rest'];
			}
			$pkValues[$pk] = $value;
		}
		return $pkValues;
	}
}
