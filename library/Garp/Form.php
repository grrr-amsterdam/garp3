<?php
/**
 * Garp_Form
 * class description
 * @author Harmen Janssen | grrr.nl
 * @version 1
 * @package Garp
 * @subpackage Form
 *
 * @todo Add setters and getters for _addHoneypotValidation and 
 * _addTimestampValidation
 */
class Garp_Form extends Zend_Form {
	/**
 	 * Name used for timestamp-based anti-spam field
 	 * @var String
 	 */
	const TIMESTAMP_FIELD_KEY = 'ts';


	/**
 	 * Name used for honeypot
 	 * @var String
 	 */
	const HONEYPOT_FIELD_KEY = 'hp';


	/**
 	 * Parent form.
 	 * @var Garp_Form
 	 */
	protected $_parent;


	/**
 	 * Wether to set a default suffix on labels of required elements
 	 * @var Boolean
 	 */
	protected $_defaultRequiredLabelSuffix = ' <i>*</i>';


	/**
 	 * Wether to add a honeypot
 	 * @var Boolean
 	 */
	protected $_addHoneypotValidation = true;


	/**
 	 * Wether to add timestamp validation
 	 * @var Boolean
 	 */
	protected $_addTimestampValidation = true;


	/**
 	 * Wether to hijack this form using ajax
 	 * @var Boolean
 	 */
	protected $_ajax = false; 


    /**
     * Initalize!
     * @return void
     */
	public function init() {
		$this->addPrefixPath('Garp_Form', 'Garp/Form')
		    ->addElementPrefixPath('Garp_Form', 'Garp/Form/')
            ->addDecorator('FormElements')
            ->addDecorator('Form')
			;

		if (!$this->getAttrib('class')) {
			$this->setAttrib('class', 'garp-form');
		}

		// Anti-spam fields
		if ($this->_addTimestampValidation) {
			$this->addTimestampValidation();
		}
		if ($this->_addHoneypotValidation) {
			$this->addHoneypotValidation();
		}
	}


	/**
     * Create an element
     *
     * Acts as a factory for creating elements. Elements created with this
     * method will not be attached to the form, but will contain element
     * settings as specified in the form object (including plugin loader
     * prefix paths, default decorators, etc.).
     *
     * @param  string $type
     * @param  string $name
     * @param  array|Zend_Config $options
     * @return Zend_Form_Element
     */
    public function createElement($type, $name, $options = null) {
		if ('html' == strtolower($type)) {
			// For simple HTML elements, skip all the decorator stuff below
			return new Garp_Form_Element_Html($name, $options);
		}

		$options = $options ?: array();

		// I don't like the id to be the same as $name, 'cause it's usually a tad generic.
		if (empty($options['id'])) {
			$this->_addIdAttribute($name, $options);
		}

		// Default to a sensible set of decorators
		if (empty($options['decorators'])) {
			$options['disableLoadDefaultDecorators'] = true;
			$this->_addDefaultDecorators($type, $options);
		}

		// Add the StringTrim filter by default
		if (empty($options['filters'])) {
			$options['filters'] = array('StringTrim');
		}

		$element = parent::createElement($type, $name, $options);

		// If the Identical validator is given, set a data-attribute so 
		// Javascript can validate the two fields as well
		if ($identicalValidator = $element->getValidator('Identical')) {
			$element->setAttrib('data-identical-to', $identicalValidator->getToken());
		}

		// Set HTML5 required attribute
		if ($this->_elementNeedsRequiredAttribute($element, $options)) {
			$element->setAttrib('required', 'required');
		}

		return $element;
	}


	/**
 	 * Check if an element needs required attribute
 	 * @param Zend_Form_Element $element
 	 * @param Array $options
 	 * @return Boolean
 	 */
	protected function _elementNeedsRequiredAttribute(Zend_Form_Element $element, array $options) {
		return isset($options['required']) &&
			$options['required'] &&
			!$element instanceof Zend_Form_Element_MultiCheckbox
		;
	}

	/**
     * Add a display group
     *
     * Groups named elements for display purposes.
     *
     * If a referenced element does not yet exist in the form, it is omitted.
     *
     * @param  array $elements
     * @param  string $name
     * @param  array|Zend_Config $options
     * @return Zend_Form
     * @throws Zend_Form_Exception if no valid elements provided
     */
    public function addDisplayGroup(array $elements, $name, $options = null) {
		// Allow custom decorators, but default to a sensible set
		if (empty($options['decorators'])) {
			$options['decorators'] = array(
				'FormElements',
				'Fieldset',
			);
		}			
		return parent::addDisplayGroup($elements, $name, $options);
	}


	/**
     * Add a form group/subform
     *
     * @param  Zend_Form $form
     * @param  string $name
     * @param  int $order
     * @return Zend_Form
     */
    public function addSubForm(Zend_Form $form,  $name = null, $order = null) {
		if (!$name) {
			$name = $form->getName();
		}
		$form->setParent($this);
		return parent::addSubForm($form, $name, $order);
	}


	/**
 	 * Set required label suffix
 	 * @param String $suffix
 	 * @return $this
 	 */
	public function setDefaultRequiredLabelSuffix($suffix) {
		$this->_defaultRequiredLabelSuffix = $suffix;
		return $this;
	}


	/**
 	 * Get required label suffix
 	 * @return String
 	 */
	public function getDefaultRequiredLabelSuffix() {
		return $this->_defaultRequiredLabelSuffix;
	}


	/**
 	 * Set wether to hijack the form using AJAX
 	 * @param Boolean $flag
 	 * @return $this
 	 */
	public function setAjax($flag) {
		$this->_ajax = $flag;
		$class = $this->getAttrib('class');
		if ($flag && !preg_match('/(^|\s)ajax($|\s)/', $class)) {
			$class .= ' ajax';
		} else {
			$class = preg_replace('/(^|\s)(ajax)($|\s)/', '$1$3', $class);
		}
		$this->setAttrib('class', $class);
		return $this;
	}


	/**
 	 * @return Boolean
 	 */
	public function getAjax() {
		return $this->_ajax;
	}


	/**
 	 * Add field that records how long it took to submit the form.
 	 * This should be longer than 1 second, otherwise we suspect spammy 
 	 * behavior.
 	 * @return Void
 	 */
	public function addTimestampValidation() {
		// Add timestamp-based spam counter-measure
		$this->addElement('hidden', self::TIMESTAMP_FIELD_KEY, array(
			'validators' => array(new Garp_Validate_Duration)
		));
		// If the form is submitted, do not set the value.
		if (!$this->getValue(self::TIMESTAMP_FIELD_KEY)) {
			$now = time();
			$this->getElement(self::TIMESTAMP_FIELD_KEY)->setValue($now);
		}
	}


	/**
 	 * Add field that acts as a honeypot for spambots. It should be left blank 
 	 * in order to be valid. 
 	 * Use CSS to visually hide this field from humans.
 	 * @return Void
 	 */
	public function addHoneypotValidation() {
		$this->addElement('honeypot', self::HONEYPOT_FIELD_KEY);
	}


	/**
 	 * Register a parent.
 	 * @param Garp_Form $parent
 	 * @return $this
 	 */
	public function setParent(Garp_Form $parent) {
		$this->_parent = $parent;
		return $this;
	}


	/**
 	 * Get parent
 	 * @return Garp_Form
 	 */
	public function getParent() {
		return $this->_parent;
	}


	/**
 	 * Add id attribute to the options array.
 	 * Called from Garp_Form::createElement()
 	 * @param String $name Name-attribute of the element
 	 * @param Array $options
 	 * @return Void
 	 */
	protected function _addIdAttribute($name, array &$options) {
		$names = array(
			$this->getName(),
			$name
		);
		$parent = $this;
		while ($parent = $parent->getParent()) {
			$name = $parent->getName();
			if (!is_null($name)) {
				array_unshift($names, $parent->getName());
			}
		}
		$name = implode('-', $names);
		// if the root form has no name, the id will start with "-"
		$name = ltrim($name, '-');
		$options['id'] = strtolower($name).'-field';
	}


	/**
 	 * Retrieve default decorators
 	 * @param String $type Type of element
 	 * @param Array $options Options given with the element
 	 * @return Void
 	 */
	protected function _addDefaultDecorators($type, array &$options) {
		// Set default required label suffix
		$labelOptions = array();
		if ($this->_defaultRequiredLabelSuffix) {
			$labelOptions['requiredSuffix'] = $this->getDefaultRequiredLabelSuffix();
			$escape = isset($options['escape']) ? $options['escape'] : true;
			if (!empty($options['label']) && $escape) {
				$options['label'] = $this->getView()->escape($options['label']);
			}
			$labelOptions['escape'] = false;
			unset($options['escape']);
		}
		$options['decorators'] = array(
			'ViewHelper',
			array('Label', $labelOptions),
			'Description',
			'Errors'
		);

		if ($type != 'hidden') {
			$divWrapperOptions = array('tag' => 'div');
			if (isset($options['parentClass'])) {
				$divWrapperOptions['class'] = $options['parentClass'];
				unset($options['parentClass']);
			}
			$options['decorators'][] = array('HtmlTag', $divWrapperOptions);
		}
	}


	/**
 	 * Extend __clone to also clone attached validators, decorators and filters.
 	 * @return Void
 	 */
	public function __clone() {
		parent::__clone();

		$decorators = array();
		$oldDecorators = $this->getDecorators();
		foreach ($oldDecorators as $oldDecorator) {
			$decorators[] = clone $oldDecorator;
		}
		$this->setDecorators($decorators);
	}
}
