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
	protected $_revManifest;

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
		// If using manifest, gulp-rev is used to generate versioned filenames.
		// Look 'em up in the manifest file
		if ($ini->cdn->useRevManifest) {
			$file = $this->_processRevManifest($file);
		// If only basename is given, we assume "modern" approach.
		// AssetUrl will:
		// - prepend assets.<extension>.root to the file
		// - add the current semver to the path
		} else if (strpos($file, '/') === false) {
			$file = $this->getVersionedBuildPath($file);

		// Else we will use the old (but actually more "modern") approach.
		// AssetUrl will:
		// - append semver as query string (main.js?v0.0.1)
		} else if (!empty($file) && substr($file, -1) !== '/') {
			$file = $this->getVersionedQuery($file);
		}

		// For backwards compatibility: deprecated param assetType
		if ($ini->cdn->assetType) {
			return $this->_getUrl($file, $ini->cdn->assetType,
				$this->_getAssetDomain($ini, $ini->cdn->assetType), $ini->cdn->ssl);
		}

		$extension = $forced_extension ? $forced_extension : $this->_getExtension($file);
		if (!empty($ini->cdn->{$extension}->location)) {
			return $this->_getUrl($file, $ini->cdn->{$extension}->location,
				$this->_getAssetDomain($ini, $ini->cdn->{$extension}->location), $ini->cdn->ssl);
		}

		return $this->_getUrl($file, $ini->cdn->type,
			$this->_getAssetDomain($ini, $ini->cdn->type), $ini->cdn->ssl);
	}

	protected function _getAssetDomain(Zend_Config $ini, $cdnType) {
		if ($cdnType === 's3' && $ini->cdn->ssl && $ini->cdn->s3->region) {
			// Technically not a domain since there's a path containing the bucket
			return 's3-' . $ini->cdn->s3->region . '.amazonaws.com/' . $ini->cdn->s3->bucket;
		}
		return $ini->cdn->domain;
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

	protected function _getUrl($file, $cdnType, $domain, $ssl) {
		switch ($cdnType) {
			case 's3':
				return $this->_getS3Url($file, $domain, $ssl);
			break;
			case 'local':
				return $this->_getLocalUrl($file);
			break;
			default:
				throw new Exception("Unknown CDN specified.");
		}
	}


	protected function _getS3Url($file, $domain, $ssl) {
		return 'http' . ($ssl ? 's' : '') . '://' . $domain . $file;
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

	protected function _processRevManifest($file) {
		$base = basename($file);
		$manifest = $this->getRevManifest();
		if (!$manifest) {
			throw new Exception('There is no manifest file for environment ' . APPLICATION_ENV);
		}

		if (array_key_exists($base, $manifest)) {
			$base = $manifest[$base];
		}
		return dirname($file) . DIRECTORY_SEPARATOR . $base;
	}

	public function getRevManifest() {
		if (!$this->_revManifest) {
			$manifestPath = APPLICATION_PATH . '/../rev-manifest-' . 'staging'. '.json';
			$this->_revManifest = json_decode(@file_get_contents($manifestPath), true);
		}
		return $this->_revManifest;
	}
}
