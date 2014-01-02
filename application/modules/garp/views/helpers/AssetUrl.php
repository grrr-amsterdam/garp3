<?php
/**
 * G_View_Helper_AssetUrl
 * Generate URLs for assets (CSS, Javascript, Images, Flash)
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Helper
 * @lastmodified $Date: $
 */
class G_View_Helper_AssetUrl extends Zend_View_Helper_BaseUrl {	
	/**
	 * Create a versioned URL to a file
	 * @param String $file The file path
	 * @return String
	 */
	public function assetUrl($file = null) {
		$ini = Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/application.ini');
		switch ($ini->cdn->type) {
			case 's3':
				return 'http://'.$ini->cdn->domain.$file;
			break;
			case 'local':
				$baseUrl = $this->getBaseUrl();
				$baseUrl = '/'.ltrim($baseUrl, '/\\');

				// $ini = Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/application.ini', APPLICATION_ENV);
				// $assetVersion = $ini->app->assetVersion;

				$front = Zend_Controller_Front::getInstance();
				$requestParams = $front->getRequest()->getParams();

				// for assets, chop the locale part of the URL.
				if (array_key_exists('locale', $requestParams) && $requestParams['locale'] && preg_match('~^/('.$requestParams['locale'].')~', $baseUrl)) {
					$baseUrl = preg_replace('~^/('.$requestParams['locale'].')~', '/', $baseUrl);
				}

				// Remove trailing slashes
				if (null !== $file) {
					$file = '/'.ltrim($file, '/\\');
				}

				$version = defined('APP_VERSION') ? APP_VERSION : null;
				if (!$version) {
					require_once APPLICATION_PATH.'/modules/g/views/helpers/Exception.php';
					throw new G_View_Helper_Exception('APP_VERSION is not set.');
				}
				return rtrim($baseUrl, '/').'/v'.$version.$file;
			break;
			default:
				throw new Exception("Unknown CDN specified.");
		}
	}
}
