<?php
/**
 * Garp_Service_PostcodeNl_Zipcode_Set
 * Can be treated as a numeric array of Garp_Service_PostcodeNl_Zipcode instances.
 * BAG postal code / geodata protocol for Postcode.nl data files
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Elasticsearch
 * @lastmodified $Date: $
 */
class Garp_Service_PostcodeNl_Zipcode_Set extends ArrayObject {
	/**
 	 * @param String $content The content of the 6PP CSV file
 	 */
	public function __construct($content) {
		$lines = explode("\n", $content);

		// remove header row
		unset($lines[0]);

		array_walk($lines, array($this, '_loadNode'));
	}

	protected function _loadNode($line) {
		$node = new Garp_Service_PostcodeNl_Zipcode($line);
		if ($node->isValid()) {
			$this[] = $node;
		}

	}
}

