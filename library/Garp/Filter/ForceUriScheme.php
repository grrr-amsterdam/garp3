<?php
/**
 * Garp_Filter_ForceUriScheme
 * Force a URI scheme onto a string.
 * For instance "google.com" becomes "http://google.com".
 * 
 * @author Harmen Janssen | grrr.nl
 * @version 1
 * @package Garp
 * @subpackage Filter
 */
class Garp_Filter_ForceUriScheme implements Zend_Filter_Interface {
    /**
     * Returns the result of filtering $value
     * @param  mixed $value
     * @return mixed
     */
	public function filter($value) {
		if ($value && !preg_match('~^[a-z]+://~i', $value)) {
			$value = 'http://'.$value;
		}
		return $value;
	}
}
