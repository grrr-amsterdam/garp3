<?php
/**
 * Garp_Form_Element_Date
 *
 * Note that the HTML5 'date' input type is NOT used.
 * It's not reliable across browsers and would require tons of 
 * extra work to make work consistently on both the front- and the backend.
 *
 * We just render a text input and add datepickerfunctionality in 
 * Garp's frontend form implementation, relying on jQuery UI.
 *
 * @author Harmen Janssen | grrr.nl
 * @version 1
 * @package Garp
 * @subpackage Form
 */
class Garp_Form_Element_Date extends Garp_Form_Element_Text {

	const DEFAULT_DATE_FORMAT = 'd-m-Y';

	public function init() {
		$class = $this->getAttrib('class');
		$class .= ($class ? ' ' : '') . 'date';
		$this->setAttrib('class', $class);

		if (!$this->getAttrib('data-format')) {
			$this->setAttrib('data-format', self::DEFAULT_DATE_FORMAT);
		}

		// Add server validation/filtering
		$this->addValidator(new Garp_Validate_Date($this->getAttrib('data-format')));
	}

}
