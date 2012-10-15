<?php
/**
 * Garp_Form_Element_Url
 * @author Harmen Janssen | grrr.nl
 * @version 1
 * @package Garp
 * @subpackage Form
 */
class Garp_Form_Element_Url extends Garp_Form_Element_Text {

	public function init() {
		parent::init();

		$this->addFilter(new Garp_Filter_ForceUriScheme());
		$this->addValidator(new Garp_Validate_Url());
	}

}
