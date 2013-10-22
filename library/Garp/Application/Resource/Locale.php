<?php
/**
 * Garp_Application_Resource_Locale
 * Does a setlocale() call to make strftime() and friends behave.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp_Application_Resource
 */
class Garp_Application_Resource_Locale extends Zend_Application_Resource_Locale {
    /**
     * Retrieve locale object
     *
     * @return Zend_Locale
     */
	public function getLocale() {
		$locale = parent::getLocale();
		if ($locale) {
			setlocale(LC_ALL, $locale->__toString());
		}

		return $locale;
	}
}
