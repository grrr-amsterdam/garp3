<?php
/**
 * Garp_Controller_Plugin_I18n
 * This plugin loads the correct Zend_Translate for the current locale.
 * @author Joe Gornick | joegornick.com
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Controller
 * @lastmodified $Date: $
 */
class Garp_Controller_Plugin_I18n extends Zend_Controller_Plugin_Abstract {
    /**
     * Sets the application locale and translation based on the locale param, if
     * one is not provided it defaults to english
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return Void
     */
    public function routeShutdown(Zend_Controller_Request_Abstract $request) {
        $config = Zend_Registry::get('config');
        $frontController = Zend_Controller_Front::getInstance();
        $params = $request->getParams();
        $registry = Zend_Registry::getInstance();
        // Steps setting the locale.
        // 1. Default language is set in config
        // 2. TLD in host header
        // 3. Locale params specified in request
        $locale = $registry->get('Zend_Locale');
        // Check host header TLD.
        $tld = preg_replace('/^.*\./', '', $request->getHeader('Host'));

        // Provide a list of tld's and their corresponding default languages
        $tldLocales = $frontController->getParam('tldLocales');
        if (is_array($tldLocales) && array_key_exists($tld, $tldLocales)) {
            // The TLD in the request matches one of our specified TLD -> Locales
            $locale->setLocale(strtolower($tldLocales[$tld]));
        } elseif (isset($params['locale'])) {
            // There is a locale specified in the request params.
            $locale->setLocale(strtolower($params['locale']));
        }
        // Now that our locale is set, let's check which language has been selected
        // and try to load a translation file for it.
        $language = $locale->getLanguage();
        $translate = Garp_I18n::getTranslateByLocale($locale);
        Zend_Registry::set('Zend_Translate', $translate);
        Zend_Form::setDefaultTranslator($translate);

        if (!$config->resources->router->locale->enabled) {
            return;
        }

        $path = '/' . ltrim($request->getPathInfo(), '/\\');

        // If the language is in the path, then we will want to set the baseUrl
        // to the specified language.
        $langIsInUrl = preg_match('/^\/' . $language . '\/?/', $path);
        $uiDefaultLangIsInUrl = false;
        $uiDefaultLanguage = false;
        if (isset($config->resources->locale->uiDefault)) {
            $uiDefaultLanguage = $config->resources->locale->uiDefault;
            $uiDefaultLangIsInUrl = preg_match('/^\/' . $uiDefaultLanguage . '\/?/', $path);
        }

        if ($langIsInUrl || $uiDefaultLangIsInUrl) {
            if ($uiDefaultLangIsInUrl) {
                $frontController->setBaseUrl($frontController->getBaseUrl() . '/' . $uiDefaultLanguage);
            } else {
                $frontController->setBaseUrl($frontController->getBaseUrl() . '/' . $language);
            }
        } elseif (!empty($config->resources->router->locale->enabled) && $config->resources->router->locale->enabled) {
            $redirectUrl = '/'.$language.$path;
            if ($frontController->getRouter()->getCurrentRouteName() === 'admin' &&
                !empty($config->resources->locale->adminDefault)) {
                $adminDefaultLanguage = $config->resources->locale->adminDefault;
                $redirectUrl = '/' . $adminDefaultLanguage . $path;
            } elseif ($uiDefaultLanguage) {
                $redirectUrl = '/'.$uiDefaultLanguage.$path;
            }
            $this->getResponse()
                ->setRedirect($redirectUrl, 301)
            ;
        }
    }

}
