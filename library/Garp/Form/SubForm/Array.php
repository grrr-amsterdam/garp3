<?php
/**
 * Garp_Form_SubForm_Array
 * This subform type arranges fields in arrays, by creating an even deeper 
 * nested subform per array index.
 * The whole bunch can be duplicated in its entirety, using scripts found in 
 * garp.front.js. Garp_Form_Subform_Array::isValid() makes sure elements added
 * dynamically are added back upon submit.
 * @author Harmen Janssen | grrr.nl
 * @version 1
 * @package Garp
 * @subpackage Form
 */
class Garp_Form_SubForm_Array extends Garp_Form_SubForm {
	/**
 	 * Wether this array is duplicatable
 	 * @var Boolean
 	 */
 	protected $_duplicatable = false;


	/**
 	 * Duplicatable options
 	 * @var Array
 	 */
	protected $_duplicatableOptions = array();


	/**
 	 * Initialize!
 	 * @return Void
 	 */
	public function init() {
		parent::init();

		if ($duplicatableOptions = $this->getAttrib('duplicatable')) {
			$this->_duplicatable = true;
			$this->_duplicatableOptions = is_array($duplicatableOptions) ? $duplicatableOptions : array();
		}
		$this->setAttrib('duplicatable', null);
	}


	/**
 	 * Wether this subForm is duplicatable
 	 * @return Boolean
 	 */
	public function isDuplicatable() {
		return $this->_duplicatable;
	}


	/**
 	 * Add element to the form.
 	 * Make sure the element ends up in the right subform, as per 
 	 * $options['index'] (default = 0).
 	 * @return Zend_Form
 	 */
    public function addElement($element, $name = null, $options = null) {
		if (is_string($element)) {
			$index = isset($options['index']) ? $options['index'] : 0;
			unset($options['index']);
		} elseif ($element instanceof Zend_Form_Element) {
			$index = $element->getAttrib('index') ?: 0;
			$element->setAttrib('index', null);
		} else {
			throw new Garp_Form_Exception('Unable to resolve given element. $element must be string or Zend_Form_Element.');
		}

		if (!$subform = $this->getSubForm($index)) {
			$subform = $this->_createSubFormAtIndex($index);
			$this->addSubForm($subform);
		}
		$subform->addElement($element, $name, $options);
	}


	/**
 	 * Upon validation, we can check if there have been dynamically added input 
 	 * fields, and if so, create elements for them.
 	 * @return Boolean
 	 */
	public function isValid($data) {
        if (!is_array($data)) {
            throw new Zend_Form_Exception(__METHOD__ . ' expects an array');
        }

		$this->_incrementArray($data);
		return parent::isValid($data);
	}


	/**
     * Set default values for elements
 	 * @param Array $defaults
 	 * @return Zend_Form
	 */
    public function setDefaults(array $defaults) {	
		$this->_incrementArray($defaults);
		return parent::setDefaults($defaults);
	}


	/**
 	 * Increment the array of subforms until it matches the count given in $data
 	 * @param Array $data Data that populates the form
 	 * @return Void
 	 */
	protected function _incrementArray($data) {
		// Sanity check: if there is no subform at index 0, there is nothing for 
		// us to duplicate
		if (!$this->getSubForm('0')) {
			return;
		}

		if (!empty($data[$this->getName()]) && is_array($data[$this->getName()])) {
			foreach ($data[$this->getName()] as $key => $value) {
				if (!$subform = $this->getSubForm($key)) {
					// This subform was added dynamically
					$subform = clone $this->getSubForm('0');
					$subform->setName($key);
					// Do not print duplicatable attributes on subsequent forms
					$class = $subform->getDecorator('HtmlTag')->getOption('class');
					$class = str_replace('duplicatable', '', $class);
					$subform->getDecorator('HtmlTag')->setOption('class', $class);
					$this->addSubForm($subform);
				}
			}
		}
	}


	/**
 	 * Create a new subform at a given index
 	 * @param Int $index
 	 * @return Garp_SubForm
 	 */
	protected function _createSubFormAtIndex($index) {
		$class = 'garp-subform';
		if ($this->isDuplicatable()) {
			$class .= ' duplicatable';
		}
		$subform = new Garp_Form_SubForm(array(
			'name' => (string)$index,
			'class' => $class
		));
		if (is_array($this->_duplicatableOptions)) {
			if (!empty($this->_duplicatableOptions['buttonClass'])) {
				$subform->getDecorator('HtmlTag')->setOption('data-button-class', $this->_duplicatableOptions['buttonClass']);
			}
			if (!empty($this->_duplicatableOptions['buttonAddClass'])) {
				$subform->getDecorator('HtmlTag')->setOption('data-button-add-class', $this->_duplicatableOptions['buttonAddClass']);
			}
			if (!empty($this->_duplicatableOptions['buttonRemoveClass'])) {
				$subform->getDecorator('HtmlTag')->setOption('data-button-remove-class', $this->_duplicatableOptions['buttonRemoveClass']);
			}
			if (!empty($this->_duplicatableOptions['buttonAddLabel'])) {
				$subform->getDecorator('HtmlTag')->setOption('data-button-add-text', $this->_duplicatableOptions['buttonAddLabel']);
			}
			if (!empty($this->_duplicatableOptions['buttonRemoveLabel'])) {
				$subform->getDecorator('HtmlTag')->setOption('data-button-remove-text', $this->_duplicatableOptions['buttonRemoveLabel']);
			}
			if (!empty($this->_duplicatableOptions['skipElements'])) {
				$subform->getDecorator('HtmlTag')->setOption('data-skip-elements', $this->_duplicatableOptions['skipElements']);
			}
			if (!empty($this->_duplicatableOptions['afterDuplicate'])) {
				$subform->getDecorator('HtmlTag')->setOption('data-after-duplicate', $this->_duplicatableOptions['afterDuplicate']);
			}
		}
		return $subform;
	}	
}
