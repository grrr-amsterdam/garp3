<?php
/**
 * Garp_Form_Element_Email
 * @author Harmen Janssen | grrr.nl
 * @version 1
 * @package Garp
 * @subpackage Form
 */
class Garp_Form_Element_Email extends Garp_Form_Element_Text {

	public function init() {
		// Allowing the HostName validator in here is probably overkill. Just checking the syntax is enough for now.
		$this->addValidator('EmailAddress', false, array('domain' => false));
	}

}
