<?php
/**
 * Garp_Form_Element_Checkbox
 * class description
 * @author Harmen Janssen | grrr.nl
 * @version 1
 * @package Garp
 * @subpackage Form
 */
class Garp_Form_Element_Checkbox extends Zend_Form_Element_Checkbox {

	public function init() {
		$this->setUncheckedValue('');
	}

}
