<?php
/**
 * Garp_Validate_Identical
 * class description
 *
 * @package Garp_Validate
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Validate_Identical extends Zend_Validate_Identical {
    /**
     * Overwritten to support nested tokens, from input fields in subforms (such
     * as credentials[pwd].
     *
     * @param string $value
     * @param array $context
     * @return bool
     */
    public function isValid($value, $context = null) {
        $this->_setValue((string) $value);

        $token = $this->getToken();
        $pos = strrpos($token, '[');
        if ($pos !== false) {
            $token = rtrim($token, ']');
            $token = substr($token, $pos+1);
        }

        if (($context !== null) && isset($context) && array_key_exists($token, $context)) {
            $token = $context[$token];
        }

        if ($token === null) {
            $this->_error(self::MISSING_TOKEN);
            return false;
        }

        $strict = $this->getStrict();
        if (($strict && ($value !== $token)) || (!$strict && ($value != $token))) {
            $this->_error(self::NOT_SAME);
            return false;
        }

        return true;
    }
}
