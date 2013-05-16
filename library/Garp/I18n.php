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
		if (!Zend_Registry::isRegistered('Zend_Locale')) {
			throw new Garp_I18n_Exception('Zend_Locale is not registered in Zend_Registry.');
		}
		return Zend_Registry::get('Zend_Locale')->getLanguage();
	}
	
	
	/**
	 * Return the default locale (as defined in application.ini)
	 * @return String
	 */
	public static function getDefaultLocale() {
		if (!Zend_Registry::isRegistered('Zend_Locale')) {
			throw new Garp_I18n_Exception('Zend_Locale is not registered in Zend_Registry.');
		}
		$locale = Zend_Registry::get('Zend_Locale');
		$default = $locale->getDefault();
		if ($default) {
			$keys = array_keys($default);
			$default = current($keys);
		}
		return $default;
	}
	
	
	/**
	 * Return a list of all possible locales
	 * @return Array
	 */
	public static function getLocales() {
		return Zend_Controller_Front::getInstance()->getParam('locales');
	}

	/**
 	 * Generate localized versions of routes
 	 * @param Array $routes The originals
 	 * @param Array $locales
 	 * @return Array
 	 */
	public static function getLocalizedRoutes(array $routes, array $locales) {
		$localizedRoutes = array();
		$defaultLocale = self::getDefaultLocale();
		$requiredLocalesRegex = '^('.join('|', $locales).')$';
		
		foreach ($routes as $key => $value) {
			// First let's add the default locale to this routes defaults.
			$defaults = isset($value['defaults'])
				? $value['defaults']
				: array();

			// Always default all routes to the Zend_Locale default
			$value['defaults'] = array_merge(array('locale' => $defaultLocale ), $defaults);

			//$routes[$key] = $value;

			// Get our route and make sure to remove the first forward slash
			// since it's not needed.
			$routeString = $value['route'];
			$routeString = ltrim($routeString, '/\\');

			// Modify our normal route to have the locale parameter.
			if (!isset($value['type']) || $value['type'] === 'Zend_Controller_Router_Route') {
				$value['route'] = ':locale/'.$routeString;
				$value['reqs']['locale'] = $requiredLocalesRegex;
				$localizedRoutes['locale_'.$key] = $value;
			} else if ($value['type'] === 'Zend_Controller_Router_Route_Regex') {
				$value['route'] = '('.join('|', $locales).')\/'.$routeString;

				// Since we added the local regex match, we need to bump the existing
				// match numbers plus one.
				$map = isset($value['map']) ? $value['map'] : array();
				foreach ($map as $index => $word) {
					unset($map[$index++]);
					$map[$index] = $word;
				}

				// Add our locale map
				$map[1] = 'locale';
				ksort($map);

				$value['map'] = $map;

				$localizedRoutes['locale_'.$key] = $value;
			} elseif ($value['type'] === 'Zend_Controller_Router_Route_Static') {
				foreach ($locales as $locale) {
					$value['route'] = $locale.'/'.$routeString;
					$value['defaults']['locale'] = $locale;
					$routes['locale_'.$locale.'_'.$key] = $value;
				}
			}
		}
		return $localizedRoutes;
	}
}
