<?php
/**
 * Garp_Service_PostcodeNl_Response
 * BAG postal code / geodata protocol for Postcode.nl data files
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Elasticsearch
 * @lastmodified $Date: $
 */
class Garp_Service_PostcodeNl_Response extends ArrayObject {
	/**
 	 * @param String $content The content of the 6PP CSV file
 	 */
	public function __construct($content) {
		$lines = explode("\n", $content);
		unset($lines[0]);

		foreach ($lines as $line) {
			$node = new Garp_Service_PostcodeNl_ResponseNode($line);
			$this[] = $node;
		}
	}
}

