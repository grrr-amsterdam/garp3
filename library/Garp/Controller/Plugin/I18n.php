<?php
/**
 * Garp_Controller_Plugin_I18n
 * This plugin loads the correct Zend_Translate for the current locale.
 * @author Joe Gornick | joegornick.com
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
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
		
		if (array_key_exists($tld, $tldLocales)) {
			// The TLD in the request matches one of our specified TLD -> Locales
			$locale->setLocale($tldLocales[$tld]);
		} elseif (isset($params['locale'])) {
			// There is a locale specified in the request params.
			$locale->setLocale($params['locale']);
		}
		
		// Now that our locale is set, let's check which language has been selected
		// and try to load a translation file for it. If the language is the default,
		// then we do not need to load a translation.
		$language = $locale->getLanguage();
		if ($language !== $locale->getDefault()) {
			$i18nFile = APPLICATION_PATH.'/data/i18n/'.$language.'.php';
			try {
				$translate = new Zend_Translate('array', $i18nFile, $locale, array('disableNotices' => true));
				Zend_Registry::set('Zend_Translate', $translate);
				Zend_Form::setDefaultTranslator($translate);
			} catch (Zend_Translate_Exception $e) {
				// Since there was an error when trying to load the translation catalog,
				// let's not load a translation object which essentially defaults to
				// locale default.
			}
		}
		
		// Now that we have our locale setup, let's check to see if we are loading
		// a language that is not the default, and update our base URL on the front
		// controller to the specified language.
		$defaultLanguage = array_keys($locale->getDefault());
		$defaultLanguage = $defaultLanguage[0];
		
		$path = '/' . ltrim($request->getPathInfo(), '/\\');
		
		// If the language is in the path, then we will want to set the baseUrl
		// to the specified language.
		if (preg_match('/^\/' . $language . '\/?/', $path)) {
			$frontController->setBaseUrl($frontController->getBaseUrl() . '/' . $language);
		}
		
		if (!headers_sent()) {
			setcookie('Zend_Locale', $language, null, '/', $request->getHttpHost());
		}
    }
}
