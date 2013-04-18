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
 	 * @param Garp_Model_Db $model
 	 * @param Mixed $where
 	 * @return Void
 	 */
	public function __construct(Garp_Model_Db $model, $where) {
		$this->_model = $model;
		$this->_where = $where;
	}

	/**
	 * Extract primary key information from a WHERE clause and construct a cache key from it.
	 * @param Garp_Model_Db $model
	 * @param Mixed $where
	 * @return String
	 */
	public function extract() {
		if (is_array($this->_where)) {
			$where = implode(' AND ', $where);
		}
		$pkColumns = $model->info(Zend_Db_Table_Abstract::PRIMARY);
		$pkValues = array();
		foreach ($pkColumns as $pk) {
			$regexp = '/(?:`?'.preg_quote($model->getName()).'`?\.)?`?(?:'.preg_quote($pk).')`?\s?=\s?(?:(?P<q>[\'"])(?P<value>(?:(?!\k<q>).)*)\k<q>|(?P<rest>\w*))/';
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

		if (count($pkValues) === count($pkColumns)) {
			$pks = array_keys($pkValues);
			$pkCount = count($pks);
			sort($pks);
			$cacheKey = $model->getName();
			foreach ($pks as $i => $pk) {
				$cacheKey .= $pkValues[$pk];
				if ($i < $pkCount-1) {
					$cacheKey .= '_';
				}
			}
			return $cacheKey;
		}
		return false;
	}
}
