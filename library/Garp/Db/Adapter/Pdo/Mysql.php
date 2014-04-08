<?php
/**
 * Garp_Db_Adapter_Pdo_Mysql
 * A better performing MySQL adapter.
 * All this does is overwrite the quote() methods. In Zend's native implementation the quote() method of 
 * Zend_Db_Adapter_Abstract uses $this->_connect() to create an instance of a specific PDO driver.
 * In that driver is a RDBMS specific _quote() method. 
 * This is a pretty good idea, except for that pesky _connect() call. Fuck that. We do not want to 
 * establish an actual connection to a database every time we do $select->where('id = ?', $id) somewhere in
 * the application.
 * So these methods take over and skip that connect call. I kept it in for clarity, but commented out.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class Garp_Db_Adapter_Pdo_Mysql extends Zend_Db_Adapter_Pdo_Mysql {
	public function quote($value, $type = null) {
        //$this->_connect();

        if ($value instanceof Zend_Db_Select) {
            return '(' . $value->assemble() . ')';
        }

        if ($value instanceof Zend_Db_Expr) {
            return $value->__toString();
        }

        if (is_array($value)) {
            foreach ($value as &$val) {
                $val = $this->quote($val, $type);
            }
            return implode(', ', $value);
        }

        if ($type !== null && array_key_exists($type = strtoupper($type), $this->_numericDataTypes)) {
            $quotedValue = '0';
            switch ($this->_numericDataTypes[$type]) {
                case Zend_Db::INT_TYPE: // 32-bit integer
                    $quotedValue = (string) intval($value);
                    break;
                case Zend_Db::BIGINT_TYPE: // 64-bit integer
                    // ANSI SQL-style hex literals (e.g. x'[\dA-F]+')
                    // are not supported here, because these are string
                    // literals, not numeric literals.
                    if (preg_match('/^(
                          [+-]?                  # optional sign
                          (?:
                            0[Xx][\da-fA-F]+     # ODBC-style hexadecimal
                            |\d+                 # decimal or octal, or MySQL ZEROFILL decimal
                            (?:[eE][+-]?\d+)?    # optional exponent on decimals or octals
                          )
                        )/x',
                        (string) $value, $matches)) {
                        $quotedValue = $matches[1];
                    }
                    break;
                case Zend_Db::FLOAT_TYPE: // float or decimal
                    $quotedValue = sprintf('%F', $value);
            }
            return $quotedValue;
        }

        return $this->_quote($value);
    }


	/**
     * Quote a raw string.
     *
     * @param string $value     Raw string
     * @return string           Quoted string
     */
    protected function _quote($value) {
        if (is_int($value)) {
            return $value;
        } elseif (is_float($value)) {
            return sprintf('%F', $value);
        }
        return "'" . addcslashes($value, "\000\n\r\\'\"\032") . "'";
    }
}
