<?php
/**
 * Garp_Semver
 * Wrapper around semver. For now it's read-only, and cannot yet compare versions.
 * @see https://github.com/flazz/semver
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Garp
 */
class Garp_Semver {
    protected $_path;

    public function __construct($path = null) {
        $this->_path = $path ?: $this->_getDefaultSemverLocation();
    }

    public function getVersion() {
        try {
            $conf = new Zend_Config_Yaml($this->_path);
        } catch (Zend_Config_Exception $e) {
            return 'v0.0.0';
        }
        $special = '';
        if (!$this->_specialIsEmpty($conf)) {
            $special = '-' . $conf->special;
        }
        return "v{$conf->major}.{$conf->minor}.{$conf->patch}{$special}";
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
