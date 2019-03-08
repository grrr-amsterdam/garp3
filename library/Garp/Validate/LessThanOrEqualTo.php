<?php

/**
 * Garp_Validate_LessThanOrEqualTo
 * Extends Zend_Validate_LessThan, overwriting the isValid function to support equal to
 *
 * @package Garp_Validate
 * @author  Han Kortekaas <han@grrr.nl>
 */
class Garp_Validate_LessThanOrEqualTo extends Zend_Validate_LessThan
{
    const NOT_LESS_OR_EQUAL_TO = 'notLessThanOrEqualTo';

    /**
     * @var array
     */
    protected $_messageTemplates = array(
        self::NOT_LESS_OR_EQUAL_TO => "'%value%' is not less than or equal to '%max%'"
    );

    /**
     * Defined by Zend_Validate_Interface
     * Overwrites the function in Zend_Validate_LessThan to deal with equal to
     *
     * Returns true if $value is less than or equal to max option
     *
     * @param  mixed $value
     * @return boolean
     */
    public function isValid($value)
    {
        $this->_setValue($value);

        if ($this->_max < $value) {
            $this->_error(self::NOT_LESS_OR_EQUAL_TO);
            return false;
        }
        return true;
    }

}
