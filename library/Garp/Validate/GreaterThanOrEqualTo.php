<?php

/**
 * Garp_Validate_GreaterThanOrEqualTo
 * Extends Zend_Validate_GreaterThan, overwriting the isValid function to support equal to
 *
 * @package Garp_Validate
 * @author  Han Kortekaas <han@grrr.nl>
 */
class Garp_Validate_GreaterThanOrEqualTo extends Zend_Validate_GreaterThan
{
    const NOT_GREATER_OR_EQUAL_TO = 'notGreaterThanOrEqualTo';

    /**
     * @var array
     */
    protected $_messageTemplates = array(
        self::NOT_GREATER_OR_EQUAL_TO => "'%value%' is not greater than or equal to '%min%'"
    );

    /**
     * Defined by Zend_Validate_Interface
     * Overwrites the function in Zend_Validate_GreaterThan to deal with equal to
     *
     * Returns true if $value is greater than or equal to min option
     *
     * @param  mixed $value
     * @return boolean
     */
    public function isValid($value)
    {
        $this->_setValue($value);

        if ($this->_min > $value) {
            $this->_error(self::NOT_GREATER_OR_EQUAL_TO);
            return false;
        }
        return true;
    }

}
