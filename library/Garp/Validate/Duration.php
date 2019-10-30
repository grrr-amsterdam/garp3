<?php
/**
 * Garp_Validate_Duration
 * For now a fairly simple validator that checks wether the given timestamp is
 * at least 1 second ago.
 *
 * @package Garp_Validate
 * @author  Harmen Janssen <harmen@grrr.nl>
 * @todo We could make this a proper validator that can check the difference
 * between two timestamps, instead of the simple "Is the diff between timestamp
 * and time() 1 second?".
 */
class Garp_Validate_Duration extends Zend_Validate_Abstract {

    const MIN_DURATION = 1;
    const DURATION_TOO_SHORT = 'durationTooShort';

    protected $_errorMessages = array(
        //                           is this proper English?
        self::DURATION_TOO_SHORT => 'The timestamp is not long ago enough'
    );

    public function isValid($value) {
        if (!is_numeric($value) || time() - $value <= self::MIN_DURATION) {
            $this->_error(self::DURATION_TOO_SHORT);
            return false;
        }
        return true;
    }
}
