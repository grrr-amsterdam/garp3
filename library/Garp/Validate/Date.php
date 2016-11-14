<?php
/**
 * Garp_Validate_Date
 * Use PHP date() formats to generate regexp rules for validation
 *
 * @package Garp_Validate
 * @author  Harmen Janssen <harmen@grrr.nl>
 * @see http://nl1.php.net/manual/en/function.date.php
 */
class Garp_Validate_Date extends Zend_Validate_Abstract {

    const FORMAT_MISMATCH = 'formatMismatch';
    const INVALID_DATE    = 'dateInvalidDate';

    /**
     * Wether to validate the actual date, or only its format.
     * e.g. This checks wether a user for instance submitted 31 February.
     *
     * @var bool
     */
    protected $_validateParsedDate = false;

    protected $_messageTemplates = array(
        self::FORMAT_MISMATCH => "'%value%' does not fit the date format '%readableFormat%'",
        self::INVALID_DATE => "'%value%' does not appear to be a valid date"
    );

    protected $_messageVariables = array(
        'format'  => '_format',
        'readableFormat' => '_readableFormat'
    );

    /**
     * The chosen date format
     *
     * @var String
     */
    protected $_format;

    /**
     * A human readable format to show in the error message
     *
     * @var String
     */
    protected $_readableFormat;

    /**
     * Map date symbols to regexp
     *
     * @var String
     */
    protected $_dateRegexpMapper = array(
        // Day
        'd' => '\d{2}',
        'D' => 'Mon|Tue|Wed|Thu|Fri|Sat|Sun',
        'j' => '\d{1,2}',
        'l' => 'Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday',
        'N' => '[1-7]{1}',
        'S' => 'st|nd|rd|th',
        'w' => '[0-6]{1}',
        'z' => '\d{1,3}',
        // Week
        'W' => '\d{1,2}',
        // Month
        'F' =>
            'January|February|March|April|May|June|July|August|September|October|November|December',
        'm' => '\d{1,2}',
        'M' => 'Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec',
        'n' => '\d{1,2}',
        't' => '\d{2}',
        // Year
        'L' => '1|0',
        'o' => '\d{4}',
        'Y' => '\d{1,4}',
        'y' => '\d{2}',
        // @todo Add Full date/time formats?
    );

    /**
     * Class constructor
     *
     * @param string $format date() compatible format
     * @param string $humanReadableFormat Something to show the user
     * @return void
     */
    public function __construct($format, $humanReadableFormat = null) {
        $this->setFormat($format);
        $this->setReadableFormat($humanReadableFormat ?: $format);
    }

    public function getRegexp() {
        // construct regexp
        $regexp = '~^';
        $escape = false;
        for ($i = 0; $i < strlen($this->_format); ++$i) {
            $char = $this->_format[$i];
            $quotedChar = preg_quote($char, '~');
            // look for escape characters, this enters escape mode (literal adding of the next
            // char in the iteration
            if ($char == '\\') {
                $escape = true;
                continue;
            }
            if (!$escape && array_key_exists($char, $this->_dateRegexpMapper)) {
                $regexp .= "({$this->_dateRegexpMapper[$char]})";
            } else {
                $regexp .= $quotedChar;
            }
            // reset escape
            $escape = false;
        }
        $regexp .= '$~';
        return $regexp;
    }

    public function isValid($value) {
        $this->_setValue($value);
        $regexp = $this->getRegexp();
        if (!preg_match($regexp, $value)) {
            $this->_error(self::FORMAT_MISMATCH);
            return false;
        }
        if (!$this->_validateParsedDate) {
            return true;
        }
        if (!$this->_validateDate($value)) {
            $this->_error(self::INVALID_DATE);
            return false;
        }
        return true;
    }

    /**
     * Get format
     *
     * @return string
     */
    public function getFormat() {
        return $this->_format;
    }

    /**
     * Set format
     *
     * @param string $format
     * @return $this
     */
    public function setFormat($format) {
        $this->_format = $format;
        return $this;
    }

    /**
     * Get readableFormat
     *
     * @return string
     */
    public function getReadableFormat() {
        return $this->_readableFormat;
    }

    /**
     * Set readableFormat
     *
     * @param string $readableFormat
     * @return $this
     */
    public function setReadableFormat($readableFormat) {
        $this->_readableFormat = $readableFormat;
        return $this;
    }

    /**
     * Set wether to validate the parsed date.
     *
     * @param bool $wetherToValidate
     * @return $this
     */
    public function validateParsedDate($wetherToValidate) {
        $this->_validateParsedDate = $wetherToValidate;
        return $this;
    }

    protected function _validateDate($date) {
        $d = DateTime::createFromFormat($this->_format, $date);
        return $d && $d->format($this->_format) === $date;
    }

}

