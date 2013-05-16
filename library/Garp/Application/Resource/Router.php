<?php
/**
 * Garp_Application_Resource_Router
 * Creates internationalized versions of known routes.
 *
 * Example: when you have defined "/blog/:slug" and you have configured 
 * "en" and "nl" as available locales, this resource will silently add aliases
 * for this route:
 *
 * /en/blog/:slug
 * /nl/blog/:slug
 *
 * @author Joe Gornick | joegornick.com
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Resource
 * @lastmodified $Date: $
 */
class Garp_Application_Resource_Router extends Zend_Application_Resource_Router {
	/**
	 * This property lets this Resource override the existing Resource
	 * @var String
	 */
	public $_explicitType = 'router';
 
	/**
	 * @var Zend_Locale
	 */
	protected $_locale;
 
	/**
	 * Retrieve router object
	 * @return Zend_Controller_Router_Rewrite
	 */
	public function getRouter() {
		$routesIni = $this->_getRoutesConfig();
		$this->setOptions($routesIni->toArray());
		$options = $this->getOptions();

		if ($this->_localeIsEnabled()) {
			$bootstrap = $this->getBootstrap();

			if (!$this->_locale) {
				$bootstrap->bootstrap('Locale');
				$this->_locale = $bootstrap->getContainer()->locale;
			}

			$defaultLocale = array_keys($this->_locale->getDefault());
			$defaultLocale = $defaultLocale[0];

			$locales = $this->_getPossibleLocales();
			$routes = $options['routes'];
			$localizedRoutes = Garp_I18n::getLocalizedRoutes($routes, $locales);
			$options['routes'] = array_merge($routes, $localizedRoutes);
			$this->setOptions($options);
		}

		$router = parent::getRouter();
		$router->addDefaultRoutes();
		return $router;
	}

	/**
	 * Retrieve a routes.ini file containing routes
	 * @return Zend_Config_Ini
	 */
	protected function _getRoutesConfig() {
		$options = $this->getOptions();
		$root = APPLICATION_PATH . '/configs';
		$path = '/routes.ini';

		if (isset($options['routesFile']) && is_string($options['routesFile'])) {
			$path = $options['routesFile'];
		}

		// Figure out the current language
		if ($this->_localeIsEnabled()) {
			$lang = $this->_getCurrentLanguage();
			if ($lang && isset($options['routesFile'][$lang])) {
				$path = $options['routesFile'][$lang];
			}
		}
		$path = DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
		$ini = new Zend_Config_Ini($root.$path, APPLICATION_ENV);
		return $ini;
	}

	/**
	 * Fetch all possible locales from the front controller parameters.
	 * @return Array
	 */
	protected function _getPossibleLocales() {
		$bootstrap = $this->getBootstrap();
		$bootstrap->bootstrap('FrontController');
		$frontController = $bootstrap->getContainer()->frontcontroller;
		$locales = $frontController->getParam('locales');
		return $locales;
	}

	/**
 	 * Check if locale is enabled
 	 * @return Boolean
 	 */
	protected function _localeIsEnabled() {
		$options = $this->getOptions();
		return isset($options['locale']['enabled']) && $options['locale']['enabled'];
	}

	/**
 	 * Get current language from URL
 	 * @return String
 	 */
	protected function _getCurrentLanguage() {
		if (!isset($_SERVER['REQUEST_URI'])) {
			return null;
		}
		$requestUri = $_SERVER['REQUEST_URI'];
		$bits = explode('/', $requestUri);
		// remove empty values
		$bits = array_filter($bits, 'strlen');
		// reindex the array
		$bits = array_values($bits);
		$locales = $this->_getPossibleLocales();
		if (array_key_exists(0, $bits) && in_array($bits[0], $locales)) {
			return $bits[0];
		}
		return null;
	}
}
