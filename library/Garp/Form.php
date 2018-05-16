<?php
use Garp\Functional as f;

/**
 * Garp_Form
 * class description
 *
 * @package Garp
 * @author  Harmen Janssen <harmen@grrr.nl>
 * @todo Add setters and getters for _addHoneypotValidation and _addTimestampValidation
 */
class Garp_Form extends Zend_Form {
    /**
     * Name used for timestamp-based anti-spam field
     *
     * @var string
     */
    const TIMESTAMP_FIELD_KEY = 'ts';

    /**
     * Name used for honeypot
     *
     * @var string
     */
    const HONEYPOT_FIELD_KEY = 'hp';

    /**
     * Parent form.
     *
     * @var Garp_Form
     */
    protected $_parent;

    /**
     * Wether to set a default suffix on labels of required elements
     *
     * @var string
     */
    protected $_defaultRequiredLabelSuffix = ' <i>*</i>';

    /**
     * Wether to add a honeypot
     *
     * @var bool
     */
    protected $_addHoneypotValidation = true;

    /**
     * Wether to add timestamp validation
     *
     * @var bool
     */
    protected $_addTimestampValidation = true;

    /**
     * Wether to hijack this form using ajax
     *
     * @var bool
     */
    protected $_ajax = false;

    /**
     * Extend __clone to also clone attached validators, decorators and filters.
     *
     * @return void
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

    /**
     * Initalize!
     *
     * @return void
     */
    public function init() {
        $this->addPrefixPath('Garp_Form', 'Garp/Form')
            ->addElementPrefixPath('Garp_Form', 'Garp/Form/')
            ->addDecorator('FormElements')
            ->addDecorator('Form');

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
     * Override to unset automatically added security fields
     *
     * @param bool $suppressArrayNotation
     * @return array
     */
    public function getValues($suppressArrayNotation = false) {
        $values = parent::getValues($suppressArrayNotation);
        unset($values[self::HONEYPOT_FIELD_KEY]);
        unset($values[self::TIMESTAMP_FIELD_KEY]);
        return $values;
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

        // Escape the label, since i
        $escape = $options['escape'] ?? true;
        if (!empty($options['label']) && $escape) {
            $options['label'] = htmlspecialchars($options['label'], ENT_COMPAT, 'UTF-8');
        }

        $decoratorInheritance = $this->_parseDecoratorInheritance($options['decorators']['inherit'] ?? []);

        unset($options['decorators']['inherit']);

        // Default to a sensible set of decorators
        if (empty($options['decorators'])) {
            $options['disableLoadDefaultDecorators'] = true;
            $options['decorators'] = $this->_getDefaultDecoratorOptions(
                $type,
                $options
            );
        }

        // Class assumed to be used in getDefaultDecoratorOptions.
        unset($options['parentClass']);

        // Add the StringTrim filter by default
        if (empty($options['filters'])) {
            $options['filters'] = ['StringTrim'];
        }

        $element = parent::createElement($type, $name, $options);

        $this->_mergeDecoratorOptions($element, $decoratorInheritance['merge_options']);
        $this->_omitDefaultDecorators($element, $decoratorInheritance['omit']);
        $this->_mergeDecorators($element, $decoratorInheritance['merge']);

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
     *
     * @param string $suffix
     * @return $this
     */
    public function setDefaultRequiredLabelSuffix($suffix) {
        $this->_defaultRequiredLabelSuffix = $suffix;
        return $this;
    }

    /**
     * Get required label suffix
     *
     * @return string
     */
    public function getDefaultRequiredLabelSuffix() {
        return $this->_defaultRequiredLabelSuffix;
    }

    /**
     * Set wether to hijack the form using AJAX
     *
     * @param bool $flag
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
     * @return bool
     */
    public function getAjax() {
        return $this->_ajax;
    }

    /**
     * Add field that records how long it took to submit the form.
     * This should be longer than 1 second, otherwise we suspect spammy
     * behavior.
     *
     * @return void
     */
    public function addTimestampValidation() {
        // Add timestamp-based spam counter-measure
        $this->addElement(
            'hidden', self::TIMESTAMP_FIELD_KEY, array(
            'validators' => array(new Garp_Validate_Duration)
            )
        );
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
     *
     * @return void
     */
    public function addHoneypotValidation() {
        $this->addElement('honeypot', self::HONEYPOT_FIELD_KEY);
    }

    /**
     * Register a parent.
     *
     * @param Garp_Form $parent
     * @return $this
     */
    public function setParent(Garp_Form $parent) {
        $this->_parent = $parent;
        return $this;
    }

    /**
     * Get parent
     *
     * @return Garp_Form
     */
    public function getParent() {
        return $this->_parent;
    }

    /**
     * Check if an element needs required attribute
     *
     * @param Zend_Form_Element $element
     * @param array $options
     * @return bool
     */
    protected function _elementNeedsRequiredAttribute(Zend_Form_Element $element, array $options) {
        return isset($options['required']) &&
            $options['required'] &&
            !$element instanceof Zend_Form_Element_MultiCheckbox
        ;
    }

    /**
     * Add id attribute to the options array.
     * Called from Garp_Form::createElement()
     *
     * @param string $name Name-attribute of the element
     * @param array $options
     * @return void
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
        $options['id'] = strtolower($name) . '-field';
    }

    /**
     * Retrieve default decorators
     *
     * @param  string $type Type of element
     * @param  array  $options Options given with the element
     * @return void
     */
    protected function _getDefaultDecoratorOptions($type, array $options = []): array {
        // Set default required label suffix
        $labelOptions = [];
        if ($this->_defaultRequiredLabelSuffix) {
            $labelOptions['requiredSuffix'] = $this->getDefaultRequiredLabelSuffix();
            // labeloptions should never be escaped because of required suffix (<i>*</i>)
            $labelOptions['escape'] = false;
        }
        $decorators = [
            'ViewHelper',
            ['Label', $labelOptions],
            'Description',
            'Errors'
        ];

        if ($type != 'hidden') {
            $divWrapperOptions = ['tag' => 'div'];
            if (isset($options['parentClass'])) {
                $divWrapperOptions['class'] = $options['parentClass'];
            }
            $decorators[] = ['HtmlTag', $divWrapperOptions];
        }
        return $decorators;
    }

    /**
     * Overwrite or append existing decorator options.
     *
     * @param  Zend_Form_Element $element
     * @param  array $inheritance Spec describing which options to merge
     * @return void
     */
    protected function _mergeDecoratorOptions(Zend_Form_Element $element, array $inheritance) {
        foreach ($inheritance as $mergedDecorator) {
            $name = is_array($mergedDecorator) ? $mergedDecorator[0] : $mergedDecorator;
            $options = is_array($mergedDecorator) ? $mergedDecorator[1] : [];
            $existingDecorator = $element->getDecorator($name);
            if (!$existingDecorator) {
                continue;
            }
            foreach ($options as $key => $value) {
                $existingDecorator->setOption($key, $value);
            }
        }
    }

    /**
     * Merge a new decorator into the list of default decorators.
     * Control placement with 'at' or 'after' flags.
     *
     * @param  Zend_Form_Element $element
     * @param  array $inheritance
     * @return void
     */
    protected function _mergeDecorators(Zend_Form_Element $element, array $inheritance) {
        if (empty($inheritance)) {
            return;
        }

        $element->setDecorators(
            f\reduce(
                function ($decorators, $mergeSpec) {
                    if (!isset($mergeSpec['at']) && !isset($mergeSpec['after'])) {
                        return f\concat($decorators, [$mergeSpec]);
                    }
                    if (!isset($mergeSpec['spec'])) {
                        throw new InvalidArgumentException(
                            'key "spec" is required to describe decorator'
                        );
                    }
                    $mergeCallback = $this->_getDecoratorMergeCallback(
                        $mergeSpec['at'] ?? $mergeSpec['after']
                    );
                    $mergeFn = isset($mergeSpec['at']) ?
                        f\merge_at($mergeSpec['spec'], $mergeCallback) :
                        f\merge_after($mergeSpec['spec'], $mergeCallback);
                    return $mergeFn($decorators);
                },
                $element->getDecorators(),
                $inheritance
            )
        );
    }

    /**
     * Remove existing decorators.
     *
     * @param  Zend_Form_Element $element
     * @param  array $omitted
     * @return void
     */
    protected function _omitDefaultDecorators(Zend_Form_Element $element, array $omitted) {
        foreach ($omitted as $name) {
            $element->removeDecorator($name);
        }
    }

    protected function _parseDecoratorInheritance(array $spec): array {
        $out = [
            'merge_options' => $spec['merge_options'] ?? [],
            'merge' => $spec['merge'] ?? [],
            'omit' => $spec['omit'] ?? [],
        ];
        $rest = f\omit(f\keys($out), $spec);
        if (count($rest) > 0) {
            throw new InvalidArgumentException(
                'Unrecognized options ' . implode(', ', f\keys($rest)) . ' encountered'
            );
        }
        return $out;
    }

    protected function _getDecoratorMergeCallback(string $decoratorIdentifier) {
        return function ($decoratorValue, $decoratorKey) use ($decoratorIdentifier) {
            return $decoratorKey === $decoratorIdentifier
                || f\last(explode('_', $decoratorKey)) === $decoratorIdentifier;
        };
    }

}
