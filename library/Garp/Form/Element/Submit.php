<?php
/**
 * Garp_Form_Element_Submit
 * Overwritten to remove Decorators. Buttons don't need decorators.
 * @author Harmen Janssen | grrr.nl
 * @version 1
 * @package Garp
 * @subpackage Form
 */
class Garp_Form_Element_Submit extends Zend_Form_Element_Submit {

	public function init() {
		// Don't be silly. Buttons don't use labels.
		$this->removeDecorator('Label');
	}

}
