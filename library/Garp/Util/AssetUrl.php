<?php
/**
 * Garp_Util_AssetUrl
 * class description
 *
 * @package Garp_Util
 * @author  Harmen Janssen <harmen@grrr.nl>
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Util_AssetUrl {
    protected $_useSemver = false;
    protected $_revManifest;
    protected $_url;

    /**
     * Create a versioned URL to a file
     *
     * @param string $file The file path
     * @param string $forced_extension Force to use an extension, even when extension
     *                                 doesn't match or is missing
     *                                 (eg. '/' in 'ASSET_URL' when using a cdn,
     *                                 but 'js' is 'local')
     * @return void
     */
    public function __construct($file = null, $forced_extension = false) {
        if (is_null($file)) {
            return;
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
            $this->_url = $this->_getUrl(
                $file,
                $ini->cdn->assetType,
                $this->_getAssetDomain($ini, $ini->cdn->assetType),
                $ini->cdn->ssl
            );
            return;
        }

        $extension = $forced_extension ? $forced_extension : $this->_getExtension($file);
        if (!empty($ini->cdn->{$extension}->location)) {
            $location = $ini->cdn->{$extension}->location;
            $domain = $location !== 'local' ?
                $this->_getAssetDomain($ini, $ini->cdn->{$extension}->location) :
                null;
            $this->_url = $this->_getUrl($file, $location, $domain, $ini->cdn->ssl);
            return;
        }

        $this->_url = $this->_getUrl(
            $file, $ini->cdn->type,
            $this->_getAssetDomain($ini, $ini->cdn->type), $ini->cdn->ssl
        );
    }

    protected function _getAssetDomain(Zend_Config $ini, $cdnType) {
        if ($ini->cdn->domain) {
            return $ini->cdn->domain;
        }
        if ($cdnType === 's3' && $ini->cdn->s3->region) {
            // Technically not a domain since there's a path containing the bucket
            return 's3-' . $ini->cdn->s3->region . '.amazonaws.com/' . $ini->cdn->s3->bucket;
        }
        throw new Exception(
            'Unable to get asset domain. Either specify cdn.domain or configure ' .
            'cdn.s3.region to use a generic amazonaws.com domain.'
        );
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
            return $this->_getLocalUrl($file, $domain, $ssl);
            break;
        default:
            throw new Exception("Unknown CDN specified.");
        }
    }

    protected function _getS3Url($file, $domain, $ssl) {
        return 'http' . ($ssl ? 's' : '') . '://' . $domain . $file;
    }

    protected function _getLocalUrl($file, $domain, $ssl) {
        if (!strlen($file)) {
            return '';
        }

        $baseUrlHelper = new Zend_View_Helper_BaseUrl();
        $baseUrl = $baseUrlHelper->getBaseUrl();
        $baseUrl = '/' . ltrim($baseUrl, '/\\');

        $front = Zend_Controller_Front::getInstance();
        $requestParams = array();
        if ($front->getRequest()) {
            $requestParams = $front->getRequest()->getParams();
        }

        // for assets, chop the locale part of the URL.
        if (array_key_exists('locale', $requestParams) && $requestParams['locale']
            && preg_match('~^/(' . $requestParams['locale'] . ')~', $baseUrl)
        ) {
            $baseUrl = preg_replace('~^/(' . $requestParams['locale'] . ')~', '/', $baseUrl);
        }

        // Remove trailing slashes
        if (null !== $file) {
            $file = ltrim($file, '/\\');
        }

        return rtrim($baseUrl, '/') . '/' . $file;
        return 'http' . ($ssl ? 's' : '') . '://' . $domain . rtrim($baseUrl, '/') . '/' . $file;
    }

    public function getVersionedQuery($file) {
        return $file . '?' . new Garp_Semver();
    }

    public function getVersionedBuildPath($file) {
        $buildConfig = 'build';
        $assetsConfig = Zend_Registry::get('config')->assets->{$this->_getExtension($file)};
        if (empty($assetsConfig->$buildConfig)) {
            $buildConfig = 'root';
        }
        if (empty($assetsConfig->$buildConfig)) {
            return $file;
        }
        return rtrim($assetsConfig->$buildConfig, '/') .
            '/' . new Garp_Semver() . '/' . $file;
    }

    protected function _processRevManifest($file) {
        // If argument is the root and not a file, return early
        if ($file === '/' || !$file) {
            return $file;
        }

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
            $manifestPath = APPLICATION_PATH . '/../rev-manifest-' . APPLICATION_ENV . '.json';
            $this->_revManifest = json_decode(@file_get_contents($manifestPath), true);
        }
        return $this->_revManifest;
    }

    public function __toString() {
        return $this->_url;
    }
}
