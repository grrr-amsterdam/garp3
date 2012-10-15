<?php
/**
 * Garp_I18n
 * Wrapper around various i18n related functionality.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage I18n
 * @lastmodified $Date: $
 */
class Garp_I18n {
	/**
	 * Return the current locale
	 * @return String
	 */
	public static function getCurrentLocale() {
		return Zend_Controller_Front::getInstance()->getRequest()->getParam('locale');
	}
	
	
	/**
	 * Return the default locale (as defined in application.ini)
	 * @return String
	 */
	public static function getDefaultLocale() {
		if (Zend_Registry::isRegistered('Zend_Locale')) {
			$locale = Zend_Registry::get('Zend_Locale');
			return $locale->getDefault();
		} else {
			throw new Garp_I18n_Exception('Zend_Locale is not registered in Zend_Registry.');
		}
	}
	
	
	/**
	 * Return a list of all possible locales
	 * @return Array
	 */
	public static function getAllPossibleLocales() {
		return Zend_Controller_Front::getInstance()->getParam('locales');
	}
}