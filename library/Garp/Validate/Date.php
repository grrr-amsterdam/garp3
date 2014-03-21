<?php
/**
 * Garp_Validate_Date
 * Use PHP date() formats to generate regexp rules for validation
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Garp_Validate
 * @see http://nl1.php.net/manual/en/function.date.php
 *
 * @todo Build support for escaped characters. 
 * For instance: "\W\e\e\k W" should match "Week 20", but at the moment 
 * the regexp will match "\20\e\e\k 20".
 */
class Garp_Validate_Date extends Zend_Validate_Abstract {

	/**
 	 * The chosen date format
 	 * @var String
 	 */
	protected $_format;

	/**
 	 * Map date symbols to regexp
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
		'F' => 'January|February|March|April|May|June|July|August|September|October|November|December',
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
 	 * @param String $format date() compatible format
 	 * @return Void
 	 */
	public function __construct($format) {
		$this->_format = $format;
	}
	
	public function getRegexp() {
		// construct regexp
		$regexp = '~';
		for ($i = 0; $i < strlen($this->_format); ++$i) {
			$char = $this->_format[$i];
			$quotedChar = preg_quote($char, '~');
			if (array_key_exists($char, $this->_dateRegexpMapper)) {
				$regexp .= "({$this->_dateRegexpMapper[$char]})";
			} else {
				$regexp .= $quotedChar;
			}
		}
		$regexp .= '~';
		return $regexp;
	}

	public function isValid($value) {
		$regexp = $this->getRegexp();
		return preg_match($regexp, $value);

		return true;
	}

}
