<?php
/**
 * G_View_Helper_AssetUrl
 * Generate URLs for assets (CSS, Javascript, Images, Flash)
 * @author Harmen Janssen, David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Helper
 * @lastmodified $Date: $
 */
class G_View_Helper_AssetUrl extends Zend_View_Helper_BaseUrl {
	protected $_useSemver = false;

	/**
	 * Create a versioned URL to a file
	 * @param String $file The file path
	 * @return String
	 */
	public function assetUrl($file = null) {
		$ini = Zend_Registry::get('config');
		$this->_useSemver = isset($ini->cdn->useVersionedPaths) && $ini->cdn->useVersionedPaths;

		// For backwards compatibility: deprecated param assetType
		if ($ini->cdn->assetType) {
			return $this->_getUrl($file, $ini->cdn->assetType, $ini->cdn->domain);
		}

		$extension = $this->_getExtension($file);
		if (!empty($ini->cdn->{$extension}->location)) {
			return $this->_getUrl($file, $ini->cdn->{$extension}->location, $ini->cdn->domain);
		}

		return $this->_getUrl($file, $ini->cdn->type, $ini->cdn->domain);
	}

	protected function _getExtension($file) {
		if (!$file) {
			return;
		}

		$fileParts = explode('.', $file);
		$lastPart = $fileParts[sizeof($fileParts) - 1];

		return $lastPart;
	}

	protected function _getUrl($file, $cdnType, $domain) {
		switch ($cdnType) {
			case 's3':
				return $this->_getS3Url($file, $domain);
			break;
			case 'local':
				return $this->_getLocalUrl($file);
			break;
			default:
				throw new Exception("Unknown CDN specified.");
		}		
	}


	protected function _getS3Url($file, $domain) {
		return 'http://' . $domain . $file;
	}
	

	protected function _getLocalUrl($file) {
		$baseUrl = $this->getBaseUrl();
		$baseUrl = '/' . ltrim($baseUrl, '/\\');

		$front = Zend_Controller_Front::getInstance();
		$requestParams = $front->getRequest()->getParams();

		// for assets, chop the locale part of the URL.
		if (array_key_exists('locale', $requestParams) && $requestParams['locale'] && 
			preg_match('~^/('.$requestParams['locale'].')~', $baseUrl)) {
			$baseUrl = preg_replace('~^/('.$requestParams['locale'].')~', '/', $baseUrl);
		}

		// Remove trailing slashes
		if (null !== $file) {
			$file = '/' . ltrim($file, '/\\');
		}

		$version = '';
		if ($this->_useSemver) {
			$version = (string)new Garp_Semver;
		} 
		return rtrim($baseUrl, '/') . '/' . $version . $file;
	}
}
