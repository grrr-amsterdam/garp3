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
		$this->_where = $where;
	}

	/**
	 * Extract primary key information from a WHERE clause and construct a cache key from it.
	 * @return Array
	 */
	public function extract() {
		if (is_array($this->_where)) {
			$this->_where = implode(' AND ', $this->_where);
		}
		$pkColumns = $this->_model->info(Zend_Db_Table_Abstract::PRIMARY);
		$pkValues = array();
		foreach ($pkColumns as $pk) {
			$regexp = '/(?:`?'.preg_quote($this->_model->getName()).'`?\.)?`?(?:'.preg_quote($pk).')`?\s?=\s?(?:(?P<q>[\'"])(?P<value>(?:(?!\k<q>).)*)\k<q>|(?P<rest>\w*))/';
			if (preg_match($regexp, $this->_where, $matches)) {
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
}
