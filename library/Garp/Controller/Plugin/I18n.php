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
			$locale->setLocale($tldLocales[$tld]);
		} elseif (isset($params['locale'])) {
			// There is a locale specified in the request params.
			$locale->setLocale($params['locale']);
		} elseif ($locale->getDefault()) {
			// Why is it necessary to set the current language to the default language?
			// @todo Investigate
			$defaults = array_keys($locale->getDefault());
			$locale->setLocale(current($defaults));
		}

		// Now that our locale is set, let's check which language has been selected
		// and try to load a translation file for it.
		$language = $locale->getLanguage();
		$translate = $this->_getTranslate($locale);
		Zend_Registry::set('Zend_Translate', $translate);
		Zend_Form::setDefaultTranslator($translate);
		
		$path = '/' . ltrim($request->getPathInfo(), '/\\');
		
		// If the language is in the path, then we will want to set the baseUrl
		// to the specified language.
		$langIsInUrl = preg_match('/^\/' . $language . '\/?/', $path);
		$uiDefaultLangIsInUrl = false;
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
				->setHttpResponseCode(301)
				->setRedirect($redirectUrl)
			;
		}
    }


	/**
	 * Create a Zend_Translate instance for the given locale.
	 * @param Zend_Locale $locale
	 * @return Zend_Translate
	 */
	protected function _getTranslate(Zend_Locale $locale) {
		$adapterParams = array(
			'locale' => $locale,
			'disableNotices' => true,
			'scan' => Zend_Translate::LOCALE_FILENAME,
			// Argh: the 'content' key is necessary in order to load the actual data, 
			// even when using an adapter that ignores it.
			'content' => '!' 
		);

		// Figure out which adapter to use
		$translateAdapter = 'array';
		$config = Zend_Registry::get('config');
		if (!empty($config->resources->locale->translate->adapter)) {
			$translateAdapter = $config->resources->locale->translate->adapter;
		}
		$adapterParams['adapter'] = $translateAdapter;

		// Some additional configuration for the array adapter
		if ($translateAdapter == 'array') {
			$language = $locale->getLanguage();
			// @todo Move this to applciation.ini?
			$adapterParams['content'] = APPLICATION_PATH.'/data/i18n/'.$language.'.php';

			// Turn on caching
			if (Zend_Registry::isRegistered('CacheFrontend')) {
				$adapterParams['cache'] = Zend_Registry::get('CacheFrontend');
			}
			
		}
		
		$translate = new Zend_Translate($adapterParams);
		return $translate;
	}
}
