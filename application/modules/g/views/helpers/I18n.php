<?php
/**
 * G_View_Helper_I18n
 * I18n helper functions
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      G_View_Helper
 */
class G_View_Helper_I18n extends Zend_View_Helper_Abstract {
	/**
	 * Chain method.
	 * @return G_View_Helper_String 
	 */
	public function i18n() {
		return $this;
	}

	/**
 	 * Get a route in a different language.
 	 * @param String $language
 	 * @param Array $routeParams Parameters used in assembling the alternate route
 	 * @param String $route Which route to use. Defaults to current route.
 	 * @return String
 	 */
	public function getAlternateUrl($language, array $routeParams = array(), $route = null) {
		if (!$route) {
			$router = Zend_Controller_Front::getInstance()->getRouter();
			$route = $router->getCurrentRouteName();
		}
		if (empty($this->view->config()->resources->router->routesFile->{$language})) {
			return null;
		}
		$routesFile = $this->view->config()->resources->router->routesFile->{$language};
		$config = new Garp_Config_Ini($routesFile, APPLICATION_ENV);
		$routes = $config->routes->toArray();
		$localizedRoutes = Garp_I18n::getLocalizedRoutes($routes, array($language));

		$router = new Zend_Controller_Router_Rewrite();
		$router->addConfig(new Zend_Config($localizedRoutes));
		$routeParams['locale'] = $language;

		// @todo Also add existing GET params in the form of ?foo=bar
		try {
			$alternateRoute = $router->assemble($routeParams, $route);
		} catch (Exception $e) {
			return null;
		}
		// Remove the baseURl because it contains the current language
		$alternateRoute = str_replace($this->view->baseUrl(), '', $alternateRoute);

		// Always use explicit localization
		if ($alternateRoute == '/') {
			$alternateRoute = '/' . $language;
		}
		return $alternateRoute;
	}

	/**
	 * Maps methods to Garp_Util_String
	 * @param String $method
	 * @param Array $args
	 * @return Mixed
	 */
	public function __call($method, $args) {
		return call_user_func_array(array('Garp_I18n', $method), $args);
	}
}
