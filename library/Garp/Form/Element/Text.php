<?php
/**
 * Garp_Form_Element_Text
 * Renders various HTML5 input fields.
 * Inspired by the Glitch library's Form package: @see https://github.com/Enrise/Glitch_Lib/tree/release-3.0/Form
 * @author Harmen Janssen | grrr.nl
 * @version 1
 * @package Garp
 * @subpackage Form
 */
class Garp_Form_Element_Text extends Zend_Form_Element_Text {
	/**#@+
	 * Constants that are used for types of elements
	 *
	 * @var string
	 */
    const DEFAULT_TYPE          = 'text';
    const FIELD_EMAIL           = 'email';
    const FIELD_EMAIL_ADDRESS   = 'emailaddress';
    const FIELD_URL             = 'url';
    const FIELD_NUMBER          = 'number';
    const FIELD_RANGE           = 'range';
    const FIELD_DATE            = 'text';
    const FIELD_MONTH           = 'month';
    const FIELD_WEEK            = 'week';
    const FIELD_TIME            = 'time';
    const FIELD_DATE_TIME       = 'text';
    const FIELD_DATE_TIME_LOCAL = 'datetime-local';
    const FIELD_SEARCH          = 'search';
    const FIELD_COLOR           = 'color';
	const FIELD_TEL             = 'tel';
    /**#@-*/

    /**
	 * Mapping of key => value pairs for the elements
	 *
	 * @var array
	 */
    protected static $_mapping = array(
        self::FIELD_EMAIL           => 'email',
        self::FIELD_EMAIL_ADDRESS   => 'email',
        self::FIELD_URL             => 'url',
        self::FIELD_NUMBER          => 'number',
        self::FIELD_RANGE           => 'range',
        self::FIELD_DATE            => 'text',
        self::FIELD_MONTH           => 'month',
        self::FIELD_WEEK            => 'week',
        self::FIELD_TIME            => 'time',
        self::FIELD_DATE_TIME       => 'text',
        self::FIELD_DATE_TIME_LOCAL => 'datetime-local',
        self::FIELD_SEARCH          => 'search',
        self::FIELD_COLOR           => 'color',
		self::FIELD_TEL             => 'tel',
    );

	/**
 	 * Constructor figures out which type of input to render
 	 * @param $spec
 	 * @param $options
 	 * @return Void
 	 */
	public function __construct($spec, $options = null) {
		if (empty($options['type'])) {
			$options['type'] = $this->_getType();
		}
		parent::__construct($spec, $options);
	}

	public function init() {
		// When using the AlphaNumeric validator, extra niftyness can be added 
		// by using the HTML5 pattern attribute.
		if ($this->getValidator('alnum')) {
			$this->setAttrib('pattern', '[A-Za-z0-9]+');
		}
	}

	/**
 	 * Take the type from the classname
 	 * @return String
 	 */
	protected function _getType() {
		$className = strtolower(get_class($this));
		$classNameParts = explode('_', $className);
		$type = array_pop($classNameParts);
		if (array_key_exists($type, self::$_mapping)) {
			return self::$_mapping[$type];
		}
        return self::DEFAULT_TYPE;
	}
}
