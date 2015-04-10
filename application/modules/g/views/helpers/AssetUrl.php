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
	 * @param String $forced_extension Force to use an extension, even when extension doesn't match or is missing (eg. '/' in 'ASSET_URL' when using a cdn, but 'js' is 'local')
	 * @return String
	 */
	public function assetUrl($file = null, $forced_extension = false) {
		if (is_null($file)) {
			return $this;
		}

		$ini = Zend_Registry::get('config');
		// If only basename is given, we assume "modern" approach.
		// AssetUrl will:
		// - prepend assets.<extension>.root to the file
		// - add the current semver to the path
		if (strpos($file, '/') === false) {
			$file = $this->getVersionedBuildPath($file);

		// Else we will use the old (but actually more "modern") approach.
		// AssetUrl will:
		// - append semver as query string (main.js?v0.0.1)
		} else if (!empty($file) && substr($file, -1) !== '/') {
			$file = $this->getVersionedQuery($file);
		}

		// For backwards compatibility: deprecated param assetType
		if ($ini->cdn->assetType) {
			return $this->_getUrl($file, $ini->cdn->assetType, $ini->cdn->domain);
		}

		$extension = $forced_extension ? $forced_extension : $this->_getExtension($file);
		if (!empty($ini->cdn->{$extension}->location)) {
			return $this->_getUrl($file, $ini->cdn->{$extension}->location, $ini->cdn->domain);
		}

		return $this->_getUrl($file, $ini->cdn->type, $ini->cdn->domain);
	}

	protected function _getExtension($file) {
		if (!$file) {
			return;
		}

		// Strip appended query string
		if (false !== strpos($file, '?')) {
			$file = substr($file, 0, strpos($file, '?'));
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
		$requestParams = array();
		if ($front->getRequest()) {
			$requestParams = $front->getRequest()->getParams();
		}

		// for assets, chop the locale part of the URL.
		if (array_key_exists('locale', $requestParams) && $requestParams['locale'] &&
			preg_match('~^/('.$requestParams['locale'].')~', $baseUrl)) {
			$baseUrl = preg_replace('~^/('.$requestParams['locale'].')~', '/', $baseUrl);
		}

		// Remove trailing slashes
		if (null !== $file) {
			$file = ltrim($file, '/\\');
		}

		return rtrim($baseUrl, '/') . '/' . $file;
	}

	public function getVersionedQuery($file) {
		return $file . '?' . new Garp_Semver();
	}

	public function getVersionedBuildPath($file) {
		if (!isset(Zend_Registry::get('config')->assets->{$this->_getExtension($file)}->root)) {
			return $file;
		}
		return rtrim(Zend_Registry::get('config')->assets->{$this->_getExtension($file)}->root, '/') .
			'/' . new Garp_Semver() . '/' . $file;
	}
}
