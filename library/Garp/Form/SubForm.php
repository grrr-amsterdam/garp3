<?php
/**
 * Garp_Form_SubForm
 * Note: this does not extends from Zend_Form_SubForm because I want the good 
 * stuff I provide in Garp_Form here as well.
 *
 * @author Harmen Janssen | grrr.nl
 * @version 1
 * @package Garp
 * @subpackage Form
 */
class Garp_Form_SubForm extends Garp_Form {
	protected $_addTimestampValidation = false;
	protected $_addHoneypotValidation  = false;

	/**
     * Whether or not form elements are members of an array
     * @var bool
     */
    protected $_isArray = true;


	/**
     * Initalize!
     * @return void
     */	
	public function init() {
		parent::init();

		// Reserve garp-form for parent forms
		$class = $this->getAttrib('class');
		if (!$class || 'garp-form' === $class) {
			$class = 'garp-subform';
		}
		$this->setDecorators(array('FormElements'));
		$this->addDecorator('HtmlTag', array('class' => $class));
	}


	/**
 	 * Add index to name attribute for better organisation of POST data.
	 * @param  string $type
     * @param  string $name
     * @param  array|Zend_Config $options
     * @return Zend_Form_Element
     */
    public function createElement($type, $name, $options = null) {
		$element = parent::createElement($type, $name, $options);
		return $element;
	} 
}
