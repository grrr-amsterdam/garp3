<?php
/**
 * G_View_Helper_I18n
 * I18n helper functions
 *
 * @package G_View_Helper
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class G_View_Helper_I18n extends Zend_View_Helper_Abstract {

    /**
     * Chain method.
     *
     * @return G_View_Helper_I18n
     */
    public function i18n() {
        return $this;
    }

    /**
     * Get a route in a different language.
     *
     * @param string $language
     * @param array $routeParams  Parameters used in assembling the alternate route
     * @param string $route       Which route to use. Defaults to current route.
     * @param bool $defaultToHome Wether to use home as a fallback alt route
     * @return string
     */
    public function getAlternateUrl(
        $language, array $routeParams = array(), $route = null, $defaultToHome = true
    ) {
        if (!$route) {
            $router = Zend_Controller_Front::getInstance()->getRouter();
            $route  = $router->getCurrentRouteName();
        }
        if (empty($this->view->config()->resources->router->routesFile->{$language})) {
            return null;
        }
        $routes = $this->_getRoutesWithFallback($language);
        $localizedRoutes = Garp_I18n::getLocalizedRoutes($routes, array($language));

        $router = new Zend_Controller_Router_Rewrite();
        $router->addConfig(new Zend_Config($localizedRoutes));
        $routeParams['locale'] = $language;

        // @todo Also add existing GET params in the form of ?foo=bar
        try {
            $alternateRoute = $router->assemble($routeParams, $route);
        } catch (Exception $e) {
            // try to default to 'home'
            if (!$defaultToHome) {
                throw $e;
            }
            return $this->_constructHomeFallbackUrl($language);
        }
        // Remove the baseURl because it contains the current language
        $alternateRoute = $this->view->string()->strReplaceOnce(
            $this->view->baseUrl(), '', $alternateRoute
        );

        // Always use explicit localization
        if ($alternateRoute == '/') {
            $alternateRoute = '/' . $language;
        }
        return $alternateRoute;
    }

    /**
     * Maps methods to Garp_Util_String
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args) {
        return call_user_func_array(array('Garp_I18n', $method), $args);
    }

    /**
     * Crude fallback for routes that are not found in the alternate language
     * This assumes your homepage exists at "/<language>".
     * Probably good to refactor at some point when this becomes a problem.
     *
     * @param string $altLang
     * @return string
     */
    protected function _constructHomeFallbackUrl($altLang) {
        // Default to the homepage
        $homeUrl = $this->view->url(array(), 'home');
        // Strip the baseUrl from the current url, because that contains the current language.
        $baseUrl = $this->view->baseUrl();
        $homeUrl = str_replace($baseUrl, '', $homeUrl);
        $alternateUrl = '/' . $altLang . $homeUrl;
        return $alternateUrl;
    }

    /**
     * Return generic routes if routes file from language is empty
     * 
     * @param string $language
     * @return array
     */
    protected function _getRoutesWithFallback($language) {
        $routesFile = $this->view->config()->resources->router->routesFile->{$language};
        $config = new Garp_Config_Ini($routesFile, APPLICATION_ENV);
        
        if ($config->routes) {
            return $config->routes->toArray();
        } 
        
        $routesFile = $this->view->config()->resources->router->routesFile->generic;
        $config = new Garp_Config_Ini($routesFile, APPLICATION_ENV);
        return $config->routes->toArray();
    }
}
