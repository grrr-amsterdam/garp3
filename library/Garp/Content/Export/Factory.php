<?php
/**
 * Garp_Content_Export_Factory
 * Generate a content exporting class
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Garp_Content_Export_Factory {
	/**
	 * Return instance of Garp_Content_Export_Abstract
	 * @param String $type The requested export type
	 * @return Garp_Content_Export_Abstract
	 */
	public static function getExporter($type) {
		// normalize type
		$className = 'Garp_Content_Export_'.ucfirst($type);
		$obj = new $className();
		if (!$obj instanceof Garp_Content_Export_Abstract) {
			throw new Garp_Content_Export_Exception("Class $className does not implement Garp_Content_Export_Abstract.");
		}
		return $obj;
	}
}