<?php
/**
 * Garp_Form_Element_Html
 * Render custom HTML elements in the form
 * @author Harmen Janssen | grrr.nl
 * @version 1
 * @package Garp
 * @subpackage Form
 */
class Garp_Form_Element_Html extends Zend_Form_Element {
	/**
 	 * Helper used for rendering
 	 * @var String
 	 */
	public $helper = 'html';


	public function __construct($spec, $options = null) {
		if (empty($options['id'])) {
			$options['id'] = '';
		}
		$options['ignore'] = true;
		parent::__construct($spec, $options);
		$this->clearDecorators();
		$this->setDecorators(array(
			'ViewHelper',
		));
	}


    public function isValid($value, $context = null) {
		return true;
	}

}
