<?php
/**
 * Garp_ZFTwig_GlobalVariables
 * Provide global variables to Twig templates
 *
 * @package Garp_ZFTwig
 * @author  Harmen Janssen <harmen@grrr.nl>
 * @version 0.1.0
 */
class Garp_ZFTwig_GlobalVariables extends Ano_ZFTwig_GlobalVariables {

    /**
     * In Twig templates, provides access to configuration values.
     * Example: `app.config.cdn.region`
     *
     * @return Garp_Config_Ini
     */
    public function getConfig() {
        return Zend_Registry::get('config');
    }

    /**
     * Shortcut to APPLICATION_PATH.
     * Usage: `app.applicationPath`
     *
     * @return String
     */
    public function getApplicationPath() {
        return APPLICATION_PATH;
    }

    /**
     * Access current version in Twig templates.
     * Usage: `app.version`
     *
     * @return Garp_Semver
     */
    public function getVersion() {
        return new Garp_Semver;
    }

}
