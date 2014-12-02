<?php
/**
 * Garp_Form_Element_Honeypot
 * Used to counter spammers
 * @author Harmen Janssen | grrr.nl
 * @version 1
 * @package Garp
 * @subpackage Form
 */
class Garp_Form_Element_Honeypot extends Zend_Form_Element {

	public function init() {
		$this->setAttrib('tabindex', '-1');
		$this->setLabel('Please leave the following field blank');
		$this->getDecorator('HtmlTag')->setOption('class', 'hp');
		$this->addValidator('StringLength', false, array('max' => 0));
	}

}
