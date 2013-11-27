<?php
/**
 * Garp_Service_PostcodeNl_ResponseNode
 * BAG postal code / geodata protocol for Postcode.nl data files
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Elasticsearch
 * @lastmodified $Date: $
 */
class Garp_Service_PostcodeNl_ResponseNode {
	public $zip;
	public $latitude;
	public $longitude;	


	/**
 	 * @param String $line A single line from the 6PP CSV file
 	 */
	public function __construct($line) {
		$result = $this->_parse($line);
		$this->_load($result);
	}

	/**
 	 * @param String $line A single line from the 6PP CSV file
 	 */
	protected function _parse($line) {
		exit($line);
	}
}
