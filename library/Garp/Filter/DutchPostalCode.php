<?php
/**
 * Garp_Filter_DutchPostalCode
 * Ensures a reliable format for Dutch postal codes
 *
 * @package Garp_Filter
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Filter_DutchPostalCode implements Zend_Filter_Interface {
    /**
     * Returns the result of filtering $value
     *
     * @param  mixed $value
     * @return mixed
     */
    public function filter($value) {
        if (preg_match(Garp_Validate_DutchPostalCode::POSTALCODE_REGEXP, $value, $matches)) {
            return $matches[1] . strtoupper($matches[2]);
        }
        return $value;
    }
}
