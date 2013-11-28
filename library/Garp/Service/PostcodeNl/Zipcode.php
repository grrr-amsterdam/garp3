<?php
/**
 * Garp_Service_PostcodeNl_Zipcode
 * BAG postal code / geodata protocol for Postcode.nl data files
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Elasticsearch
 * @lastmodified $Date: $
 */
class Garp_Service_PostcodeNl_Zipcode {
	public $zipcode;
	public $latitude;
	public $longitude;	

	protected $_valid;

	/**
 	 * @param String $line A single line from the 6PP CSV file
 	 */
	public function __construct($line) {
		$this->_load($line);
	}

	public function isValid() {
		return $this->_valid;
	}

	/**
 	 * @param String $line A single line from the 6PP CSV file
 	 */
	protected function _load($line) {
		$elements = explode(',', $line);
		$complete = count($elements) === 6;
		$this->_valid = $complete;

		if (!$complete) {
			return;
		}

		$this->zipcode = $elements[0] . ' ' . $elements[1];
		$this->latitude = $elements[4];
		$this->longitude = $elements[5];
	}
}
