<?php
/**
 * Garp_Semver
 * Wrapper around semver. For now it's read-only, and cannot yet compare versions.
 *
 * @package Garp
 * @author  Harmen Janssen <harmen@grrr.nl>
 * @see https://github.com/flazz/semver
 */
class Garp_Semver {
    protected static $_cached = null;

    protected $_path;

    public static function bustCache() {
        static::$_cached = null;
    }

    public function __construct($path = null) {
        $this->_path = $path ?: $this->_getDefaultSemverLocation();
    }

    /**
     * Get the current version stored in the `.semver` file.
     *
     * @return string
     */
    public function getVersion() {
        if (static::$_cached) {
            return static::$_cached;
        }
        try {
            $conf = new Zend_Config_Yaml($this->_path);
        } catch (Zend_Config_Exception $e) {
            return 'v0.0.0';
        }
        $special = '';
        if (!$this->_specialIsEmpty($conf)) {
            $special = '-' . $conf->special;
        }
        $version = "v{$conf->major}.{$conf->minor}.{$conf->patch}{$special}";
        static::$_cached = $version;
        return $version;
    }

    public function __toString() {
        return $this->getVersion();
    }

    protected function _getDefaultSemverLocation() {
        return APPLICATION_PATH . '/../.semver';
    }

    protected function _specialIsEmpty(Zend_Config_Yaml $conf) {
        return !$conf->special || $conf->special === "''" || $conf->special === "'";
    }
}

