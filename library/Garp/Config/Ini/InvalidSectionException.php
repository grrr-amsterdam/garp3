<?php
class Garp_Config_Ini_InvalidSectionException extends Zend_Config_Exception {
    protected $_validSections;

    public function setValidSections(array $sections) {
        $this->_validSections = $sections;
    }

    public function getValidSections() {
        return $this->_validSections;
    }
}
