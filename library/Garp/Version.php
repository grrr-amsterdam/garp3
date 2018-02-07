<?php
/**
 * Garp_Version
 * Reads from the VERSION file if present.
 *
 * Note that versions are kept as git tags. This information will not be duplicated on disk except
 * as part of the deploy process. It will not be committed to version control.
 *
 * @package Garp
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Version {

    const VERSION_FILENAME = 'VERSION';

    protected static $_cached = '';

    protected $_path;

    public static function bustCache() {
        static::$_cached = '';
    }

    public function __construct($path = null) {
        $this->_path = $path ?: $this->_getDefaultVersionLocation();
    }

    public function setFromGitTags(string $branch) {
        static::$_cached = `git describe {$branch} --tags`;
    }

    public function getVersion(): string {
        if (static::$_cached) {
            return static::$_cached;
        }
        if (file_exists($this->_path)) {
            static::$_cached = file_get_contents($this->_path);
        }
        return static::$_cached;
    }

    public function __toString(): string {
        return $this->getVersion();
    }

    protected function _getDefaultVersionLocation(): string {
        return APPLICATION_PATH . '/../' . static::VERSION_FILENAME;
    }

}

