<?php
/**
 * Garp_Config_Ini_InvalidSectionException
 * Thrown when a section is requested which is not present in the ini file.
 *
 * @package Garp_Config_Ini
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Config_Ini_InvalidSectionException extends Zend_Config_Exception {
    protected $_validSections;

    public function setValidSections(array $sections) {
        $this->_validSections = $sections;
    }

    public function getValidSections() {
        return $this->_validSections;
    }
}
