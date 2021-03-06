<?php
/**
 * Garp_Util_AssetUrl
 * class description
 *
 * @package Garp_Util
 * @author  Harmen Janssen <harmen@grrr.nl>
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Util_AssetUrl implements JsonSerializable {
    /**
     * Statically stored rev-manifest json file
     *
     * @var array
     */
    protected static $_revManifest = null;

    protected $_url;

    /**
     * Create a versioned URL to a file.
     *
     * @param  string $file            The file path
     * @param  string $forcedExtension Force to use an extension, even when extension
     *                                 doesn't match or is missing.
     * @return void
     */
    public function __construct($file = null, string $forcedExtension = '') {
        $config = Zend_Registry::get('config');
        $baseUrl = $config->cdn->baseUrl;
        if (!$file) {
            $this->_url = $baseUrl;
            return;
        }

        /**
         * If using manifest, gulp-rev is used to generate versioned filenames.
         * Look 'em up in the manifest file and continue using the revisioned filename.
         */
        $file = $config->cdn->useRevManifest
            ? $this->_processRevManifest($file)
            : $this->getVersionedQuery($file);

        $extension = $forcedExtension ?: $this->_getExtension($file);
        $this->_url = $this->_cdnIsLocal($config, $extension)
            ? $file
            : $this->_prependBaseUrl($baseUrl, $file);
    }

    /**
     * Append a versioned query string to the given file path.
     * For example: main.js?v1.2.3
     *
     * Note, in the absence of a Garp_Version, microtime() will be used.
     *
     * @param  string $file
     * @return string
     */
    public function getVersionedQuery(string $file): string {
        $versionAppendix = (new Garp_Version())->__toString() ?: intval(microtime(true));
        return !empty($file) && substr($file, -1) !== '/'
            ? $file . '?' . $versionAppendix
            : $file;
    }

    protected function _cdnIsLocal(Zend_Config $config, string $extension): bool {
        $location = $config->cdn->{$extension}->location ?? '';
        return $location === 'local';
    }

    protected function _prependBaseUrl(string $baseUrl, string $file): string {
        return rtrim($baseUrl, '/') . '/' . ltrim($file, '/');
    }

    protected function _getExtension($file): string {
        if (!$file) {
            return '';
        }

        // Strip appended query string
        if (false !== strpos($file, '?')) {
            $file = substr($file, 0, strpos($file, '?'));
        }

        $fileParts = explode('.', $file);
        $lastPart = $fileParts[sizeof($fileParts) - 1];

        return $lastPart;
    }

    protected function _processRevManifest($file): string {
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

    /**
     * Read rev-manifest file, generated by a process like gulp-rev.
     * It maps original filenames to hashes ones.
     * Note that it is cached statically to be saved during the runtime of the script.
     *
     * @return string
     */
    public function getRevManifest(): array {
        if (is_null(self::$_revManifest)) {
            $manifestPath = APPLICATION_PATH . '/../rev-manifest-' . APPLICATION_ENV . '.json';
            self::$_revManifest = file_exists($manifestPath)
                ? json_decode(file_get_contents($manifestPath), true)
                : [];
        }
        return self::$_revManifest;
    }

    public function __toString() {
        return strval($this->_url);
    }

    public function jsonSerialize() {
        return $this->__toString();
    }
}

