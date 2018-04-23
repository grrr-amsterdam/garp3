<?php
/**
 * Garp_Form_Element_Honeypot
 * Used to counter spammers
 *
 * @package Garp_Form_Element
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Form_Element_Honeypot extends Zend_Form_Element {

    public function init() {
        $this->setAttrib('tabindex', '-1');
        $this->setLabel('Please leave the following field blank');
        if ($this->getDecorator('HtmlTag')) {
            $this->getDecorator('HtmlTag')->setOption('class', 'hp');
        }
        $this->addValidator('StringLength', false, ['max' => 0]);
    }

}
