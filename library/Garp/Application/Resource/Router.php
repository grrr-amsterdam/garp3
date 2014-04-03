<?php
/**
 * Garp_Application_Resource_Router
 * Handles internationalized routes
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
	 * @var Zend_Application_Resource_Frontcontroller
	 */
	protected $_front;

	/**
	 * @var Zend_Locale
	 */
	protected $_locale;
 
	/**
	 * Retrieve router object
	 * @return Zend_Controller_Router_Rewrite
	 */
	public function getRouter() {
		$ini = new Zend_Config_Ini(APPLICATION_PATH.'/configs/routes.ini', APPLICATION_ENV);
		$this->setOptions($ini->toArray());
		$options = $this->getOptions();
		
		if (!isset($options['locale']['enabled']) ||
			!$options['locale']['enabled']) {
			return parent::getRouter();
		}

		$bootstrap = $this->getBootstrap();
 		
		if (!$this->_front) {
			$bootstrap->bootstrap('FrontController');
			$this->_front = $bootstrap->getContainer()->frontcontroller;
		}
 
		if (!$this->_locale) {
			$bootstrap->bootstrap('Locale');
			$this->_locale = $bootstrap->getContainer()->locale;
		}
 
		$defaultLocale = array_keys($this->_locale->getDefault());
		$defaultLocale = $defaultLocale[0];
 
		$locales = $this->_front->getParam('locales');
		$requiredLocalesRegex = '^('.join('|', $locales).')$';
 
		$routes = $options['routes'];
		foreach ($routes as $key => $value) {
			// First let's add the default locale to this routes defaults.
			$defaults = isset($value['defaults'])
				? $value['defaults']
				: array();
			
			// Always default all routes to the Zend_Locale default
			$value['defaults'] = array_merge(array('locale' => $defaultLocale ), $defaults);
 			
			$routes[$key] = $value;
 			
			// Get our route and make sure to remove the first forward slash
			// since it's not needed.
			$routeString = $value['route'];
			$routeString = ltrim($routeString, '/\\');
			
			// Modify our normal route to have the locale parameter.
			if (!isset($value['type']) || $value['type'] === 'Zend_Controller_Router_Route') {
				$value['route'] = ':locale/'.$routeString;
				$value['reqs']['locale'] = $requiredLocalesRegex;
				$routes['locale_'.$key] = $value;
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
 				
				$routes['locale_'.$key] = $value;
			} elseif ($value['type'] === 'Zend_Controller_Router_Route_Static') {
				foreach ($locales as $locale) {
					$value['route'] = $locale.'/'.$routeString;
					$value['defaults']['locale'] = $locale;
					$routes['locale_'.$locale.'_'.$key] = $value;
				}
			}
		}

		$options['routes'] = $routes;
		$this->setOptions($options);		
		return parent::getRouter();
	}
}