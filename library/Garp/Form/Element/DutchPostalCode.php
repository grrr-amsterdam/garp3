<?php
/**
 * Garp_Form_Element_DutchPostalCode
 * Represents input for a Dutch postal code.
 *
 * @package Garp_Form_Element
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Form_Element_DutchPostalCode extends Zend_Form_Element_Text {

    public function init() {
        $currClass = $this->getAttrib('class');
        if (!is_array($currClass)) {
            $currClass = (array)$currClass;
        }
        if (!in_array('dutch-postal-code', $currClass)) {
            $currClass[] = ' dutch-postal-code';
        }
        $this->setAttrib('class', implode(' ', $currClass));

        $this->addFilter(new Garp_Filter_DutchPostalCode());
        $this->addValidator(new Garp_Validate_DutchPostalCode());
    }

}
