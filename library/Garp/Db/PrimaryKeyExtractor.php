<?php
/**
 * Garp_Db_PrimaryKeyExtractor
 * Extracts primary keys from a WHERE clause
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0.1
 * @package      Garp_Db
 */
class Garp_Db_PrimaryKeyExtractor {
    protected $_tableName;
    protected $_pkColumns;

    /**
     * @var Mixed
     */
    protected $_where;

    /**
     * Class constructor
     * @param Mixed $tableName Either a model or a table name as string
     * @param Mixed $pkColumns Either an array, or if the first argument is a model,
     *                         it is assumed to be the WHERE clause.
     * @param Mixed $where
     * @return Void
     */
    public function __construct($tableName, $pkColumns, $where = null) {
        if ($tableName instanceof Zend_Db_Table_Abstract) {
            $this->_tableName = $tableName->getName();
            $this->_pkColumns = $tableName->info(Zend_Db_Table_Abstract::PRIMARY);
            $this->_where = $pkColumns;
        } else if (func_num_args() === 3) {
            $this->_tableName = $tableName;
            $this->_pkColumns = (array)$pkColumns;
            $this->_where = $where;
        } else {
            throw new InvalidArgumentException('Invalid parameters. ' .
                'Either provide the full 3 arguments, or provide a model to take info from.');
        }

        if (is_array($this->_where)) {
            $this->_where = implode(' AND ', $this->_where);
        }
    }

    /**
     * Extract primary key information from a WHERE clause and construct a cache key from it.
     * @param Array $pkColumns Primary keys to extract (will be taken from the model if omitted)
     * @return Array
     */
    public function extract() {
        $pkValues = array();
        // Lose the parentheses, they mean nothing to us (more importantly, "((key = value))" fails)
        // @fixme A string primary key containing a "(" will not be returned.
        $where = str_replace(array('(', ')'), '', $this->_where);
        foreach ($this->_pkColumns as $pk) {
            $regexp = '/(?:`?' . preg_quote($this->_tableName) . '`?\.|\s|^){1}`?(?:' .
                preg_quote($pk) .
                ')`?\s?=\s?(?:(?P<q>[\'"])(?P<value>(?:(?!\k<q>).)*)\k<q>|(?P<rest>\w*))/';
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
